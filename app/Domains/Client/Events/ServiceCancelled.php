<?php

namespace App\Domains\Client\Events;

use App\Domains\Client\Models\ClientService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a service is cancelled
 * 
 * This should trigger:
 * - Billing termination
 * - Final invoice with cancellation fee
 * - Notification to client
 * - Account manager alert
 */
class ServiceCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientService $service,
        public float $cancellationFee
    ) {}
}
