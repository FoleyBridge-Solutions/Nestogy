<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetCommandExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $assetId;
    public string $assetName;
    public string $command;
    public string $commandType;
    public string $status;
    public ?string $output;
    public ?string $errorMessage;
    public string $executedBy;
    public string $timestamp;
    public ?string $taskId;

    /**
     * Create a new event instance.
     */
    public function __construct(array $data)
    {
        $this->assetId = $data['asset_id'];
        $this->assetName = $data['asset_name'];
        $this->command = $data['command'];
        $this->commandType = $data['command_type'] ?? 'general'; // general, service, process, terminal
        $this->status = $data['status']; // initiated, running, completed, failed
        $this->output = $data['output'] ?? null;
        $this->errorMessage = $data['error_message'] ?? null;
        $this->executedBy = $data['executed_by'];
        $this->taskId = $data['task_id'] ?? null;
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
        return 'AssetCommandExecuted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'asset_id' => $this->assetId,
            'asset_name' => $this->assetName,
            'command' => $this->command,
            'command_type' => $this->commandType,
            'status' => $this->status,
            'output' => $this->output,
            'error_message' => $this->errorMessage,
            'executed_by' => $this->executedBy,
            'task_id' => $this->taskId,
            'timestamp' => $this->timestamp,
        ];
    }
}
