<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Webklex\PHPIMAP\ClientManager;
use App\Contracts\Services\EmailServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Services\EmailService;
use App\Services\ImapService;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register IMAP Client Manager
        $this->app->singleton('imap', function ($app) {
            return new ClientManager($app['config']['imap']);
        });

        // Register Email Service
        $this->app->singleton(EmailServiceInterface::class, function ($app) {
            return new EmailService($app['mailer'], $app[PdfServiceInterface::class]);
        });
        
        // Also bind the concrete class for backward compatibility
        $this->app->singleton(EmailService::class, function ($app) {
            return $app[EmailServiceInterface::class];
        });

        // Register IMAP Service
        $this->app->singleton(ImapService::class, function ($app) {
            return new ImapService(
                $app['imap'],
                $app['config']['imap']
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure mail settings from environment
        $this->configureMailSettings();

        // Register mail event listeners
        $this->registerMailEventListeners();
    }

    /**
     * Configure mail settings from environment variables
     */
    protected function configureMailSettings(): void
    {
        if (config('mail.default') === 'smtp') {
            config([
                'mail.mailers.smtp.host' => env('MAIL_HOST', 'localhost'),
                'mail.mailers.smtp.port' => env('MAIL_PORT', 587),
                'mail.mailers.smtp.encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'mail.mailers.smtp.username' => env('MAIL_USERNAME'),
                'mail.mailers.smtp.password' => env('MAIL_PASSWORD'),
                'mail.from.address' => env('MAIL_FROM_ADDRESS', 'noreply@nestogy.com'),
                'mail.from.name' => env('MAIL_FROM_NAME', 'Nestogy ERP'),
            ]);
        }
    }

    /**
     * Register mail event listeners
     */
    protected function registerMailEventListeners(): void
    {
        // Listen for mail sending events
        Event::listen(MessageSending::class, function (MessageSending $event) {
            // Log outgoing emails
            logger()->info('Sending email', [
                'to' => collect($event->message->getTo())->keys()->first(),
                'subject' => $event->message->getSubject(),
                'timestamp' => now(),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event) {
            // Log sent emails
            logger()->info('Email sent successfully', [
                'to' => collect($event->message->getTo())->keys()->first(),
                'subject' => $event->message->getSubject(),
                'timestamp' => now(),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'imap',
            EmailServiceInterface::class,
            EmailService::class,
            ImapService::class,
        ];
    }
}