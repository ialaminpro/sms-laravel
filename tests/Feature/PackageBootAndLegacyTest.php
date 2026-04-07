<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests\Feature;

use Acolyte\SmsLaravel\Channels\SmsChannel;
use Acolyte\SmsLaravel\SMS;
use Acolyte\SmsLaravel\SmsManager;
use Acolyte\SmsLaravel\Tests\TestCase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;

final class PackageBootAndLegacyTest extends TestCase
{
    public function test_package_loads_config_and_manager_without_database_coupling(): void
    {
        self::assertSame('onnorokom', config('sms.default'));
        self::assertInstanceOf(SmsManager::class, $this->application()->make('sms'));
        self::assertInstanceOf(SmsChannel::class, $this->application()->make(ChannelManager::class)->driver('sms'));
        self::assertFalse($this->application()->make(Router::class)->getRoutes()->hasNamedRoute('sms.index'));
    }

    public function test_legacy_array_api_adapts_to_typed_manager(): void
    {
        Http::fake(['*' => Http::response($this->soapResponse('1900||+49123||legacy-id'))]);

        $json = SMS::send([
            'mobile' => '+49123',
            'smsText' => 'Legacy message',
            'mask' => 'Acme',
            'campaign' => 'migration',
            'client_id' => 'ignored-v2',
        ]);

        self::assertSame([
            'status' => 'success',
            'msg' => 'Successfully Delivered',
            'provider_message_id' => 'legacy-id',
            'error_code' => null,
        ], json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }
}
