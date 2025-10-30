<?php

namespace App\Notifications;

use App\Domains\Client\Models\ClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Service Activated Notification
 * 
 * Sent to clients and account managers when a service is activated.
 * Supports both email and database (PWA) channels.
 */
class ServiceActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClientService $service
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
            ->subject("Service Activated: {$this->service->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Great news! Your service '{$this->service->name}' has been activated and is now live.")
            ->line("**Service Details:**")
            ->line("- **Service:** {$this->service->name}")
            ->line("- **Client:** {$clientName}")
            ->line("- **Monthly Cost:** $" . number_format($this->service->monthly_cost, 2))
            ->line("- **Billing Cycle:** {$this->service->billing_cycle}")
            ->line("- **Activated:** {$this->service->activated_at->format('M d, Y')}")
            ->line("Recurring billing has been automatically set up and invoices will be generated according to your billing cycle.")
            ->action('View Service', route('clients.services.show', $this->service->id))
            ->line('Thank you for your business!');
    }

    /**
     * Get the database representation of the notification (for PWA).
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'service_activated',
            'title' => 'Service Activated',
            'message' => "Service '{$this->service->name}' has been activated for {$this->service->client->name}",
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'client_id' => $this->service->client_id,
            'client_name' => $this->service->client->name,
            'monthly_cost' => $this->service->monthly_cost,
            'link' => route('clients.services.show', $this->service->id),
            'icon' => 'check-circle',
            'color' => 'success',
        ];
    }
}
