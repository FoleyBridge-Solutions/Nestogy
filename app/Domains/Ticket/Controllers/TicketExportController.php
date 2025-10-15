<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Core\Controllers\Traits\UsesSelectedClient;
use App\Domains\Ticket\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Traits\FiltersClientsByAssignment;
use Illuminate\Http\Request;

class TicketExportController extends Controller
{
    use FiltersClientsByAssignment, UsesSelectedClient;

    public function generatePdf(Ticket $ticket)
    {
        $this->authorize('generatePdf', $ticket);

        $ticket->load([
            'client',
            'contact',
            'assignee',
            'creator',
            'replies.user',
            'timeEntries.user',
            'watchers.user',
        ]);

        $totalTimeWorked = $ticket->getTotalTimeWorked();
        $billableTimeWorked = $ticket->getBillableTimeWorked();

        $html = view('tickets.pdf', compact('ticket', 'totalTimeWorked', 'billableTimeWorked'))->render();

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'inline; filename="ticket-'.$ticket->number.'.html"');
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $query = Ticket::where('company_id', $user->company_id);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $query = $this->applyClientFilter($query, 'client_id');

        $tickets = $query->with(['client', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'tickets_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Ticket #',
                'Subject',
                'Client',
                'Status',
                'Priority',
                'Assignee',
                'Created Date',
                'Due Date',
                'Resolved Date',
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->number,
                    $ticket->subject,
                    $ticket->client->name,
                    ucfirst($ticket->status),
                    $ticket->priority,
                    $ticket->assignee?->name ?? 'Unassigned',
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->scheduled_at?->format('Y-m-d H:i:s') ?? '',
                    $ticket->resolved_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
