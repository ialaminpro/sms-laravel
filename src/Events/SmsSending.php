<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Events;

use Acolyte\SmsLaravel\Data\SmsMessage;
use DateTimeImmutable;

final readonly class SmsSending
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public SmsMessage $message,
        public string $driver,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
