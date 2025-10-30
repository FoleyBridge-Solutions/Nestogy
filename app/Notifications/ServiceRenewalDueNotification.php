<?php

namespace App\Notifications;

use App\Domains\Client\Models\ClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Service Renewal Due Notification
 * 
 * Sent at 30, 14, and 7 days before service renewal.
 * Reminds clients and account managers about upcoming renewals.
 */
class ServiceRenewalDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientService $service,
        public int $daysUntilRenewal
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $clientName = $this->service->client->name;
        $urgency = $this->daysUntilRenewal <= 7 ? 'urgent' : 'upcoming';
        $daysText = $this->daysUntilRenewal == 1 ? '1 day' : "{$this->daysUntilRenewal} days";
        
        $mail = (new MailMessage)
            ->subject("Service Renewal Due in {$daysText}: {$this->service->name}")
            ->greeting("Hello {$notifiable->name}!");

        if ($this->daysUntilRenewal <= 7) {
            $mail->line("⚠️ **Urgent:** Your service renewal is coming up soon!");
        } else {
            $mail->line("Your service renewal is approaching.");
        }

        $mail->line("**Service Details:**")
            ->line("- **Service:** {$this->service->name}")
            ->line("- **Client:** {$clientName}")
            ->line("- **Renewal Date:** {$this->service->renewal_date->format('M d, Y')}")
            ->line("- **Days Until Renewal:** {$daysText}")
            ->line("- **Monthly Cost:** $" . number_format($this->service->monthly_cost, 2));

        if ($this->service->auto_renewal) {
            $mail->line("✅ **Auto-renewal is enabled.** This service will automatically renew unless cancelled.");
        } else {
            $mail->line("⚠️ **Auto-renewal is disabled.** Please review and approve the renewal to continue service.");
        }

        return $mail->action('Review Service', route('clients.services.show', $this->service->id))
            ->line('If you have any questions, please contact your account manager.');
    }

    /**
     * Get the database representation of the notification (for PWA).
     */
    public function toDatabase($notifiable): array
    {
        $daysText = $this->daysUntilRenewal == 1 ? '1 day' : "{$this->daysUntilRenewal} days";
        
        return [
            'type' => 'service_renewal_due',
            'title' => 'Service Renewal Due',
            'message' => "Service '{$this->service->name}' for {$this->service->client->name} renews in {$daysText}",
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'client_id' => $this->service->client_id,
            'client_name' => $this->service->client->name,
            'days_until_renewal' => $this->daysUntilRenewal,
            'renewal_date' => $this->service->renewal_date->toDateString(),
            'auto_renewal_enabled' => $this->service->auto_renewal,
            'link' => route('clients.services.show', $this->service->id),
            'icon' => 'calendar',
            'color' => $this->daysUntilRenewal <= 7 ? 'danger' : 'info',
        ];
    }
}
