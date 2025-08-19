<?php

namespace App\Events;

use App\Models\ContractSchedule;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractScheduleActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ContractSchedule $schedule;

    /**
     * Create a new event instance.
     */
    public function __construct(ContractSchedule $schedule)
    {
        $this->schedule = $schedule;
    }
}