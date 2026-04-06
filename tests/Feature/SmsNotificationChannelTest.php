<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Feature;

use Acolyte\SmsLaravel\Channels\SmsChannel;
use Acolyte\SmsLaravel\Contracts\SmsDriver;
use Acolyte\SmsLaravel\Contracts\SmsNotification;
use Acolyte\SmsLaravel\Data\SmsMessage;
use Acolyte\SmsLaravel\Data\SmsResult;
use Acolyte\SmsLaravel\SmsManager;
use Acolyte\SmsLaravel\Tests\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Notifications\Notification;

final class SmsNotificationChannelTest extends TestCase
{
    public function test_notification_can_return_a_typed_message(): void
    {
        $channel = $this->channel();

        $result = $channel->send(new class {}, new TypedNotification);

        self::assertSame('sent-to-+49123', $result->providerMessageId);
    }

    public function test_notification_body_uses_notifiable_route(): void
    {
        $channel = $this->channel();
        $notifiable = new class
        {
            public function routeNotificationForSms(Notification $notification): string
            {
                return '+49456';
            }
        };

        $result = $channel->send($notifiable, new RoutedNotification);

        self::assertSame('sent-to-+49456', $result->providerMessageId);
    }

    private function channel(): SmsChannel
    {
        $manager = $this->application()->make(SmsManager::class);
        $manager->extend('notification', fn (): SmsDriver => new class implements SmsDriver
        {
            public function send(SmsMessage $message): SmsResult
            {
                return SmsResult::success('sent-to-'.$message->to);
            }
        });
        $this->application()->make(Repository::class)->set('sms.default', 'notification');

        return new SmsChannel($manager);
    }
}

final class TypedNotification extends Notification implements SmsNotification
{
    public function toSms(object $notifiable): SmsMessage
    {
        return new SmsMessage('+49123', 'Typed notification');
    }
}

final class RoutedNotification extends Notification implements SmsNotification
{
    public function toSms(object $notifiable): string
    {
        return 'Routed notification';
    }
}
