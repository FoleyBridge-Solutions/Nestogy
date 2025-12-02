<?php

namespace App\Events;

use App\Domains\Lead\Models\Lead;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadAIAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Lead $lead;
    public array $insights;

    public function __construct(Lead $lead, array $insights)
    {
        $this->lead = $lead;
        $this->insights = $insights;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('lead.'.$this->lead->id);
    }

    public function broadcastWith(): array
    {
        return $this->insights;
    }

    public function broadcastAs(): string
    {
        return 'LeadAIAnalyzed';
    }
}
