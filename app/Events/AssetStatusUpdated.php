<?php

namespace App\Events;

use App\Domains\Asset\Models\Asset;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $asset;
    public $rmmData;
    public $isOnline;
    public $lastSeen;

    /**
     * Create a new event instance.
     */
    public function __construct(Asset $asset, array $rmmData = null)
    {
        $this->asset = $asset;
        $this->rmmData = $rmmData;
        
        // Parse RMM data for online status
        if ($rmmData && isset($rmmData['rmm_last_seen'])) {
            try {
                $lastSeenTime = \Carbon\Carbon::parse($rmmData['rmm_last_seen']);
                $this->isOnline = $lastSeenTime->diffInMinutes() < 240; // 4 hours
                $this->lastSeen = $lastSeenTime->diffForHumans();
            } catch (\Exception $e) {
                $this->isOnline = false;
                $this->lastSeen = null;
            }
        } else {
            $this->isOnline = $rmmData['rmm_online'] ?? false;
            $this->lastSeen = null;
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('assets.' . $this->asset->id),
            new Channel('company.' . $this->asset->company_id . '.assets'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'asset_id' => $this->asset->id,
            'asset_name' => $this->asset->name,
            'is_online' => $this->isOnline,
            'last_seen' => $this->lastSeen,
            'rmm_public_ip' => $this->rmmData['rmm_public_ip'] ?? null,
            'rmm_platform' => $this->rmmData['rmm_platform'] ?? null,
            'rmm_version' => $this->rmmData['rmm_version'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'AssetStatusUpdated';
    }
}
