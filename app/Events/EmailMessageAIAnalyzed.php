<?php

namespace App\Events;

use App\Domains\Email\Models\EmailMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailMessageAIAnalyzed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EmailMessage $emailMessage;
    public array $insights;

    public function __construct(EmailMessage $emailMessage, array $insights)
    {
        $this->emailMessage = $emailMessage;
        $this->insights = $insights;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('email-message.'.$this->emailMessage->id);
    }

    public function broadcastWith(): array
    {
        return $this->insights;
    }

    public function broadcastAs(): string
    {
        return 'EmailMessageAIAnalyzed';
    }
}
