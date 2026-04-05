<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel;

use Acolyte\SmsLaravel\Channels\SmsChannel;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

final class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sms.php', 'sms');

        $this->app->singleton(SmsManager::class, fn ($app): SmsManager => new SmsManager(
            $app,
            $app->make('config'),
            $app->make('events'),
            $app->make(BusDispatcher::class),
        ));

        $this->app->alias(SmsManager::class, 'sms');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/sms.php' => $this->app->configPath('sms.php'),
        ], 'sms-config');

        $this->app->afterResolving(ChannelManager::class, function (ChannelManager $manager): void {
            $manager->extend('sms', fn ($app): SmsChannel => $app->make(SmsChannel::class));
        });
    }
}
