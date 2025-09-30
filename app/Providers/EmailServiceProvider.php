<?php

namespace App\Providers;

use App\Contracts\Services\EmailServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use App\Domains\Email\Services\EmailService;
use App\Domains\Email\Services\ImapService;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Webklex\PHPIMAP\ClientManager;

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
        // Register mail event listeners
        $this->registerMailEventListeners();
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
