<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Jobs;

use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Exceptions\SmsSendingFailed;
use Acolyte\SmsLaravel\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendSmsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var list<int> */
    public array $backoff;

    /** @param list<int> $backoff */
    public function __construct(
        public readonly SmsMessage $message,
        public readonly ?string $driver = null,
        public int $tries = 3,
        array $backoff = [10, 60, 300],
    ) {
        /** @var list<int> $backoff */
        $this->backoff = $backoff;
    }

    public function handle(SmsManager $sms): void
    {
        $result = $sms->send($this->message, $this->driver);

        if (! $result->successful) {
            throw new SmsSendingFailed(
                $result->errorCode ?? 'sms_failed',
                $result->errorMessage ?? 'The SMS could not be sent.',
            );
        }
    }
}
