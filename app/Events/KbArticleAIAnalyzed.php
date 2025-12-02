<?php

namespace App\Events;

use App\Domains\Knowledge\Models\KbArticle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KbArticleAIAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public KbArticle $kbArticle;
    public array $insights;

    public function __construct(KbArticle $kbArticle, array $insights)
    {
        $this->kbArticle = $kbArticle;
        $this->insights = $insights;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('kb-article.'.$this->kbArticle->id);
    }

    public function broadcastWith(): array
    {
        return $this->insights;
    }

    public function broadcastAs(): string
    {
        return 'KbArticleAIAnalyzed';
    }
}
