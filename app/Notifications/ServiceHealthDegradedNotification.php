<?php

namespace App\Notifications;

use App\Domains\Client\Models\ClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Service Health Degraded Notification
 * 
 * Sent when a service's health score drops significantly (>10 points).
 * Alerts account managers to proactively address issues.
 */
class ServiceHealthDegradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientService $service,
        public int $oldScore,
        public int $newScore
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
        $scoreDrop = $this->oldScore - $this->newScore;
        $healthStatus = $this->newScore >= 70 ? 'Good' : ($this->newScore >= 50 ? 'Needs Attention' : 'Critical');
        
        return (new MailMessage)
            ->subject("⚠️ Service Health Alert: {$this->service->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("The health score for one of your services has significantly decreased.")
            ->line("**Service Details:**")
            ->line("- **Service:** {$this->service->name}")
            ->line("- **Client:** {$clientName}")
            ->line("- **Previous Health Score:** {$this->oldScore}/100")
            ->line("- **Current Health Score:** {$this->newScore}/100 ({$healthStatus})")
            ->line("- **Score Drop:** -{$scoreDrop} points")
            ->line("**Recommended Actions:**")
            ->line("- Review recent incidents and SLA breaches")
            ->line("- Check client satisfaction levels")
            ->line("- Schedule a service review meeting")
            ->line("- Verify monitoring is functioning correctly")
            ->action('View Service Details', route('clients.services.show', $this->service->id))
            ->line('Proactive attention to service health helps prevent escalations.');
    }

    /**
     * Get the database representation of the notification (for PWA).
     */
    public function toDatabase($notifiable): array
    {
        $scoreDrop = $this->oldScore - $this->newScore;
        
        return [
            'type' => 'health_degraded',
            'title' => 'Service Health Degraded',
            'message' => "Health score for '{$this->service->name}' dropped from {$this->oldScore} to {$this->newScore}",
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'client_id' => $this->service->client_id,
            'client_name' => $this->service->client->name,
            'old_score' => $this->oldScore,
            'new_score' => $this->newScore,
            'score_drop' => $scoreDrop,
            'link' => route('clients.services.show', $this->service->id),
            'icon' => 'trending-down',
            'color' => $this->newScore >= 50 ? 'warning' : 'danger',
        ];
    }
}
