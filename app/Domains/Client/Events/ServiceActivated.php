<?php

namespace App\Domains\Client\Events;

use App\Domains\Client\Models\ClientService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a service is activated and ready for use
 * 
 * This should trigger:
 * - Recurring billing creation
 * - Notifications to client and team
 * - Monitoring setup
 */
class ServiceActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientService $service
    ) {}
}
