<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketAssignment;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Domains\Ticket\Requests\AssignTicketRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TicketStatusController extends Controller
{
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldStatus = $ticket->status;
            $newStatus = $request->status;

            $ticket->update([
                'status' => $newStatus,
                'closed_at' => strtolower($newStatus) === 'closed' ? now() : null,
                'closed_by' => strtolower($newStatus) === 'closed' ? auth()->id() : null,
                'resolved_at' => in_array(strtolower($newStatus), ['resolved', 'closed']) ? now() : null,
            ]);

            $statusChangeNote = "Status changed from {$oldStatus} to {$newStatus}";
            if ($request->filled('notes')) {
                $statusChangeNote .= "\n\nNotes: ".$request->notes;
            }

            TicketComment::create([
                'ticket_id' => $ticket->id,
                'company_id' => auth()->user()->company_id,
                'content' => $statusChangeNote,
                'visibility' => 'internal',
                'author_type' => 'user',
                'author_id' => auth()->id(),
            ]);

            Log::info('Ticket status updated', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'ticket' => $ticket->fresh(),
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Ticket status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Failed to update ticket status', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update status',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to update ticket status');
        }
    }

    public function updatePriority(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:Low,Medium,High,Critical',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldPriority = $ticket->priority;

            $ticket->update(['priority' => $request->priority]);

            $noteContent = "Priority changed from {$oldPriority} to {$request->priority}";
            if ($request->filled('notes')) {
                $noteContent .= "\n\nNotes: ".$request->notes;
            }

            TicketComment::create([
                'ticket_id' => $ticket->id,
                'company_id' => auth()->user()->company_id,
                'content' => $noteContent,
                'visibility' => 'internal',
                'author_id' => auth()->id(),
            ]);

            Log::info('Ticket priority updated', [
                'ticket_id' => $ticket->id,
                'old_priority' => $oldPriority,
                'new_priority' => $request->priority,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Priority updated successfully',
                'ticket' => $ticket->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update ticket priority', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update priority',
            ], 500);
        }
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        try {
            $oldAssignee = $ticket->assignee;
            $user = $request->user();

            $ticket->update(['assigned_to' => $request->assigned_to]);

            if ($request->assigned_to) {
                $newAssignee = User::find($request->assigned_to);
                $message = $oldAssignee ?
                    "Ticket reassigned from {$oldAssignee->name} to {$newAssignee->name}" :
                    "Ticket assigned to {$newAssignee->name}";

                TicketWatcher::firstOrCreate([
                    'company_id' => $user->company_id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $request->assigned_to,
                ], [
                    'email' => $newAssignee->email,
                    'notification_preferences' => [
                        'status_changes' => true,
                        'new_comments' => true,
                        'assignments' => true,
                        'priority_changes' => true,
                    ],
                    'is_active' => true,
                ]);
            } else {
                $message = 'Ticket unassigned';
            }

            if ($request->filled('reason')) {
                $message .= "\n\nReason: ".$request->reason;
            }

            TicketComment::create([
                'ticket_id' => $ticket->id,
                'company_id' => $user->company_id,
                'content' => $message,
                'visibility' => 'internal',
                'author_id' => $user->id,
            ]);

            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'company_id' => $user->company_id,
                'assigned_to' => $request->assigned_to,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'notes' => $request->reason,
                'is_active' => true,
            ]);

            Log::info('Ticket assigned', [
                'ticket_id' => $ticket->id,
                'old_assignee' => $oldAssignee?->id,
                'new_assignee' => $request->assigned_to,
                'user_id' => $user->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Assignment updated successfully',
                    'ticket' => $ticket->fresh(['assignee']),
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Assignment updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to assign ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update assignment',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to update assignment');
        }
    }
}
