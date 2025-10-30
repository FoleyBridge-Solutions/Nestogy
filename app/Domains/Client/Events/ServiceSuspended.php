<?php

namespace App\Domains\Client\Events;

use App\Domains\Client\Models\ClientService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a service is suspended
 * 
 * This should trigger:
 * - Billing suspension
 * - Notification to client
 * - Alert to account manager
 */
class ServiceSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientService $service,
        public string $reason
    ) {}
}
