<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comment Service
 *
 * Unified service for managing ticket comments, replacing the fragmented
 * reply system with a consistent interface.
 */
class CommentService
{
    /**
     * Add a comment to a ticket
     */
    public function addComment(
        Ticket $ticket,
        string $content,
        string $visibility = TicketComment::VISIBILITY_PUBLIC,
        ?User $author = null,
        string $source = TicketComment::SOURCE_MANUAL,
        array $options = []
    ): TicketComment {
        try {
            DB::beginTransaction();

            // Determine author
            $authorId = $author ? $author->id : ($options['author_id'] ?? null);
            $authorType = $this->determineAuthorType($source, $author);

            // Create the comment
            $comment = $ticket->comments()->create([
                'company_id' => $ticket->company_id,
                'content' => $content,
                'visibility' => $visibility,
                'source' => $source,
                'author_id' => $authorId,
                'author_type' => $authorType,
                'metadata' => $options['metadata'] ?? null,
                'parent_id' => $options['parent_id'] ?? null,
                'is_resolution' => $options['is_resolution'] ?? false,
            ]);

            // Handle time tracking if provided
            if (isset($options['time_minutes']) && $options['time_minutes'] > 0) {
                $timeEntry = $this->createTimeEntry($ticket, $author, $options['time_minutes'], $options);
                $comment->update(['time_entry_id' => $timeEntry->id]);
            }

            // Queue sentiment analysis for manual comments
            if ($source === TicketComment::SOURCE_MANUAL && ! empty($content)) {
                \App\Jobs\AnalyzeTicketSentiment::dispatch($comment->company_id, $comment->id, 'comment');
            }

            // Update ticket status if needed
            if (isset($options['update_status'])) {
                $this->updateTicketStatusFromComment($ticket, $options['update_status'], $author);
            }

            // Track first response time if this is the first staff response
            if ($this->isFirstStaffResponse($ticket, $author)) {
                $this->trackFirstResponse($ticket);
            }

            DB::commit();

            Log::info('Comment added to ticket', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'visibility' => $visibility,
                'source' => $source,
            ]);

            return $comment;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add comment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Add an internal note to a ticket
     */
    public function addInternalNote(
        Ticket $ticket,
        string $content,
        ?User $author = null,
        array $options = []
    ): TicketComment {
        return $this->addComment(
            $ticket,
            $content,
            TicketComment::VISIBILITY_INTERNAL,
            $author,
            TicketComment::SOURCE_MANUAL,
            $options
        );
    }

    /**
     * Add a system-generated comment
     */
    public function addSystemComment(
        Ticket $ticket,
        string $content,
        array $metadata = []
    ): TicketComment {
        return $this->addComment(
            $ticket,
            $content,
            TicketComment::VISIBILITY_INTERNAL,
            null,
            TicketComment::SOURCE_SYSTEM,
            ['metadata' => $metadata]
        );
    }

    /**
     * Add a workflow-generated comment
     */
    public function addWorkflowComment(
        Ticket $ticket,
        string $content,
        array $metadata = []
    ): TicketComment {
        return $this->addComment(
            $ticket,
            $content,
            TicketComment::VISIBILITY_INTERNAL,
            null,
            TicketComment::SOURCE_WORKFLOW,
            ['metadata' => $metadata]
        );
    }

    /**
     * Edit a comment
     */
    public function editComment(
        TicketComment $comment,
        string $newContent,
        User $editor
    ): bool {
        try {
            // Check permissions
            if (! $comment->canBeEditedBy($editor)) {
                throw new \Exception('User does not have permission to edit this comment');
            }

            // Store original content in metadata
            $metadata = $comment->metadata ?? [];
            $metadata['edit_history'] = $metadata['edit_history'] ?? [];
            $metadata['edit_history'][] = [
                'content' => $comment->content,
                'edited_by' => $editor->id,
                'edited_at' => now()->toIso8601String(),
            ];

            // Update comment
            $comment->update([
                'content' => $newContent,
                'metadata' => $metadata,
            ]);

            Log::info('Comment edited', [
                'comment_id' => $comment->id,
                'editor_id' => $editor->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to edit comment', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a comment
     */
    public function deleteComment(
        TicketComment $comment,
        User $deleter
    ): bool {
        try {
            // Check permissions
            if (! $comment->canBeDeletedBy($deleter)) {
                throw new \Exception('User does not have permission to delete this comment');
            }

            // Soft delete the comment
            $comment->delete();

            Log::info('Comment deleted', [
                'comment_id' => $comment->id,
                'deleter_id' => $deleter->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete comment', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a time entry for a comment
     */
    protected function createTimeEntry(
        Ticket $ticket,
        ?User $user,
        float $minutes,
        array $options
    ): TicketTimeEntry {
        $hours = $minutes / 60;

        return TicketTimeEntry::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user ? $user->id : null,
            'company_id' => $ticket->company_id,
            'description' => $options['time_description'] ?? 'Time logged with comment',
            'hours_worked' => $hours,
            'billable' => $options['billable'] ?? true,
            'work_date' => $options['work_date'] ?? now()->format('Y-m-d'),
            'entry_type' => TicketTimeEntry::TYPE_MANUAL,
            'work_type' => $options['work_type'] ?? 'general_support',
            'status' => 'draft',
        ]);
    }

    /**
     * Determine author type based on source and user
     */
    protected function determineAuthorType(string $source, ?User $author): string
    {
        if ($source === TicketComment::SOURCE_SYSTEM) {
            return TicketComment::AUTHOR_SYSTEM;
        }

        if ($source === TicketComment::SOURCE_WORKFLOW) {
            return TicketComment::AUTHOR_WORKFLOW;
        }

        if ($author) {
            if ($author->hasRole(['admin', 'manager', 'technician'])) {
                return TicketComment::AUTHOR_USER;
            }

            return TicketComment::AUTHOR_CUSTOMER;
        }

        return TicketComment::AUTHOR_SYSTEM;
    }

    /**
     * Check if this is the first staff response
     */
    protected function isFirstStaffResponse(Ticket $ticket, ?User $author): bool
    {
        if (! $author || ! $author->hasRole(['admin', 'manager', 'technician'])) {
            return false;
        }

        // Check if there are any previous staff comments
        $previousStaffComments = $ticket->comments()
            ->where('author_type', TicketComment::AUTHOR_USER)
            ->where('visibility', TicketComment::VISIBILITY_PUBLIC)
            ->count();

        return $previousStaffComments === 1; // The one we just created
    }

    /**
     * Track first response time
     */
    protected function trackFirstResponse(Ticket $ticket): void
    {
        if (! $ticket->first_response_at) {
            $ticket->update([
                'first_response_at' => now(),
                'response_time_hours' => $ticket->created_at->diffInHours(now()),
            ]);

            // Update SLA metrics
            if ($ticket->priorityQueue) {
                $ticket->priorityQueue->update([
                    'response_met_sla' => now() <= $ticket->priorityQueue->response_deadline,
                    'actual_response_at' => now(),
                ]);
            }
        }
    }

    /**
     * Update ticket status from comment
     */
    protected function updateTicketStatusFromComment(
        Ticket $ticket,
        string $newStatus,
        ?User $user
    ): void {
        $oldStatus = $ticket->status;

        if ($ticket->canTransitionTo($newStatus)) {
            $ticket->update(['status' => $newStatus]);

            // Add system comment about status change
            $this->addSystemComment(
                $ticket,
                "Status changed from {$oldStatus} to {$newStatus}",
                [
                    'action' => 'status_changed',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => $user ? $user->id : null,
                ]
            );
        }
    }

    /**
     * Get comments for client view
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getClientVisibleComments(Ticket $ticket, int $limit = 50)
    {
        return $ticket->comments()
            ->visibleToClient()
            ->with(['author', 'timeEntry'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all comments for staff view
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStaffComments(Ticket $ticket, int $limit = 50)
    {
        return $ticket->comments()
            ->with(['author', 'timeEntry', 'replies'])
            ->rootComments()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
