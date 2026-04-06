<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Data;

final readonly class SmsSegments
{
    public function __construct(
        public string $encoding,
        public int $characters,
        public int $units,
        public int $segments,
        public int $unitsPerSegment,
    ) {}
}
