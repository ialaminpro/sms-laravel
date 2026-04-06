<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Feature;

use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\Events\SmsFailed;
use Acolyte\SmsLaravel\Events\SmsSending;
use Acolyte\SmsLaravel\Events\SmsSent;
use Acolyte\SmsLaravel\SmsManager;
use Acolyte\SmsLaravel\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SmsManagerAndEventsTest extends TestCase
{
    public function test_default_driver_resolves_and_sends(): void
    {
        Http::fake(['*' => Http::response('1900||default-id')]);
        $manager = $this->application()->make(SmsManager::class);

        $result = $manager->send(new SmsMessage('+49123', 'Hello'));

        self::assertSame('provider', $manager->getDefaultDriver());
        self::assertSame('default-id', $result->providerMessageId);
    }

    public function test_explicit_custom_driver_can_be_selected(): void
    {
        $manager = $this->application()->make(SmsManager::class);
        $manager->extend('custom', fn (): SmsDriver => new class implements SmsDriver
        {
            public function send(SmsMessage $message): SmsResult
            {
                return SmsResult::success('custom-'.$message->to);
            }
        });

        $result = $manager->send(new SmsMessage('+49123', 'Hello'), 'custom');

        self::assertSame('custom-+49123', $result->providerMessageId);
    }

    public function test_success_events_are_dispatched_with_driver_context(): void
    {
        Event::fake();
        Http::fake(['*' => Http::response('1900||event-id')]);

        $this->application()->make(SmsManager::class)->send(new SmsMessage('+49123', 'Hello'));

        Event::assertDispatched(SmsSending::class, fn (SmsSending $event): bool => $event->driver === 'provider');
        Event::assertDispatched(SmsSent::class, fn (SmsSent $event): bool => $event->result->providerMessageId === 'event-id');
        Event::assertNotDispatched(SmsFailed::class);
    }

    public function test_failure_event_is_dispatched(): void
    {
        Event::fake();
        Http::fake(['*' => Http::response('', 429)]);

        $this->application()->make(SmsManager::class)->send(new SmsMessage('+49123', 'Hello'));

        Event::assertDispatched(SmsFailed::class, fn (SmsFailed $event): bool => $event->result->errorCode === 'rate_limited');
        Event::assertNotDispatched(SmsSent::class);
    }

    public function test_unexpected_custom_driver_exception_is_sanitized(): void
    {
        Event::fake();
        $manager = $this->application()->make(SmsManager::class);
        $manager->extend('broken', fn (): SmsDriver => new class implements SmsDriver
        {
            public function send(SmsMessage $message): SmsResult
            {
                throw new RuntimeException('secret credentials');
            }
        });

        $result = $manager->send(new SmsMessage('+49123', 'Hello'), 'broken');

        self::assertSame('driver_exception', $result->errorCode);
        self::assertStringNotContainsString('secret', $result->errorMessage ?? '');
        Event::assertDispatched(SmsFailed::class);
    }
}
