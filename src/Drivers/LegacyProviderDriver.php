<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Drivers;

use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

final class LegacyProviderDriver implements SmsDriver
{
    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly ?string $defaultSender = null,
        private readonly int $connectTimeout = 3,
        private readonly int $timeout = 10,
        private readonly int $retries = 2,
        private readonly int $retryDelay = 200,
        private readonly string $apiKeyHeader = 'X-API-Key',
    ) {}

    public function send(SmsMessage $message): SmsResult
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            return SmsResult::failure('configuration_error', 'The SMS provider is not configured.');
        }

        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->withHeaders([$this->apiKeyHeader => $this->apiKey])
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
                ->post($this->baseUrl, array_filter([
                    'op' => 'NumberSms',
                    'type' => 'TEXT',
                    'mobile' => $message->to,
                    'smsText' => $message->body,
                    'maskName' => $message->sender ?? $this->defaultSender,
                    'clientReference' => $message->clientReference,
                ], static fn (?string $value): bool => $value !== null && $value !== ''));
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
        /** @var mixed $json */
        $json = $response->json();

        if (is_array($json)) {
            $success = $json['successful'] ?? $json['success'] ?? null;
            $providerId = $json['message_id'] ?? $json['id'] ?? null;

            if ($success === true || ($json['code'] ?? null) === 1900 || ($json['code'] ?? null) === '1900') {
                return SmsResult::success(is_scalar($providerId) ? (string) $providerId : null);
            }

            $providerCode = $json['code'] ?? null;

            if (is_scalar($providerCode)) {
                return $this->providerFailure((string) $providerCode);
            }

            return SmsResult::failure('invalid_provider_response', 'The SMS provider returned an invalid response.');
        }

        $parts = explode('||', trim($response->body()));
        $providerCode = $parts[0];

        if ($providerCode === '1900') {
            $providerId = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;

            return SmsResult::success($providerId);
        }

        if ($providerCode !== '') {
            return $this->providerFailure($providerCode);
        }

        return SmsResult::failure('invalid_provider_response', 'The SMS provider returned an invalid response.');
    }

    private function providerFailure(string $providerCode): SmsResult
    {
        [$code, $message] = match ($providerCode) {
            '1901', 'invalid_request' => ['invalid_request', 'The SMS provider rejected the request.'],
            '1902', 'authentication_failure' => ['authentication_failure', 'The SMS provider rejected the credentials.'],
            '1903', 'insufficient_balance' => ['insufficient_provider_balance', 'The SMS provider account has insufficient balance.'],
            '1904', 'rate_limited' => ['rate_limited', 'The SMS provider rate limit was exceeded.'],
            default => ['provider_error', 'The SMS provider rejected the message.'],
        };

        return SmsResult::failure($code, $message, ['provider_code' => $providerCode]);
    }
}
