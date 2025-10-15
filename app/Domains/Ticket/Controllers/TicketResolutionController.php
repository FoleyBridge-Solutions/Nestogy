<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Services\ResolutionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TicketResolutionController extends Controller
{
    public function __construct(
        private ResolutionService $resolutionService
    ) {}

    public function resolve(Request $request, Ticket $ticket)
    {
        $this->authorize('resolve', $ticket);

        $validated = $request->validate([
            'resolution_summary' => 'required|string|min:10|max:1000',
            'allow_client_reopen' => 'boolean',
        ]);

        try {
            $this->resolutionService->resolveTicket(
                $ticket,
                $request->user(),
                $validated['resolution_summary'],
                $request->boolean('allow_client_reopen', true)
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket has been resolved successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to resolve ticket: '.$e->getMessage());
        }
    }

    public function reopen(Request $request, Ticket $ticket)
    {
        $this->authorize('reopen', $ticket);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->resolutionService->reopenTicket(
                $ticket,
                $request->user(),
                $validated['reason'] ?? null
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', 'Ticket has been reopened');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reopen ticket: '.$e->getMessage());
        }
    }
}
