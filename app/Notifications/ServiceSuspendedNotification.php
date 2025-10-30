<?php

namespace App\Notifications;

use App\Domains\Client\Models\ClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Service Suspended Notification
 * 
 * Sent when a service is suspended.
 * Notifies both the client and internal staff.
 */
class ServiceSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientService $service,
        public string $reason
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
        
        return (new MailMessage)
            ->subject("Service Suspended: {$this->service->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your service '{$this->service->name}' has been suspended.")
            ->line("**Service Details:**")
            ->line("- **Service:** {$this->service->name}")
            ->line("- **Client:** {$clientName}")
            ->line("- **Suspended:** {$this->service->suspended_at->format('M d, Y')}")
            ->line("- **Reason:** {$this->reason}")
            ->line("Recurring billing has been automatically paused. You will not be charged while the service is suspended.")
            ->line("To reactivate this service, please contact your account manager or resolve the suspension reason.")
            ->action('View Service', route('clients.services.show', $this->service->id));
    }

    /**
     * Get the database representation of the notification (for PWA).
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'service_suspended',
            'title' => 'Service Suspended',
            'message' => "Service '{$this->service->name}' for {$this->service->client->name} has been suspended",
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'client_id' => $this->service->client_id,
            'client_name' => $this->service->client->name,
            'reason' => $this->reason,
            'link' => route('clients.services.show', $this->service->id),
            'icon' => 'pause-circle',
            'color' => 'warning',
        ];
    }
}
