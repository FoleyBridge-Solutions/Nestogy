<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TicketSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $exclude = $request->get('exclude');

        if (strlen($query) < 2) {
            return response()->json(['tickets' => []]);
        }

        $user = $request->user();
        $ticketsQuery = Ticket::where('company_id', $user->company_id)
            ->with(['client:id,name', 'assignee:id,name'])
            ->where('status', '!=', 'closed');

        if ($exclude) {
            $ticketsQuery->where('id', '!=', $exclude);
        }

        $ticketsQuery->where(function ($q) use ($query) {
            $q->where('number', 'like', "%{$query}%")
                ->orWhere('subject', 'like', "%{$query}%")
                ->orWhereHas('client', function ($cq) use ($query) {
                    $cq->where('name', 'like', "%{$query}%");
                });
        });

        $tickets = $ticketsQuery->orderBy('number', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($ticket) => [
                'id' => $ticket->id,
                'number' => $ticket->number,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at->toISOString(),
                'client' => $ticket->client ? [
                    'id' => $ticket->client->id,
                    'name' => $ticket->client->name,
                ] : null,
                'assignee' => $ticket->assignee ? [
                    'id' => $ticket->assignee->id,
                    'name' => $ticket->assignee->name,
                ] : null,
            ]);

        return response()->json(['tickets' => $tickets]);
    }

    public function getViewers(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $this->trackTicketView($ticket);
        $otherViewers = $this->getTicketViewers($ticket);

        return response()->json([
            'viewers' => $otherViewers,
            'message' => count($otherViewers) > 0
                ? 'Others currently viewing: '.collect($otherViewers)->pluck('name')->join(', ')
                : '',
        ]);
    }

    private function trackTicketView(Ticket $ticket): void
    {
        $user = auth()->user();
        $cacheKey = "ticket_viewer_{$ticket->id}_{$user->id}";

        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'last_viewed' => now()->toISOString(),
            'session_id' => session()->getId(),
        ], now()->addMinutes(5));
    }

    private function getTicketViewers(Ticket $ticket): array
    {
        $currentUserId = auth()->id();
        $currentSessionId = session()->getId();
        $viewers = [];

        $companyUsers = User::where('company_id', auth()->user()->company_id)
            ->active()
            ->pluck('id');

        foreach ($companyUsers as $userId) {
            $cacheKey = "ticket_viewer_{$ticket->id}_{$userId}";
            if (Cache::has($cacheKey)) {
                $viewerData = Cache::get($cacheKey);

                if ($userId != $currentUserId && $viewerData['session_id'] != $currentSessionId) {
                    $viewers[] = $viewerData;
                }
            }
        }

        return $viewers;
    }
}
