<?php

namespace App\Jobs;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Services\ResolutionEstimateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTicketResolutionEstimates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $companyId = null,
        public ?int $ticketId = null
    ) {
    }

    public function handle(ResolutionEstimateService $service): void
    {
        if ($this->ticketId) {
            $ticket = Ticket::find($this->ticketId);
            if ($ticket) {
                $service->updateEstimateForTicket($ticket);
            }
            return;
        }

        $service->recalculateAllEstimates($this->companyId);
    }
}
