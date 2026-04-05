<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Contracts;

use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;

interface SmsDriver
{
    public function send(SmsMessage $message): SmsResult;
}
