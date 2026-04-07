<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Feature;

use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Drivers\OnnoRokomDriver;
use Acolyte\SmsLaravel\Support\SmsSegmentCalculator;
use Acolyte\SmsLaravel\Tests\TestCase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

final class ProviderDriverTest extends TestCase
{
    public function test_successful_legacy_response_is_mapped_and_request_is_safe(): void
    {
        Http::fake(['sms.example.test/*' => Http::response($this->soapResponse('1900||+49123||message-42'), 200)]);

        $result = $this->driver()->send(new SmsMessage('+49 123', 'Hello', 'Acme'));

        self::assertTrue($result->successful);
        self::assertSame('message-42', $result->providerMessageId);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->hasHeader('SOAPAction', '"http://api.onnorokomsms.com/NumberSms"')
                && ! str_contains($request->url(), 'secret-token')
                && str_contains($request->body(), '<apiKey>secret-token</apiKey>')
                && str_contains($request->body(), '<numberList>+49123</numberList>')
                && str_contains($request->body(), '<messageText>Hello</messageText>')
                && str_contains($request->body(), '<smsType>TEXT</smsType>');
        });
    }

    public function test_namespaced_soap_success_is_mapped(): void
    {
        $xml = '<soap:Envelope xmlns:soap="urn:soap"><soap:Body>'
            .'<x:NumberSmsResult xmlns:x="urn:result">1900||+49123||soap-1</x:NumberSmsResult>'
            .'</soap:Body></soap:Envelope>';
        Http::fake(['*' => Http::response($xml)]);

        $result = $this->driver()->send(new SmsMessage('+49123', 'Hello'));

        self::assertTrue($result->successful);
        self::assertSame('soap-1', $result->providerMessageId);
    }

    public function test_soap_payload_escapes_values_and_selects_ucs_for_unicode(): void
    {
        Http::fake(['*' => Http::response($this->soapResponse('1900||+49123||ucs-1'))]);

        $this->driver()->send(new SmsMessage('+49123', 'হ<&', 'A&B', 'ref<1'));

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->body(), '<messageText>হ&lt;&amp;</messageText>')
                && str_contains($request->body(), '<smsType>UCS</smsType>')
                && str_contains($request->body(), '<maskName>A&amp;B</maskName>')
                && str_contains($request->body(), '<campaignName>ref&lt;1</campaignName>');
        });
    }

    /** @return iterable<string, array{int, string}> */
    public static function httpFailures(): iterable
    {
        yield 'invalid request' => [422, 'invalid_request'];
        yield 'authentication' => [401, 'authentication_failure'];
        yield 'balance' => [402, 'insufficient_provider_balance'];
        yield 'rate limit' => [429, 'rate_limited'];
        yield 'server' => [503, 'provider_server_error'];
    }

    #[DataProvider('httpFailures')]
    public function test_http_failures_are_typed(int $status, string $expectedCode): void
    {
        Http::fake(['*' => Http::response('sensitive raw provider response', $status)]);

        $result = $this->driver()->send(new SmsMessage('+49123', 'Hello'));

        self::assertFalse($result->successful);
        self::assertSame($expectedCode, $result->errorCode);
        self::assertStringNotContainsString('sensitive', $result->errorMessage ?? '');
        self::assertSame(['http_status' => $status], $result->metadata);
    }

    public function test_provider_failure_code_is_mapped_without_raw_body(): void
    {
        Http::fake(['*' => Http::response($this->soapResponse('1902||ignored||secret detail'))]);

        $result = $this->driver()->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('authentication_failure', $result->errorCode);
        self::assertSame(['provider_code' => '1902'], $result->metadata);
    }

    public function test_retry_policy_is_limited_to_transient_failures(): void
    {
        Http::fakeSequence()
            ->push('', 503)
            ->push($this->soapResponse('1900||+49123||after-retry'), 200);
        $driver = new OnnoRokomDriver(
            $this->application()->make(Factory::class),
            new SmsSegmentCalculator,
            'https://sms.example.test/send',
            'secret-token',
            retries: 2,
            retryDelay: 0,
        );

        $result = $driver->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('after-retry', $result->providerMessageId);
        Http::assertSentCount(2);
    }

    public function test_retry_policy_does_not_retry_permanent_failures(): void
    {
        Http::fake(['*' => Http::response('', 401)]);
        $driver = new OnnoRokomDriver(
            $this->application()->make(Factory::class),
            new SmsSegmentCalculator,
            'https://sms.example.test/send',
            'secret-token',
            retries: 2,
            retryDelay: 0,
        );
        $driver->send(new SmsMessage('+49123', 'Hello'));
        Http::assertSentCount(1);
    }

    public function test_malformed_response_is_reported(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $result = $this->driver()->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('invalid_provider_response', $result->errorCode);
    }

    public function test_timeout_is_reported_without_leaking_exception_details(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out with api key secret-token'));

        $result = $this->driver()->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('provider_timeout', $result->errorCode);
        self::assertStringNotContainsString('secret-token', $result->errorMessage ?? '');
    }

    public function test_network_failure_is_reported(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

        $result = $this->driver()->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('network_failure', $result->errorCode);
    }

    public function test_missing_configuration_fails_before_an_http_request(): void
    {
        Http::fake();
        $driver = new OnnoRokomDriver(
            $this->application()->make(Factory::class),
            new SmsSegmentCalculator,
            '',
            '',
        );

        $result = $driver->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('configuration_error', $result->errorCode);
        Http::assertNothingSent();
    }

    private function driver(): OnnoRokomDriver
    {
        return new OnnoRokomDriver(
            $this->application()->make(Factory::class),
            new SmsSegmentCalculator,
            'https://sms.example.test/send',
            'secret-token',
            retries: 0,
        );
    }
}
