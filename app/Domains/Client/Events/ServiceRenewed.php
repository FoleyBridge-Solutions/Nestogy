<?php

namespace App\Domains\Client\Events;

use App\Domains\Client\Models\ClientService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a service is renewed
 */
class ServiceRenewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientService $service,
        public int $renewedMonths,
        public ?float $newPrice = null
    ) {}
}
