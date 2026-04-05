<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Facades;

use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\SmsManager;
use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SmsResult send(SmsMessage $message, ?string $driver = null)
 * @method static void queue(SmsMessage $message, ?string $driver = null)
 * @method static SmsDriver driver(?string $driver = null)
 * @method static SmsManager extend(string $driver, Closure $callback)
 *
 * @see SmsManager
 */
final class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
