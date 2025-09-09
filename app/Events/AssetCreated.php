<?php

namespace App\Events;

use App\Models\Asset;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $asset;

    /**
     * Create a new event instance.
     */
    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }
}