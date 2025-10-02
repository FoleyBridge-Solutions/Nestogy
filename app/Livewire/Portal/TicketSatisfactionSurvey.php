<?php

namespace App\Livewire\Portal;

use App\Domains\Ticket\Models\Ticket;
use App\Models\TicketRating;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TicketSatisfactionSurvey extends Component
{
    public Ticket $ticket;
    public $rating = null;
    public $feedback = '';
    public $submitted = false;
    public $existingRating = null;

    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'feedback' => 'nullable|string|max:1000',
    ];

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        
        $this->existingRating = TicketRating::where('ticket_id', $ticket->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($this->existingRating) {
            $this->rating = $this->existingRating->rating;
            $this->feedback = $this->existingRating->feedback;
            $this->submitted = true;
        }
    }

    public function setRating($value)
    {
        $this->rating = $value;
    }

    public function submitRating()
    {
        $this->validate();

        try {
            if ($this->existingRating) {
                $this->existingRating->update([
                    'rating' => $this->rating,
                    'feedback' => $this->feedback,
                ]);
            } else {
                TicketRating::create([
                    'ticket_id' => $this->ticket->id,
                    'user_id' => Auth::id(),
                    'client_id' => $this->ticket->client_id,
                    'company_id' => $this->ticket->company_id,
                    'rating' => $this->rating,
                    'feedback' => $this->feedback,
                    'rating_type' => 'satisfaction',
                ]);
            }

            $this->submitted = true;

            Flux::toast(
                text: 'Thank you for your feedback!',
                variant: 'success'
            );

            $this->dispatch('rating-submitted');

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to submit rating: ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        return view('livewire.portal.ticket-satisfaction-survey');
    }
}
