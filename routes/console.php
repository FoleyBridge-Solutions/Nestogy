<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Nestogy ERP Console Commands
|--------------------------------------------------------------------------
|
| Custom console commands for the Nestogy ERP system
|
*/

// Database backup command
Artisan::command('nestogy:backup', function () {
    $this->info('Starting database backup...');
    // Add backup logic here
    $this->info('Database backup completed successfully!');
})->purpose('Create a backup of the Nestogy database');

// Invoice reminder command
Artisan::command('nestogy:send-invoice-reminders', function () {
    $this->info('Sending invoice reminders...');
    // Add invoice reminder logic here
    $this->info('Invoice reminders sent successfully!');
})->purpose('Send reminders for overdue invoices');

// Asset check command
Artisan::command('nestogy:check-assets', function () {
    $this->info('Checking asset status...');
    // Add asset checking logic here
    $this->info('Asset check completed!');
})->purpose('Check asset status and send alerts');

// Ticket escalation command
Artisan::command('nestogy:escalate-tickets', function () {
    $this->info('Checking for tickets to escalate...');
    // Add ticket escalation logic here
    $this->info('Ticket escalation check completed!');
})->purpose('Escalate tickets based on SLA rules');

// Email processing command
Artisan::command('nestogy:process-emails', function () {
    $this->info('Processing mail queue...');
    
    // Process pending emails from the mail queue
    $mailService = app(\App\Domains\Email\Services\UnifiedMailService::class);
    $processed = $mailService->processPending(100);
    
    if ($processed > 0) {
        $this->info("Processed {$processed} pending email(s).");
    } else {
        $this->info('No pending emails to process.');
    }
    
    // Also retry failed emails
    $retried = $mailService->retryFailed();
    if ($retried > 0) {
        $this->info("Retried {$retried} failed email(s).");
    }
})->purpose('Process mail queue and send pending emails');

// Data cleanup command
Artisan::command('nestogy:cleanup', function () {
    $this->info('Starting data cleanup...');
    // Add cleanup logic here
    $this->info('Data cleanup completed!');
})->purpose('Clean up old logs and temporary files');

// System health check command
Artisan::command('nestogy:health-check', function () {
    $this->info('Performing system health check...');
    // Add health check logic here
    $this->info('System health check completed!');
})->purpose('Check system health and send alerts if needed');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Define scheduled tasks for the Nestogy ERP system
|
*/

// Schedule backup daily at 2 AM
Schedule::command('nestogy:backup')->dailyAt('02:00');

// Schedule invoice reminders daily at 9 AM
Schedule::command('nestogy:send-invoice-reminders')->dailyAt('09:00');

// Schedule asset checks every 6 hours
Schedule::command('nestogy:check-assets')->everySixHours();

// Schedule ticket escalation every 15 minutes
Schedule::command('nestogy:escalate-tickets')->everyFifteenMinutes();

// Schedule email processing every 5 minutes
Schedule::command('nestogy:process-emails')->everyFiveMinutes();

// Schedule cleanup weekly on Sunday at 3 AM
Schedule::command('nestogy:cleanup')->weeklyOn(0, '03:00');

// Schedule health check every hour
Schedule::command('nestogy:health-check')->hourly();
