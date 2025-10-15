<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Requests\ScheduleTicketRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class TicketSchedulingController extends Controller
{
    public function schedule(ScheduleTicketRequest $request, Ticket $ticket)
    {
        try {
            $ticket->update([
                'scheduled_at' => $request->scheduled_at,
                'scheduled_duration' => $request->scheduled_duration,
            ]);

            if ($request->filled('notes')) {
                \App\Domains\Ticket\Models\TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $request->user()->company_id,
                    'content' => "Ticket scheduled for {$request->scheduled_at}\n\nNotes: {$request->notes}",
                    'visibility' => 'internal',
                    'author_id' => $request->user()->id,
                ]);
            }

            Log::info('Ticket scheduled', [
                'ticket_id' => $ticket->id,
                'scheduled_at' => $request->scheduled_at,
                'user_id' => $request->user()->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket scheduled successfully',
                    'ticket' => $ticket->fresh(),
                ]);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket scheduled successfully');

        } catch (\Exception $e) {
            Log::error('Failed to schedule ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to schedule ticket',
                ], 500);
            }

            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Failed to schedule ticket');
        }
    }
}
