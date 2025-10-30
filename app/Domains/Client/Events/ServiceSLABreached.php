<?php

namespace App\Domains\Client\Events;

use App\Domains\Client\Models\ClientService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a service breaches its SLA
 * 
 * This should trigger:
 * - Ticket creation
 * - Notification to technicians
 * - Alert to management
 * - Health score recalculation
 */
class ServiceSLABreached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientService $service,
        public array $breachDetails
    ) {}
}
