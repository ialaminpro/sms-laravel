<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Contracts;

use Acolyte\SmsLaravel\Data\SmsMessage;

interface SmsNotification
{
    public function toSms(object $notifiable): SmsMessage|string;
}
