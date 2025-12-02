<?php

namespace App\Livewire\Email;

use App\Domains\Email\Models\EmailMessage;
use App\Traits\HasAutomaticAI;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmailMessageShow extends Component
{
    use HasAutomaticAI;

    public EmailMessage $message;

    public function mount(EmailMessage $message)
    {
        $this->message = $message;
        
        $this->initializeAI($message);
        
        $this->message->load([
            'emailAccount',
            'emailFolder',
            'attachments',
            'replyToMessage',
            'replies',
            'ticket',
            'communicationLog',
        ]);
        
        $this->message->markAsRead();
    }

    public function toggleFlag()
    {
        if ($this->message->is_flagged) {
            $this->message->unflag();
        } else {
            $this->message->flag();
        }
        
        $this->message->refresh();
    }

    public function createTicketFromEmail()
    {
        if ($this->message->ticket_id) {
            return redirect()->route('tickets.show', $this->message->ticket);
        }
        
        $ticket = $this->message->createTicketFromEmail();
        
        return redirect()->route('tickets.show', $ticket);
    }

    public function logCommunication()
    {
        if ($this->message->is_communication_logged) {
            return;
        }
        
        $this->message->logAsCommunication();
        $this->message->refresh();
    }

    public function render()
    {
        return view('livewire.email.email-message-show');
    }

    protected function getModel()
    {
        return $this->message;
    }

    protected function getAIAnalysisType(): string
    {
        return 'email_message';
    }
}
