<?php

namespace App\Domains\Project\Events;

use App\Domains\Project\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Project $project,
        public string $oldStatus,
        public string $newStatus,
        public ?string $reason = null
    ) {}
}