<?php

namespace App\Livewire\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Services\CommentService;
use App\Domains\Ticket\Services\TimeTrackingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketShow extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    public $comment = '';

    public $internalNote = false;

    public $attachments = [];

    public $status;

    public $priority;

    public $assignedTo;

    public $timeTracking = false;

    public $timeSpent = '';

    public $timeDescription = '';

    public $billable = true;

    // Timer properties
    public $activeTimer = null;

    public $elapsedTime = '00:00:00';

    public $timerDescription = '';

    // Timer completion modal
    public $showTimerCompletionModal = false;

    public $timerWorkDescription = '';

    public $timerIsBillable = true;

    public $timerWorkType = 'general_support';

    public $pendingTimerMinutes = 0;

    public $pendingTimerHours = 0;

    public $showStatusChangeModal = false;

    public $newStatus = '';

    public $statusChangeReason = '';

    public $showTimeEntryModal = false;

    public $showUploadModal = false;

    public $draftSaved = false;

    public $reopenOnComment = false;

    public $editingCommentId = null;

    public $editingCommentText = '';

    protected $listeners = [
        'refreshTicket' => '$refresh',
        'ticketUpdated' => 'refreshTicketData',
        'timer:completion-confirmed' => 'handleTimerCompleted',
        'refreshTimer' => 'checkActiveTimer',
        'confirmed-start-timer' => 'handleConfirmedStart',
    ];

    protected $rules = [
        'comment' => 'required|min:5|max:5000',
        'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png,gif',
        'status' => 'required|in:Open,In Progress,On Hold,Resolved,Closed',
        'priority' => 'required|in:Low,Medium,High,Critical',
        'assignedTo' => 'nullable|exists:users,id',
        'timeSpent' => 'nullable|numeric|min:0.1|max:999',
        'timeDescription' => 'required_with:timeSpent|string|max:500',
    ];

    protected $messages = [
        'comment.required' => 'Please enter a comment.',
        'comment.min' => 'Comment must be at least 5 characters.',
        'comment.max' => 'Comment must not exceed 5000 characters.',
        'attachments.*.max' => 'Each file must not exceed 10MB.',
        'attachments.*.mimes' => 'Only PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, JPEG, PNG, GIF files are allowed.',
        'timeSpent.min' => 'Time spent must be at least 0.1 hours.',
        'timeSpent.max' => 'Time spent must not exceed 999 hours.',
        'timeDescription.required_with' => 'Please describe the work performed.',
    ];

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->status = $ticket->status;
        $this->priority = $ticket->priority;
        $this->assignedTo = $ticket->assigned_to;

        // Check for active timer
        $this->checkActiveTimer();

        // Load relationships
        $this->ticket->load([
            'client',
            'contact',
            'assignee',
            'requester',
            'asset',
            'project',
            'comments.author',
            'comments.attachments',
            'watchers.user',
            'timeLogs.user',
            'priorityQueue',
            'workflow',
            'creator',
            'resolver',
            'reopener',
            'closer',
        ]);
    }

    public function addComment()
    {
        try {
            $this->validate([
                'comment' => 'required|min:5|max:5000',
                'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png,gif',
            ]);

            $commentService = app(CommentService::class);

            if ($this->reopenOnComment && in_array($this->ticket->status, ['resolved', 'closed'])) {
                $oldStatus = $this->ticket->status;
                $this->ticket->status = 'open';
                $this->ticket->reopened_at = now();
                $this->ticket->reopened_by = Auth::id();
                $this->ticket->save();

                $commentService->addSystemComment(
                    $this->ticket,
                    "Ticket reopened from {$oldStatus} status"
                );
            }

            $newComment = $commentService->addComment(
                $this->ticket,
                $this->comment,
                $this->internalNote ? \App\Domains\Ticket\Models\TicketComment::VISIBILITY_INTERNAL : \App\Domains\Ticket\Models\TicketComment::VISIBILITY_PUBLIC,
                Auth::user()
            );

            if ($this->attachments) {
                foreach ($this->attachments as $attachment) {
                    $path = $attachment->store('ticket-attachments', 'public');
                    $newComment->attachments()->create([
                        'filename' => $attachment->getClientOriginalName(),
                        'path' => $path,
                        'size' => $attachment->getSize(),
                        'mime_type' => $attachment->getMimeType(),
                    ]);
                }
            }

            if ($this->timeTracking && $this->timeSpent) {
                $timeService = app(TimeTrackingService::class);
                $timeService->logTime($this->ticket, [
                    'user_id' => Auth::id(),
                    'minutes' => $this->timeSpent * 60,
                    'description' => $this->timeDescription,
                    'billable' => true,
                    'date' => now(),
                ]);
            }

            cache()->forget('ticket_comment_draft_'.$this->ticket->id.'_'.Auth::id());
            $this->reset(['comment', 'internalNote', 'attachments', 'timeSpent', 'timeDescription', 'reopenOnComment', 'draftSaved']);
            $this->refreshTicketData();
            $this->status = $this->ticket->status;

            session()->flash('message', 'Comment added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to add comment to ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $this->ticket->id,
                'user_id' => Auth::id(),
            ]);
            
            session()->flash('error', 'Failed to add comment. Please try again.');
        }
    }

    public function updateStatus()
    {
        try {
            $this->validate([
                'newStatus' => 'required|in:Open,In Progress,On Hold,Resolved,Closed',
                'statusChangeReason' => 'required|min:10|max:500',
            ]);

            $oldStatus = $this->ticket->status;
            $this->ticket->status = $this->newStatus;
            $this->ticket->save();

            $commentService = app(CommentService::class);
            $commentService->addSystemComment(
                $this->ticket,
                "Status changed from {$oldStatus} to {$this->newStatus}. Reason: {$this->statusChangeReason}"
            );

            $this->status = $this->newStatus;
            $this->reset(['showStatusChangeModal', 'newStatus', 'statusChangeReason']);

            session()->flash('message', 'Ticket status updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to update ticket status', [
                'error' => $e->getMessage(),
                'ticket_id' => $this->ticket->id,
                'user_id' => Auth::id(),
            ]);
            
            session()->flash('error', 'Failed to update status. Please try again.');
        }
    }

    public function updatePriority()
    {
        $this->validate(['priority' => 'required|in:low,medium,high,urgent']);

        $oldPriority = $this->ticket->priority;
        $this->ticket->priority = $this->priority;
        $this->ticket->save();

        // Log activity
        if ($oldPriority !== $this->priority) {
            $commentService = app(CommentService::class);
            $commentService->addSystemComment(
                $this->ticket,
                "Priority changed from {$oldPriority} to {$this->priority}"
            );
        }

        session()->flash('message', 'Priority updated successfully.');
    }

    public function updateAssignee()
    {
        $this->validate(['assignedTo' => 'nullable|exists:users,id']);

        $oldAssignee = $this->ticket->assignee?->name ?? 'Unassigned';
        $this->ticket->assigned_to = $this->assignedTo;
        $this->ticket->save();

        // Log activity
        $newAssignee = $this->ticket->fresh()->assignee?->name ?? 'Unassigned';
        if ($oldAssignee !== $newAssignee) {
            $commentService = app(CommentService::class);
            $commentService->addSystemComment(
                $this->ticket,
                "Ticket reassigned from {$oldAssignee} to {$newAssignee}"
            );
        }

        $this->ticket->refresh();
        session()->flash('message', 'Assignee updated successfully.');
    }

    public function toggleWatch()
    {
        $watcher = $this->ticket->watchers()->where('user_id', Auth::id())->first();

        if ($watcher) {
            $watcher->delete();
            session()->flash('message', 'You are no longer watching this ticket.');
        } else {
            $this->ticket->watchers()->create(['user_id' => Auth::id()]);
            session()->flash('message', 'You are now watching this ticket.');
        }

        $this->ticket->load('watchers.user');
    }

    public function deleteComment($commentId)
    {
        $comment = TicketComment::where('id', $commentId)
            ->where('ticket_id', $this->ticket->id)
            ->where('author_id', Auth::id())
            ->first();

        if ($comment && $comment->created_at->diffInMinutes(now()) < 30) {
            $comment->delete();
            $this->ticket->load('comments.author', 'comments.attachments');
            session()->flash('message', 'Comment deleted successfully.');
        } else {
            session()->flash('error', 'You can only delete your own comments within 30 minutes of posting.');
        }
    }

    public function editComment($commentId)
    {
        $comment = TicketComment::where('id', $commentId)
            ->where('ticket_id', $this->ticket->id)
            ->where('author_id', Auth::id())
            ->first();

        if ($comment && $comment->created_at->diffInMinutes(now()) < 30) {
            $this->editingCommentId = $commentId;
            $this->editingCommentText = $comment->content;
        }
    }

    public function updateComment()
    {
        if (! $this->editingCommentId) {
            return;
        }

        $comment = TicketComment::where('id', $this->editingCommentId)
            ->where('ticket_id', $this->ticket->id)
            ->where('author_id', Auth::id())
            ->first();

        if ($comment) {
            $comment->update(['content' => $this->editingCommentText]);
            $this->reset(['editingCommentId', 'editingCommentText']);
            $this->ticket->load('comments.author', 'comments.attachments');
            session()->flash('message', 'Comment updated successfully.');
        }
    }

    public function cloneTicket()
    {
        $newTicket = $this->ticket->replicate();
        $newTicket->status = 'open';
        $newTicket->closed_at = null;
        $newTicket->resolved_at = null;
        $newTicket->resolution_summary = null;
        $newTicket->subject = 'Copy of: '.$this->ticket->subject;
        $newTicket->save();

        session()->flash('message', 'Ticket cloned successfully.');

        return redirect()->route('tickets.show', $newTicket);
    }

    public function archiveTicket()
    {
        $this->ticket->update(['archived_at' => now()]);
        session()->flash('message', 'Ticket archived successfully.');

        return redirect()->route('tickets.index');
    }

    public function deleteTimeEntry($timeEntryId)
    {
        $entry = $this->ticket->timeLogs()->where('id', $timeEntryId)
            ->where('user_id', Auth::id())
            ->first();

        if ($entry) {
            $entry->delete();
            $this->ticket->load('timeLogs.user');
            session()->flash('message', 'Time entry deleted successfully.');
        }
    }

    public function checkActiveTimer()
    {
        // Check if there's an active timer for this ticket and user
        $this->activeTimer = \App\Domains\Ticket\Models\TicketTimeEntry::where('ticket_id', $this->ticket->id)
            ->where('user_id', Auth::id())
            ->where('company_id', Auth::user()->company_id)
            ->where('entry_type', \App\Domains\Ticket\Models\TicketTimeEntry::TYPE_TIMER)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->first();

        if ($this->activeTimer) {
            $this->timerDescription = $this->activeTimer->description ?? '';
            $this->updateElapsedTime();
        }
    }

    public function startTimer()
    {
        // Use the unified timer system - dispatch to navbar timer to check for existing timers
        $this->dispatch('attempt-start-timer', ticketId: $this->ticket->id);
    }

    public function handleConfirmedStart($ticketId)
    {
        // Only proceed if this is for our ticket
        if ($ticketId != $this->ticket->id) {
            return;
        }

        try {
            $timeService = app(TimeTrackingService::class);
            $this->activeTimer = $timeService->startTracking($this->ticket, Auth::user(), [
                'description' => $this->timerDescription ?: 'Working on ticket #'.$this->ticket->number,
                'work_type' => 'general_support',
                'billable' => $this->ticket->billable ?? true,
            ]);

            $this->dispatch('timerStarted');
            $this->dispatch('refreshNavbarTimer');

            Flux::toast(
                text: 'Timer started successfully',
                variant: 'success'
            );

            $this->checkActiveTimer();
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to start timer: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function stopTimer()
    {
        if (! $this->activeTimer) {
            return;
        }

        // Dispatch event to show completion modal
        $this->dispatch('timer:request-stop', timerId: $this->activeTimer->id, source: 'ticket');
    }

    public function handleTimerCompleted($data)
    {
        // Refresh timer state after completion
        $this->checkActiveTimer();

        // Reload ticket data to show updated time logs
        $this->ticket->load('timeLogs.user');
        $this->refreshTicketData();

        // Clear any local timer state
        $this->activeTimer = null;
        $this->timerDescription = '';
        $this->elapsedTime = '00:00:00';
    }

    public function updateElapsedTime()
    {
        if (! $this->activeTimer) {
            $this->elapsedTime = '00:00:00';

            return;
        }

        $startTime = \Carbon\Carbon::parse($this->activeTimer->started_at);
        $elapsed = $startTime->diffInSeconds(now());

        // Account for paused duration if any
        $pausedMinutes = $this->activeTimer->paused_duration ?? 0;
        $elapsed = max(0, $elapsed - ($pausedMinutes * 60));

        $hours = floor($elapsed / 3600);
        $minutes = floor(($elapsed % 3600) / 60);
        $seconds = $elapsed % 60;

        $this->elapsedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function addTimeEntry()
    {
        $this->validate([
            'timeSpent' => 'required|numeric|min:0.01',
            'timeDescription' => 'required|string|min:3',
        ]);

        $timeService = app(TimeTrackingService::class);
        $timeService->logTime($this->ticket, [
            'user_id' => Auth::id(),
            'minutes' => $this->timeSpent * 60,
            'description' => $this->timeDescription,
            'billable' => $this->billable,
            'date' => now(),
        ]);

        $this->ticket->load('timeLogs.user');
        $this->refreshTicketData();
        $this->reset(['timeSpent', 'timeDescription', 'showTimeEntryModal']);
        $this->billable = true; // Reset to default

        session()->flash('message', 'Time entry added successfully.');
    }

    public function saveDraft()
    {
        if ($this->comment) {
            cache()->put(
                'ticket_comment_draft_'.$this->ticket->id.'_'.Auth::id(),
                $this->comment,
                now()->addDays(1)
            );
            $this->draftSaved = true;
        }
    }

    public function loadDraft()
    {
        $draft = cache()->get('ticket_comment_draft_'.$this->ticket->id.'_'.Auth::id());
        if ($draft) {
            $this->comment = $draft;
        }
    }

    public function refreshTicketData()
    {
        $this->ticket->refresh();
        $this->ticket->load([
            'comments.author',
            'comments.attachments',
            'timeLogs.user',
            'watchers.user',
            'priorityQueue',
        ]);
    }

    public function render()
    {
        $technicians = \App\Models\User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $isWatching = $this->ticket->watchers()->where('user_id', Auth::id())->exists();

        // Load draft if exists
        if (! $this->comment) {
            $this->loadDraft();
        }

        return view('livewire.tickets.ticket-show', [
            'technicians' => $technicians,
            'isWatching' => $isWatching,
            'statuses' => ['open', 'in_progress', 'pending', 'resolved', 'closed'],
            'priorities' => ['low', 'medium', 'high', 'urgent', 'critical'],
        ]);
    }
}
