<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Email Processing - Check for new emails every 5 minutes
        $schedule->command('emails:process')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/email-processing.log'));

        // SLA Breach Detection - Check for SLA breaches every 15 minutes
        $schedule->command('tickets:check-sla-breaches')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/sla-breaches.log'));

        // Ticket Escalation - Check for tickets that need escalation every 30 minutes
        $schedule->command('tickets:escalate')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ticket-escalation.log'));

        // Auto-close resolved tickets after specified days
        $schedule->command('tickets:auto-close')
            ->daily()
            ->at('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/ticket-auto-close.log'));

        // Invoice Reminders - Send reminders for overdue invoices
        $schedule->command('invoices:send-reminders')
            ->daily()
            ->at('09:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/invoice-reminders.log'));

        // Recurring Invoices - Generate recurring invoices
        $schedule->command('invoices:generate-recurring')
            ->daily()
            ->at('00:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/recurring-invoices.log'));

        // Process Failed Payments - Retry failed payments hourly
        $schedule->command('payments:retry-failed')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/failed-payments.log'));

        // Database Backup - Backup database daily
        $schedule->command('backup:database')
            ->daily()
            ->at('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/database-backup.log'));

        // Clean up old backups (keep last 30 days)
        $schedule->command('backup:clean')
            ->daily()
            ->at('04:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/backup-cleanup.log'));

        // System Health Check - Monitor system health
        $schedule->command('system:health-check')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/system-health.log'));

        // Clear expired sessions
        $schedule->command('session:clear-expired')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/session-cleanup.log'));

        // Update asset warranty status
        $schedule->command('assets:update-warranty-status')
            ->daily()
            ->at('01:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/asset-warranty.log'));

        // Send project deadline reminders
        $schedule->command('projects:send-deadline-reminders')
            ->daily()
            ->at('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/project-reminders.log'));

        // Generate monthly reports
        $schedule->command('reports:generate-monthly')
            ->monthlyOn(1, '00:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/monthly-reports.log'));

        // Clean up temporary files
        $schedule->command('cleanup:temp-files')
            ->daily()
            ->at('05:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/temp-cleanup.log'));

        // Update currency exchange rates
        $schedule->command('currency:update-rates')
            ->daily()
            ->at('06:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/currency-rates.log'));

        // Send password expiry notifications
        $schedule->command('users:password-expiry-notifications')
            ->daily()
            ->at('08:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/password-expiry.log'));

        // Clean up old logs (keep last 90 days)
        $schedule->command('logs:clean')
            ->weekly()
            ->sundays()
            ->at('04:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/log-cleanup.log'));

        // Update ticket SLA status
        $schedule->command('tickets:update-sla-status')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ticket-sla.log'));

        // Send daily activity summary to managers
        $schedule->command('reports:daily-summary')
            ->dailyAt('18:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/daily-summary.log'));

        // Sync with external integrations
        $schedule->command('integrations:sync')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/integrations-sync.log'));

        // Clean up orphaned files
        $schedule->command('storage:clean-orphaned')
            ->weekly()
            ->saturdays()
            ->at('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/orphaned-files.log'));

        // Generate and send weekly performance reports
        $schedule->command('reports:weekly-performance')
            ->weekly()
            ->mondays()
            ->at('07:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/weekly-performance.log'));

        // Update search index
        $schedule->command('search:update-index')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/search-index.log'));

        // CRITICAL: Process contract renewals and send notifications
        // This protects MSP revenue by ensuring contracts auto-renew
        $schedule->command('contracts:process-renewals')
            ->daily()
            ->at('01:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/contract-renewals.log'));

        // Check and notify about expiring contracts
        $schedule->command('contracts:check-expiring')
            ->daily()
            ->at('09:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/contract-expiry.log'));

        // Queue cleanup - Remove old failed jobs
        $schedule->command('queue:prune-failed --hours=168')
            ->daily()
            ->at('04:45')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue-cleanup.log'));

        // Cache cleanup
        $schedule->command('cache:gc')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cache-cleanup.log'));

        // Monitor disk usage and send alerts
        $schedule->command('system:monitor-disk-usage')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/disk-usage.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}