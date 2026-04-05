<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Data;

final readonly class SmsResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(
        public bool $successful,
        public ?string $providerMessageId,
        public ?string $errorCode,
        public ?string $errorMessage,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function success(?string $providerMessageId = null, array $metadata = []): self
    {
        return new self(true, $providerMessageId, null, null, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function failure(string $errorCode, string $errorMessage, array $metadata = []): self
    {
        return new self(false, null, $errorCode, $errorMessage, $metadata);
    }
}
