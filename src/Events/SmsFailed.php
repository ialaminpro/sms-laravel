<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Events;

use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use DateTimeImmutable;

final readonly class SmsFailed
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public SmsMessage $message,
        public string $driver,
        public SmsResult $result,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
