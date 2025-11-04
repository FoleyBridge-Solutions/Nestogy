<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetProcessUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $assetId;
    public string $assetName;
    public array $processes;
    public int $totalProcesses;
    public array $topCpuProcesses;
    public array $topMemoryProcesses;
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data)
    {
        $this->assetId = $data['asset_id'];
        $this->assetName = $data['asset_name'];
        $this->processes = $data['processes'] ?? [];
        $this->totalProcesses = $data['total_processes'] ?? 0;
        $this->topCpuProcesses = $data['top_cpu_processes'] ?? [];
        $this->topMemoryProcesses = $data['top_memory_processes'] ?? [];
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("assets.{$this->assetId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'AssetProcessUpdate';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'asset_id' => $this->assetId,
            'asset_name' => $this->assetName,
            'processes' => $this->processes,
            'total_processes' => $this->totalProcesses,
            'top_cpu_processes' => $this->topCpuProcesses,
            'top_memory_processes' => $this->topMemoryProcesses,
            'timestamp' => $this->timestamp,
        ];
    }
}
