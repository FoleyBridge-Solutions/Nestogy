<?php

namespace App\Jobs;

use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notify Client of RMM Alert Job
 * 
 * Sends notifications to clients about critical RMM alerts and tickets.
 * Configurable based on client preferences and alert severity.
 */
class NotifyClientOfRMMAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Ticket $ticket;
    protected RMMAlert $alert;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(Ticket $ticket, RMMAlert $alert)
    {
        $this->ticket = $ticket;
        $this->alert = $alert;
        $this->queue = 'notifications';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing client notification for RMM alert', [
                'ticket_id' => $this->ticket->id,
                'alert_id' => $this->alert->id,
                'client_id' => $this->ticket->client_id,
                'severity' => $this->alert->severity,
            ]);

            // Check if client notifications are enabled
            if (!$this->shouldNotifyClient()) {
                Log::info('Client notifications disabled, skipping', [
                    'ticket_id' => $this->ticket->id,
                ]);
                return;
            }

            // Send email notification
            $this->sendEmailNotification();

            // Send SMS if configured for urgent alerts
            if ($this->alert->severity === RMMAlert::SEVERITY_URGENT) {
                $this->sendSMSNotification();
            }

            Log::info('Client notification completed successfully', [
                'ticket_id' => $this->ticket->id,
                'alert_id' => $this->alert->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Client notification failed', [
                'ticket_id' => $this->ticket->id,
                'alert_id' => $this->alert->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if client should be notified.
     */
    protected function shouldNotifyClient(): bool
    {
        // Check integration alert rules
        $alertRules = $this->alert->integration->alert_rules ?? [];
        if (!data_get($alertRules, 'notify_client', false)) {
            return false;
        }

        // Check client notification preferences
        $client = $this->ticket->client;
        if (!$client) {
            return false;
        }

        // Only notify for certain severity levels
        $minSeverity = data_get($alertRules, 'notify_client_min_severity', 'high');
        return $this->meetsMinimumSeverity($minSeverity);
    }

    /**
     * Check if alert meets minimum severity threshold.
     */
    protected function meetsMinimumSeverity(string $minSeverity): bool
    {
        $severityLevels = [
            RMMAlert::SEVERITY_LOW => 1,
            RMMAlert::SEVERITY_NORMAL => 2,
            RMMAlert::SEVERITY_HIGH => 3,
            RMMAlert::SEVERITY_URGENT => 4,
        ];

        $alertLevel = $severityLevels[$this->alert->severity] ?? 1;
        $minLevel = $severityLevels[$minSeverity] ?? 3;

        return $alertLevel >= $minLevel;
    }

    /**
     * Send email notification to client.
     */
    protected function sendEmailNotification(): void
    {
        $client = $this->ticket->client;
        
        // Get primary contact email
        $email = $client->email ?? $client->primaryContact()?->email;
        if (!$email) {
            Log::warning('No email address found for client notification', [
                'client_id' => $client->id,
                'ticket_id' => $this->ticket->id,
            ]);
            return;
        }

        $emailData = [
            'ticket' => $this->ticket,
            'alert' => $this->alert,
            'client' => $client,
            'severity_label' => $this->alert->getSeverityLabel(),
            'login_url' => route('client.portal.login'),
        ];

        try {
            // This would integrate with your mail system
            Log::info('Would send email notification', [
                'to' => $email,
                'subject' => "[{$this->alert->getSeverityLabel()}] System Alert - {$this->alert->message}",
                'ticket_id' => $this->ticket->id,
            ]);

            // Uncomment when mail templates are available:
            // Mail::to($email)->send(new RMMAlertNotification($emailData));

        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'email' => $email,
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification for urgent alerts.
     */
    protected function sendSMSNotification(): void
    {
        $client = $this->ticket->client;
        
        // Get primary contact phone
        $phone = $client->phone ?? $client->primaryContact()?->phone;
        if (!$phone) {
            Log::info('No phone number found for SMS notification', [
                'client_id' => $client->id,
                'ticket_id' => $this->ticket->id,
            ]);
            return;
        }

        $message = "URGENT: System alert for {$client->getDisplayName()}. " .
                  "Issue: {$this->alert->message}. " .
                  "Ticket #{$this->ticket->id} created. " .
                  "Check client portal for details.";

        try {
            // This would integrate with your SMS service
            Log::info('Would send SMS notification', [
                'to' => $phone,
                'message' => $message,
                'ticket_id' => $this->ticket->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'phone' => $phone,
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Client notification job failed permanently', [
            'ticket_id' => $this->ticket->id,
            'alert_id' => $this->alert->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'client-notification',
            'ticket:' . $this->ticket->id,
            'alert:' . $this->alert->id,
            'severity:' . $this->alert->severity,
        ];
    }
}