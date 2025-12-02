<?php

namespace App\Events;

use App\Domains\Project\Models\Project;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectAIAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Project $project;
    public array $insights;

    /**
     * Create a new event instance.
     */
    public function __construct(Project $project, array $insights)
    {
        $this->project = $project;
        $this->insights = $insights;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('project.'.$this->project->id);
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
        return 'ProjectAIAnalyzed';
    }
}
