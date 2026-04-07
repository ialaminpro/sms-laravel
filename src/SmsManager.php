<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel;

use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\Drivers\OnnoRokomDriver;
use Acolyte\SmsLaravel\Events\SmsFailed;
use Acolyte\SmsLaravel\Events\SmsSending;
use Acolyte\SmsLaravel\Events\SmsSent;
use Acolyte\SmsLaravel\Jobs\SendSmsJob;
use Acolyte\SmsLaravel\Support\SmsSegmentCalculator;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Throwable;

final class SmsManager extends Manager
{
    public function __construct(
        Container $container,
        private readonly Repository $configuration,
        private readonly EventDispatcher $events,
        private readonly BusDispatcher $bus,
    ) {
        parent::__construct($container);
    }

    public function getDefaultDriver(): string
    {
        $driver = $this->configuration->get('sms.default', 'onnorokom');

        return is_string($driver) ? $driver : 'onnorokom';
    }

    public function driver($driver = null): SmsDriver
    {
        $resolved = parent::driver($driver);

        if (! $resolved instanceof SmsDriver) {
            throw new InvalidArgumentException('SMS drivers must implement '.SmsDriver::class.'.');
        }

        return $resolved;
    }

    public function send(SmsMessage $message, ?string $driver = null): SmsResult
    {
        $driverName = $driver ?? $this->getDefaultDriver();
        $this->events->dispatch(new SmsSending($message, $driverName));

        try {
            $result = $this->driver($driverName)->send($message);
        } catch (Throwable) {
            $result = SmsResult::failure('driver_exception', 'The SMS driver failed unexpectedly.');
        }

        $event = $result->successful
            ? new SmsSent($message, $driverName, $result)
            : new SmsFailed($message, $driverName, $result);

        $this->events->dispatch($event);

        return $result;
    }

    public function queue(SmsMessage $message, ?string $driver = null): void
    {
        $tries = $this->integerConfig('sms.queue.tries', 3);
        $backoff = $this->backoffConfig();
        $job = new SendSmsJob($message, $driver, $tries, $backoff);
        $connection = $this->nullableStringConfig('sms.queue.connection');
        $queue = $this->nullableStringConfig('sms.queue.queue');

        if ($connection !== null) {
            $job->onConnection($connection);
        }

        if ($queue !== null) {
            $job->onQueue($queue);
        }

        $this->bus->dispatch($job);
    }

    protected function createOnnorokomDriver(): SmsDriver
    {
        return new OnnoRokomDriver(
            http: $this->container->make(Factory::class),
            segments: $this->container->make(SmsSegmentCalculator::class),
            baseUrl: $this->stringConfig('sms.drivers.onnorokom.base_url'),
            apiKey: $this->stringConfig('sms.drivers.onnorokom.api_key'),
            defaultSender: $this->nullableStringConfig('sms.drivers.onnorokom.sender'),
            connectTimeout: $this->integerConfig('sms.drivers.onnorokom.connect_timeout', 3),
            timeout: $this->integerConfig('sms.drivers.onnorokom.timeout', 10),
            retries: $this->integerConfig('sms.drivers.onnorokom.retries', 2),
            retryDelay: $this->integerConfig('sms.drivers.onnorokom.retry_delay', 200),
        );
    }

    private function stringConfig(string $key, string $default = ''): string
    {
        $value = $this->configuration->get($key, $default);

        return is_string($value) ? $value : $default;
    }

    private function nullableStringConfig(string $key): ?string
    {
        $value = $this->configuration->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function integerConfig(string $key, int $default): int
    {
        $value = $this->configuration->get($key, $default);

        return is_int($value) ? $value : $default;
    }

    /** @return list<int> */
    private function backoffConfig(): array
    {
        $value = $this->configuration->get('sms.queue.backoff', [10, 60, 300]);

        if (! is_array($value)) {
            return [10, 60, 300];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_int($item)));
    }
}
