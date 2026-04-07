<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Drivers;

use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\Support\SmsSegmentCalculator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

final class OnnoRokomDriver implements SmsDriver
{
    private const SOAP_ACTION = 'http://api.onnorokomsms.com/NumberSms';

    public function __construct(
        private readonly Factory $http,
        private readonly SmsSegmentCalculator $segments,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly ?string $defaultSender = null,
        private readonly int $connectTimeout = 3,
        private readonly int $timeout = 10,
        private readonly int $retries = 2,
        private readonly int $retryDelay = 200,
    ) {}

    public function send(SmsMessage $message): SmsResult
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            return SmsResult::failure('configuration_error', 'The SMS provider is not configured.');
        }

        try {
            $response = $this->http
                ->accept('text/xml')
                ->withHeaders(['SOAPAction' => '"'.self::SOAP_ACTION.'"'])
                ->withBody($this->soapEnvelope($message), 'text/xml; charset=utf-8')
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->retry(
                    $this->retries + 1,
                    $this->retryDelay,
                    when: static fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->status() === 429 || $exception->response->serverError())),
                    throw: false,
                )
                ->post($this->baseUrl);
        } catch (ConnectionException $exception) {
            $code = str_contains(strtolower($exception->getMessage()), 'timed out')
                ? 'provider_timeout'
                : 'network_failure';

            return SmsResult::failure($code, 'The SMS provider could not be reached.');
        } catch (Throwable) {
            return SmsResult::failure('network_failure', 'The SMS provider request failed.');
        }

        if (! $response->successful()) {
            return $this->httpFailure($response);
        }

        return $this->mapResponse($response);
    }

    private function soapEnvelope(SmsMessage $message): string
    {
        $encoding = $this->segments->calculate($message->body)->encoding === 'GSM-7' ? 'TEXT' : 'UCS';

        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            .'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            .'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<soap:Body><NumberSms xmlns="http://api.onnorokomsms.com/">'
            .'<apiKey>'.$this->xml($this->apiKey).'</apiKey>'
            .'<messageText>'.$this->xml($message->body).'</messageText>'
            .'<numberList>'.$this->xml($message->to).'</numberList>'
            .'<smsType>'.$encoding.'</smsType>'
            .'<maskName>'.$this->xml($message->sender ?? $this->defaultSender ?? '').'</maskName>'
            .'<campaignName>'.$this->xml($message->clientReference ?? '').'</campaignName>'
            .'</NumberSms></soap:Body></soap:Envelope>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function httpFailure(Response $response): SmsResult
    {
        [$code, $message] = match ($response->status()) {
            400, 404, 422 => ['invalid_request', 'The SMS provider rejected the request.'],
            401, 403 => ['authentication_failure', 'The SMS provider rejected the credentials.'],
            402 => ['insufficient_provider_balance', 'The SMS provider account has insufficient balance.'],
            429 => ['rate_limited', 'The SMS provider rate limit was exceeded.'],
            default => $response->serverError()
                ? ['provider_server_error', 'The SMS provider is temporarily unavailable.']
                : ['provider_error', 'The SMS provider returned an unexpected HTTP status.'],
        };

        return SmsResult::failure($code, $message, ['http_status' => $response->status()]);
    }

    private function mapResponse(Response $response): SmsResult
    {
        $matched = preg_match(
            '/<(?:[A-Za-z0-9_]+:)?NumberSmsResult\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?NumberSmsResult>/s',
            $response->body(),
            $matches,
        );

        if ($matched !== 1) {
            return SmsResult::failure('invalid_provider_response', 'The SMS provider returned an invalid response.');
        }

        $providerResponse = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $parts = explode('||', $providerResponse);
        $providerCode = $parts[0];

        if ($providerCode === '1900') {
            $providerId = isset($parts[2]) ? rtrim($parts[2], '/') : null;

            return SmsResult::success($providerId !== '' ? $providerId : null);
        }

        return $providerCode !== ''
            ? $this->providerFailure($providerCode)
            : SmsResult::failure('invalid_provider_response', 'The SMS provider returned an invalid response.');
    }

    private function providerFailure(string $providerCode): SmsResult
    {
        [$code, $message] = match ($providerCode) {
            '1901' => ['invalid_request', 'The SMS provider reported missing request content.'],
            '1902' => ['authentication_failure', 'The SMS provider rejected the credentials.'],
            '1903' => ['insufficient_provider_balance', 'The SMS provider account has insufficient balance.'],
            '1905' => ['invalid_destination', 'The SMS provider rejected the destination number.'],
            '1906' => ['operator_not_found', 'The SMS provider could not identify the destination operator.'],
            '1907' => ['invalid_sender', 'The SMS provider rejected the sender name.'],
            '1908' => ['message_too_long', 'The SMS provider rejected the message length.'],
            '1909' => ['duplicate_reference', 'The SMS provider rejected a duplicate campaign reference.'],
            '1910' => ['invalid_message', 'The SMS provider rejected the message.'],
            '1911' => ['request_limit_exceeded', 'The SMS provider rejected too many messages in one request.'],
            default => ['provider_error', 'The SMS provider rejected the message.'],
        };

        return SmsResult::failure($code, $message, ['provider_code' => $providerCode]);
    }
}
