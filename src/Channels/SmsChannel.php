<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Channels;

use Acolyte\SmsLaravel\Contracts\SmsNotification;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\SmsManager;
use Illuminate\Notifications\Notification;
use LogicException;

final readonly class SmsChannel
{
    public function __construct(private SmsManager $sms) {}

    public function send(object $notifiable, Notification $notification): SmsResult
    {
        if (! $notification instanceof SmsNotification) {
            throw new LogicException('SMS notifications must implement '.SmsNotification::class.'.');
        }

        $outbound = $notification->toSms($notifiable);

        if ($outbound instanceof SmsMessage) {
            return $this->sms->send($outbound);
        }

        $route = $this->route($notifiable, $notification);

        return $this->sms->send(new SmsMessage($route, $outbound));
    }

    private function route(object $notifiable, Notification $notification): string
    {
        $callback = [$notifiable, 'routeNotificationForSms'];

        if (! is_callable($callback)) {
            throw new LogicException('The notifiable must define routeNotificationForSms().');
        }

        /** @var mixed $route */
        $route = $callback($notification);

        if (! is_string($route) || trim($route) === '') {
            throw new LogicException('routeNotificationForSms() must return a non-empty string.');
        }

        return $route;
    }
}
