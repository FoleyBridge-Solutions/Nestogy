<?php

namespace App\Events;

use App\Domains\Contract\Models\Contract;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractAIAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contract $contract;
    public array $insights;

    public function __construct(Contract $contract, array $insights)
    {
        $this->contract = $contract;
        $this->insights = $insights;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('contract.'.$this->contract->id);
    }

    public function broadcastWith(): array
    {
        return $this->insights;
    }

    public function broadcastAs(): string
    {
        return 'ContractAIAnalyzed';
    }
}
