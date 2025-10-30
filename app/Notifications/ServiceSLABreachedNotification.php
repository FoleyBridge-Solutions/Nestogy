<?php

namespace App\Notifications;

use App\Domains\Client\Models\ClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Service SLA Breached Notification
 * 
 * Sent to internal staff when an SLA breach is recorded.
 * Alerts account managers and technical staff to take action.
 */
class ServiceSLABreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientService $service,
        public array $incidentData
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // SLA breaches are critical - use database (PWA) and mail
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $clientName = $this->service->client->name;
        $severity = $this->incidentData['severity'] ?? 'medium';
        $description = $this->incidentData['description'] ?? 'No description provided';
        
        return (new MailMessage)
            ->subject("🚨 SLA Breach: {$this->service->name} - {$clientName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("⚠️ **ALERT:** An SLA breach has been recorded for one of your services.")
            ->line("**Service Details:**")
            ->line("- **Service:** {$this->service->name}")
            ->line("- **Client:** {$clientName}")
            ->line("- **Total Breaches:** {$this->service->sla_breaches_count}")
            ->line("- **Last Breach:** {$this->service->last_sla_breach_at->format('M d, Y H:i')}")
            ->line("**Incident Details:**")
            ->line("- **Severity:** " . strtoupper($severity))
            ->line("- **Description:** {$description}")
            ->line("**Action Required:**")
            ->line("- Review the incident and take corrective action")
            ->line("- Contact the client if necessary")
            ->line("- Document resolution steps")
            ->action('View Service', route('clients.services.show', $this->service->id))
            ->line('This is an automated alert from the service monitoring system.');
    }

    /**
     * Get the database representation of the notification (for PWA).
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sla_breach',
            'title' => 'SLA Breach Alert',
            'message' => "SLA breach recorded for '{$this->service->name}' - {$this->service->client->name}",
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'client_id' => $this->service->client_id,
            'client_name' => $this->service->client->name,
            'total_breaches' => $this->service->sla_breaches_count,
            'severity' => $this->incidentData['severity'] ?? 'medium',
            'description' => $this->incidentData['description'] ?? 'No description',
            'link' => route('clients.services.show', $this->service->id),
            'icon' => 'alert-triangle',
            'color' => 'danger',
            'priority' => 'high',
        ];
    }
}
