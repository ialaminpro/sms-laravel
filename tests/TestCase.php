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
    protected function soapResponse(string $result): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<soap:Body><NumberSmsResponse xmlns="http://api.onnorokomsms.com/">'
            .'<NumberSmsResult>'.htmlspecialchars($result, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</NumberSmsResult>'
            .'</NumberSmsResponse></soap:Body></soap:Envelope>';
    }

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
        $app['config']->set('sms.drivers.onnorokom.base_url', 'https://sms.example.test/sendsms.asmx');
        $app['config']->set('sms.drivers.onnorokom.api_key', 'secret-token');
        $app['config']->set('sms.drivers.onnorokom.retries', 0);
    }
}
