<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Requests\MergeTicketRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketMergeController extends Controller
{
    public function merge(MergeTicketRequest $request, Ticket $ticket)
    {
        try {
            $user = $request->user();
            $targetTicket = Ticket::where('company_id', $user->company_id)
                ->findOrFail($request->target_ticket_id);

            DB::transaction(function () use ($ticket, $targetTicket, $request, $user) {
                if ($request->boolean('merge_comments', true)) {
                    $ticket->comments()->update(['ticket_id' => $targetTicket->id]);
                }

                if ($request->boolean('merge_time_entries', true)) {
                    $ticket->timeEntries()->update(['ticket_id' => $targetTicket->id]);
                }

                if ($request->boolean('merge_attachments', true)) {
                    $ticket->calendarEvents()->update(['ticket_id' => $targetTicket->id]);
                }

                $ticket->assignments()->update(['ticket_id' => $targetTicket->id]);

                $sourceTicketNumber = $ticket->number ?? $ticket->id;
                $mergeMessage = "Ticket #{$sourceTicketNumber} ({$ticket->subject}) was merged into this ticket";
                if ($request->filled('merge_reason')) {
                    $mergeMessage .= "\n\nReason: ".$request->merge_reason;
                }

                TicketComment::create([
                    'ticket_id' => $targetTicket->id,
                    'company_id' => $user->company_id,
                    'content' => $mergeMessage,
                    'visibility' => 'internal',
                    'author_id' => $user->id,
                ]);

                $originalTicketDetails = "Original Ticket Details:\n";
                $originalTicketDetails .= "Subject: {$ticket->subject}\n";
                $originalTicketDetails .= "Priority: {$ticket->priority}\n";
                $originalTicketDetails .= "Status: {$ticket->status}\n";
                $originalTicketDetails .= "Description: {$ticket->details}\n";

                TicketComment::create([
                    'ticket_id' => $targetTicket->id,
                    'company_id' => $user->company_id,
                    'content' => $originalTicketDetails,
                    'visibility' => 'internal',
                    'author_id' => $user->id,
                ]);

                if ($request->boolean('close_source_ticket', true)) {
                    $ticket->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                        'closed_by' => $user->id,
                    ]);
                }

                $targetTicketNumber = $targetTicket->number ?? $targetTicket->id;
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $user->company_id,
                    'content' => "This ticket was merged into Ticket #{$targetTicketNumber}",
                    'visibility' => 'internal',
                    'author_id' => $user->id,
                ]);
            });

            Log::info('Ticket merged', [
                'source_ticket_id' => $ticket->id,
                'target_ticket_id' => $targetTicket->id,
                'user_id' => $user->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket merged successfully',
                    'target_ticket_url' => route('tickets.show', $targetTicket),
                ]);
            }

            return redirect()->route('tickets.show', $targetTicket)
                ->with('success', "Ticket merged successfully into #{$targetTicket->number}");

        } catch (\Exception $e) {
            Log::error('Failed to merge ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to merge ticket',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to merge ticket');
        }
    }
}
