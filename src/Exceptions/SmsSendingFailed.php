<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Exceptions;

use RuntimeException;

final class SmsSendingFailed extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
