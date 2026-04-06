<?php

declare(strict_types=1);

namespace Acolyte\SmsLaravel\Tests;

use Acolyte\SmsLaravel\SmsServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Foundation\Application;
use LogicException;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function application(): Application
    {
        if (! $this->app instanceof Application) {
            throw new LogicException('The Testbench application has not been created.');
        }

        return $this->app;
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [BusServiceProvider::class, SmsServiceProvider::class];
    }

    /** @param  Application  $app */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('sms.drivers.provider.base_url', 'https://sms.example.test/send');
        $app['config']->set('sms.drivers.provider.api_key', 'secret-token');
        $app['config']->set('sms.drivers.provider.retries', 0);
    }
}
