<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Data;

use InvalidArgumentException;

final readonly class SmsMessage
{
    public string $to;

    public string $body;

    public ?string $sender;

    public ?string $clientReference;

    public function __construct(
        string $to,
        string $body,
        ?string $sender = null,
        ?string $clientReference = null,
    ) {
        $normalizedTo = preg_replace('/\s+/', '', $to);

        if ($normalizedTo === null || $normalizedTo === '') {
            throw new InvalidArgumentException('The SMS recipient must not be empty.');
        }

        if ($body === '') {
            throw new InvalidArgumentException('The SMS body must not be empty.');
        }

        if (preg_match('//u', $body) !== 1) {
            throw new InvalidArgumentException('The SMS body must contain valid UTF-8.');
        }

        $this->to = $normalizedTo;
        $this->body = $body;
        $this->sender = self::nullableTrim($sender);
        $this->clientReference = self::nullableTrim($clientReference);
    }

    private static function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
