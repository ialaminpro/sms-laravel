<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Feature;

use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\Exceptions\SmsSendingFailed;
use Acolyte\SmsLaravel\Jobs\SendSmsJob;
use Acolyte\SmsLaravel\SmsManager;
use Acolyte\SmsLaravel\Tests\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Bus;

final class SmsQueueTest extends TestCase
{
    public function test_queue_dispatches_a_safe_serializable_job(): void
    {
        Bus::fake();
        $config = $this->application()->make(Repository::class);
        $config->set('sms.queue.connection', 'redis');
        $config->set('sms.queue.queue', 'outbound-sms');

        $this->application()->make(SmsManager::class)->queue(new SmsMessage('+49123', 'Queued'), 'provider');

        Bus::assertDispatched(SendSmsJob::class, function (SendSmsJob $job): bool {
            return $job->message->to === '+49123'
                && $job->driver === 'provider'
                && $job->connection === 'redis'
                && $job->queue === 'outbound-sms';
        });
    }

    public function test_job_resolves_the_named_driver_at_execution_time(): void
    {
        $manager = $this->application()->make(SmsManager::class);
        $calls = new DriverCallCounter;
        $manager->extend('counting', fn (): SmsDriver => new CountingDriver($calls, true));
        $job = new SendSmsJob(new SmsMessage('+49123', 'Queued'), 'counting');

        $job->handle($manager);

        self::assertSame(1, $calls->count);
    }

    public function test_failed_job_throws_a_typed_safe_exception_for_queue_retries(): void
    {
        $manager = $this->application()->make(SmsManager::class);
        $manager->extend('failing', fn (): SmsDriver => new CountingDriver(new DriverCallCounter, false));
        $job = new SendSmsJob(new SmsMessage('+49123', 'Queued'), 'failing');

        $this->expectException(SmsSendingFailed::class);
        $this->expectExceptionMessage('Provider unavailable.');

        $job->handle($manager);
    }
}

final class DriverCallCounter
{
    public int $count = 0;
}

final readonly class CountingDriver implements SmsDriver
{
    public function __construct(private DriverCallCounter $counter, private bool $successful) {}

    public function send(SmsMessage $message): SmsResult
    {
        $this->counter->count++;

        return $this->successful
            ? SmsResult::success('queued-id')
            : SmsResult::failure('provider_server_error', 'Provider unavailable.');
    }
}
