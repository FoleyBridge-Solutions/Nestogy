<?php

namespace App\Livewire;

use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Services\CommentService;
use App\Domains\Ticket\Services\TimeTrackingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class TimerBatchCompletionModal extends Component
{
    // Modal state
    public $showModal = false;

    public $activeTimers = [];

    public $timerDetails = [];

    // Batch options
    public $applyToAll = false;

    public $batchDescription = '';

    public $batchWorkType = 'general_support';

    public $batchIsBillable = true;

    public $batchAddComment = true;

    // Individual timer settings
    public $individualSettings = [];

    // Processing state
    public $isProcessing = false;

    public $processedCount = 0;

    public $totalCount = 0;

    // Work type options
    public $workTypes = [];

    protected $listeners = [
        'timer:request-stop-all' => 'handleBatchStopRequest',
    ];

    public function mount()
    {
        $this->workTypes = \App\Domains\Ticket\Models\TimeEntryTemplate::getAvailableWorkTypes();
    }

    #[On('timer:request-stop-all')]
    public function handleBatchStopRequest()
    {
        try {
            // Get all active timers for the current user
            $service = app(TimeTrackingService::class);
            $this->activeTimers = $service->getAllActiveTimers(Auth::user());

            if ($this->activeTimers->isEmpty()) {
                Flux::toast(
                    text: 'No active timers found',
                    variant: 'warning'
                );

                return;
            }

            // Prepare timer details for display
            $this->timerDetails = [];
            $this->individualSettings = [];
            $this->totalCount = $this->activeTimers->count();

            foreach ($this->activeTimers as $timer) {
                $elapsed = $timer->getElapsedTime();
                $hours = round($elapsed / 60, 2);

                $this->timerDetails[] = [
                    'id' => $timer->id,
                    'ticket_id' => $timer->ticket_id,
                    'ticket_number' => $timer->ticket->number ?? $timer->ticket_id,
                    'ticket_subject' => $timer->ticket->subject ?? 'Untitled',
                    'elapsed_minutes' => $elapsed,
                    'elapsed_hours' => $hours,
                    'elapsed_display' => $this->formatElapsedTime($elapsed),
                ];

                // Initialize individual settings
                $this->individualSettings[$timer->id] = [
                    'description' => $timer->description ?? 'Worked on ticket #'.($timer->ticket->number ?? $timer->ticket_id),
                    'work_type' => $timer->work_type ?? 'general_support',
                    'is_billable' => $timer->billable ?? true,
                    'add_comment' => true,
                ];
            }

            // Show the modal
            $this->showModal = true;

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to load active timers: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function confirmStopAll()
    {
        // Validate
        if ($this->applyToAll) {
            $this->validate([
                'batchDescription' => 'required|string|min:5|max:1000',
                'batchWorkType' => 'required|string',
            ], [
                'batchDescription.required' => 'Please describe what you worked on',
                'batchDescription.min' => 'Description must be at least 5 characters',
            ]);
        } else {
            // Validate individual settings
            foreach ($this->individualSettings as $timerId => $settings) {
                $this->validate([
                    "individualSettings.{$timerId}.description" => 'required|string|min:5|max:1000',
                    "individualSettings.{$timerId}.work_type" => 'required|string',
                ], [
                    "individualSettings.{$timerId}.description.required" => 'All timers need a description',
                    "individualSettings.{$timerId}.description.min" => 'Description must be at least 5 characters',
                ]);
            }
        }

        $this->isProcessing = true;
        $this->processedCount = 0;

        DB::beginTransaction();

        try {
            $service = app(TimeTrackingService::class);
            $commentService = app(CommentService::class);
            $results = [];

            foreach ($this->activeTimers as $timer) {
                // Determine settings to use
                if ($this->applyToAll) {
                    $description = $this->batchDescription;
                    $workType = $this->batchWorkType;
                    $isBillable = $this->batchIsBillable;
                    $addComment = $this->batchAddComment;
                } else {
                    $settings = $this->individualSettings[$timer->id];
                    $description = $settings['description'];
                    $workType = $settings['work_type'];
                    $isBillable = $settings['is_billable'];
                    $addComment = $settings['add_comment'];
                }

                // Stop the timer
                $result = $service->stopTracking($timer, [
                    'description' => $description,
                    'work_performed' => $description,
                    'work_type' => $workType,
                    'billable' => $isBillable,
                ]);

                // Update work type and billable if changed
                $result->work_type = $workType;
                $result->billable = $isBillable;
                $result->save();

                // Add comment if requested
                if ($addComment && $timer->ticket) {
                    $hours = round($result->hours_worked, 2);
                    $minutes = round($result->minutes_worked, 0);
                    $workTypeLabel = ucwords(str_replace('_', ' ', $workType));

                    $commentContent = "⏱️ **Time Entry Logged** ({$hours} hours / {$minutes} minutes)\n\n";
                    $commentContent .= "**Work Performed:** {$description}\n";
                    $commentContent .= "**Work Type:** {$workTypeLabel}\n";
                    $commentContent .= '**Billable:** '.($isBillable ? 'Yes' : 'No');

                    if ($isBillable && $result->amount > 0) {
                        $commentContent .= " (Amount: \${$result->amount})";
                    }

                    $commentService->addComment(
                        ticket: $timer->ticket,
                        content: $commentContent,
                        visibility: TicketComment::VISIBILITY_INTERNAL,
                        author: Auth::user(),
                        source: TicketComment::SOURCE_SYSTEM,
                        options: [
                            'metadata' => [
                                'time_entry_id' => $result->id,
                                'auto_generated' => true,
                                'type' => 'timer_completion',
                                'batch_stop' => true,
                            ],
                        ]
                    );
                }

                $results[] = [
                    'timer_id' => $timer->id,
                    'hours' => round($result->hours_worked, 2),
                    'amount' => $result->amount,
                ];

                $this->processedCount++;
            }

            DB::commit();

            // Calculate totals
            $totalHours = collect($results)->sum('hours');
            $totalAmount = collect($results)->sum('amount');

            // Show success message
            $message = "{$this->processedCount} timers stopped - {$totalHours}h total";
            if ($totalAmount > 0) {
                $message .= " (\${$totalAmount})";
            }

            Flux::toast(
                heading: 'All Timers Stopped',
                text: $message,
                variant: 'success'
            );

            // Dispatch events
            $this->dispatch('timer:batch-completion-confirmed', results: $results);
            $this->dispatch('refreshTimer');
            $this->dispatch('refreshNavbarTimer');
            $this->dispatch('time-entry-updated');

            // Close modal
            $this->closeModal();

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                text: 'Failed to stop timers: '.$e->getMessage(),
                variant: 'danger'
            );

            $this->isProcessing = false;
        }
    }

    public function cancelBatchStop()
    {
        $this->closeModal();
    }

    protected function closeModal()
    {
        $this->showModal = false;
        $this->reset(['activeTimers', 'timerDetails', 'individualSettings', 'batchDescription', 'batchWorkType']);
        $this->applyToAll = false;
        $this->batchIsBillable = true;
        $this->batchAddComment = true;
        $this->isProcessing = false;
        $this->processedCount = 0;
        $this->totalCount = 0;
    }

    protected function formatElapsedTime($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d:00', $hours, $mins);
    }

    public function render()
    {
        return view('livewire.timer-batch-completion-modal');
    }
}
