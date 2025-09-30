<?php

namespace App\Providers;

use App\Mail\Transport\Smtp2goTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class Smtp2goServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the SMTP2GO transport
        Mail::extend('smtp2go', function (array $config = []) {
            if (! isset($config['api_key']) || empty($config['api_key'])) {
                throw new \InvalidArgumentException('SMTP2GO API key is required');
            }

            return new Smtp2goTransport($config['api_key']);
        });
    }
}
