<?php

namespace Acolyte\SmsLaravel;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__ . '/routes/web.php';
        $this->app->make('Acolyte\SmsLaravel\SmsController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'sms');
        $this->publishes([
            __DIR__ . '/views' => base_path('resources/views/acolyte/sms'),
            __DIR__ . '/migrations' => base_path('database/migrations'),
            __DIR__ . '/config' => base_path('config'),
        ]);
    }
}
