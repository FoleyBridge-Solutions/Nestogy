<?php

namespace App\Events;

use App\Domains\Client\Models\Client;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientAIAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Client $client;
    public array $insights;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, array $insights)
    {
        $this->client = $client;
        $this->insights = $insights;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('client.'.$this->client->id);
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return $this->insights;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ClientAIAnalyzed';
    }
}
