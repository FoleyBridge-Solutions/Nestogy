<?php

namespace App\Livewire;

use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Models\TimeEntryTemplate;
use App\Domains\Ticket\Services\CommentService;
use App\Domains\Ticket\Services\TimeTrackingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class TimerCompletionModal extends Component
{
    // Modal state
    public $showModal = false;

    public $showDiscardConfirmation = false;

    public $timerId = null;

    public $activeTimer = null;

    // Timer data
    public $ticketId = null;

    public $ticketNumber = null;

    public $ticketSubject = null;

    public $elapsedMinutes = 0;

    public $elapsedHours = 0;

    public $elapsedDisplay = '00:00:00';

    // Form fields
    public $workDescription = '';

    public $workType = 'general_support';

    public $isBillable = true;

    public $selectedTemplateId = null;

    public $addCommentToTicket = true;

    // Smart suggestions
    public $suggestedTemplates = [];

    public $suggestedWorkType = 'general_support';

    public $rateInfo = [];

    // Work type options
    public $workTypes = [];

    protected $listeners = [
        'timer:request-stop' => 'handleTimerStopRequest',
    ];

    public function mount()
    {
        $this->workTypes = TimeEntryTemplate::getAvailableWorkTypes();
    }

    #[On('timer:request-stop')]
    public function handleTimerStopRequest($timerId, $source = 'unknown')
    {
        try {
            // Load the timer
            $this->activeTimer = TicketTimeEntry::with('ticket')->find($timerId);

            if (! $this->activeTimer) {
                Flux::toast(
                    text: 'Timer not found',
                    variant: 'danger'
                );

                return;
            }

            // Verify ownership
            if ($this->activeTimer->user_id !== Auth::id()) {
                Flux::toast(
                    text: 'You can only stop your own timers',
                    variant: 'danger'
                );

                return;
            }

            // Set timer data
            $this->timerId = $timerId;
            $this->ticketId = $this->activeTimer->ticket_id;
            $this->ticketNumber = $this->activeTimer->ticket->number ?? $this->ticketId;
            $this->ticketSubject = $this->activeTimer->ticket->subject ?? 'Untitled';

            // Calculate elapsed time
            $this->elapsedMinutes = $this->activeTimer->getElapsedTime();
            $this->elapsedHours = round($this->elapsedMinutes / 60, 2);
            $this->elapsedDisplay = $this->formatElapsedTime($this->elapsedMinutes);

            // Load smart suggestions
            $this->loadSmartSuggestions();

            // Pre-fill work description if timer already has one
            if ($this->activeTimer->description && $this->activeTimer->description !== 'Working on ticket #'.$this->ticketNumber) {
                $this->workDescription = $this->activeTimer->description;
            }

            // Pre-fill work type if set
            if ($this->activeTimer->work_type) {
                $this->workType = $this->activeTimer->work_type;
            }

            // Pre-fill billable status
            $this->isBillable = $this->activeTimer->billable ?? true;

            // Show the modal
            $this->showModal = true;

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to load timer: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    protected function loadSmartSuggestions()
    {
        $service = app(TimeTrackingService::class);

        // Get rate info for current time
        $this->rateInfo = $service->getSmartRateInfo(now(), [
            'priority' => $this->activeTimer->ticket->priority ?? null,
            'client_id' => $this->activeTimer->ticket->client_id ?? null,
        ]);

        // Get template suggestions based on ticket content
        if ($this->activeTimer->ticket) {
            $this->suggestedTemplates = TimeEntryTemplate::getSuggestionsForTicket(
                Auth::user()->company_id,
                $this->activeTimer->ticket->subject ?? '',
                $this->activeTimer->ticket->description ?? ''
            )->take(3)->map(function ($item) {
                return [
                    'id' => $item['template']->id,
                    'name' => $item['template']->name,
                    'description' => $item['template']->description,
                    'work_type' => $item['template']->work_type,
                    'confidence' => round($item['confidence']),
                ];
            })->toArray();

            // Set suggested work type from best matching template
            if (! empty($this->suggestedTemplates)) {
                $this->suggestedWorkType = $this->suggestedTemplates[0]['work_type'];
            }
        }
    }

    public function selectTemplate($templateId)
    {
        $template = TimeEntryTemplate::find($templateId);
        if ($template) {
            $this->selectedTemplateId = $templateId;
            $this->workDescription = $template->description;
            $this->workType = $template->work_type;
            $this->isBillable = $template->is_billable;
        }
    }

    public function confirmStopTimer()
    {
        // Validate
        $this->validate([
            'workDescription' => 'required|string|min:5|max:1000',
            'workType' => 'required|string',
            'isBillable' => 'boolean',
        ], [
            'workDescription.required' => 'Please describe what you worked on',
            'workDescription.min' => 'Description must be at least 5 characters',
            'workType.required' => 'Please select a work type',
        ]);

        try {
            $service = app(TimeTrackingService::class);

            // Stop the timer with the provided details
            $result = $service->stopTracking($this->activeTimer, [
                'description' => $this->workDescription,
                'work_performed' => $this->workDescription,
                'work_type' => $this->workType,
                'billable' => $this->isBillable,
            ]);

            // Update work type if changed
            $result->work_type = $this->workType;
            $result->billable = $this->isBillable;
            $result->save();

            // Add a comment to the ticket if requested
            if ($this->addCommentToTicket && $this->activeTimer->ticket) {
                $commentService = app(CommentService::class);

                // Format the comment content
                $hours = round($result->hours_worked, 2);
                $minutes = round($result->minutes_worked, 0);
                $workTypeLabel = ucwords(str_replace('_', ' ', $this->workType));

                $commentContent = "⏱️ **Time Entry Logged** ({$hours} hours / {$minutes} minutes)\n\n";
                $commentContent .= "**Work Performed:** {$this->workDescription}\n";
                $commentContent .= "**Work Type:** {$workTypeLabel}\n";
                $commentContent .= '**Billable:** '.($this->isBillable ? 'Yes' : 'No');

                if ($this->isBillable && $result->amount > 0) {
                    $commentContent .= " (Amount: \${$result->amount})";
                }

                // Add the comment as internal visibility
                $commentService->addComment(
                    ticket: $this->activeTimer->ticket,
                    content: $commentContent,
                    visibility: TicketComment::VISIBILITY_INTERNAL,
                    author: Auth::user(),
                    source: TicketComment::SOURCE_SYSTEM,
                    options: [
                        'metadata' => [
                            'time_entry_id' => $result->id,
                            'auto_generated' => true,
                            'type' => 'timer_completion',
                        ],
                    ]
                );
            }

            // If a template was used, increment its usage
            if ($this->selectedTemplateId) {
                $template = TimeEntryTemplate::find($this->selectedTemplateId);
                if ($template) {
                    $template->incrementUsage();
                }
            }

            $hours = round($result->hours_worked, 2);
            $amount = $result->amount;

            // Dispatch success event
            $this->dispatch('timer:completion-confirmed', [
                'timerId' => $this->timerId,
                'hours' => $hours,
                'amount' => $amount,
            ]);

            // Show success message
            $message = "{$hours}h recorded";
            if ($amount > 0) {
                $message .= " (\${$amount})";
            }

            Flux::toast(
                heading: 'Timer stopped',
                text: $message,
                variant: 'success'
            );

            // Refresh components
            $this->dispatch('refreshTimer');
            $this->dispatch('refreshNavbarTimer');
            $this->dispatch('time-entry-updated');

            // Close modal
            $this->closeModal();

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to stop timer: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function cancelTimer()
    {
        // Keep timer running, just close modal
        $this->dispatch('timer:cancelled', timerId: $this->timerId);
        $this->closeModal();
    }

    public function discardTimer()
    {
        // Show discard confirmation modal
        $this->showDiscardConfirmation = true;
    }

    public function confirmDiscardTimer()
    {
        try {
            // Delete the timer entry completely
            $this->activeTimer->delete();

            Flux::toast(
                text: 'Timer discarded',
                variant: 'warning'
            );

            // Refresh components
            $this->dispatch('refreshTimer');
            $this->dispatch('refreshNavbarTimer');
            $this->dispatch('time-entry-updated');

            // Close both modals
            $this->showDiscardConfirmation = false;
            $this->closeModal();

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to discard timer',
                variant: 'danger'
            );
            $this->showDiscardConfirmation = false;
        }
    }

    public function cancelDiscard()
    {
        $this->showDiscardConfirmation = false;
    }

    protected function closeModal()
    {
        $this->showModal = false;
        $this->reset(['timerId', 'activeTimer', 'workDescription', 'workType', 'selectedTemplateId']);
        $this->isBillable = true;
        $this->addCommentToTicket = true;
    }

    protected function formatElapsedTime($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        $secs = 0; // We don't track seconds in the database

        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    }

    public function render()
    {
        return view('livewire.timer-completion-modal');
    }
}
