<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Models\TicketWorkflow;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TicketQueueController extends Controller
{
    public function activeTimers(Request $request)
    {
        $user = $request->user();
        $query = TicketTimeEntry::runningTimers()
            ->with(['ticket', 'user', 'ticket.client'])
            ->where('company_id', $user->company_id);

        if (! $user->hasRole('admin') && ! $user->hasPermission('tickets.view-all-timers')) {
            $query->where('user_id', $user->id);
        }

        $activeTimers = $query->orderBy('started_at', 'desc')->paginate(20);

        $statistics = [
            'total_active' => $activeTimers->total(),
            'total_time_today' => $this->getTodayTimeStatistics($user),
            'users_with_timers' => $activeTimers->pluck('user_id')->unique()->count(),
        ];

        return view('tickets.active-timers', compact('activeTimers', 'statistics'));
    }

    public function slaViolations(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'sla_violation',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold'],
        ]);
    }

    public function slaWarning(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'sla_warning',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold'],
        ]);
    }

    public function unassigned(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'unassigned',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold'],
        ]);
    }

    public function dueToday(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'due_today',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold'],
        ]);
    }

    public function teamQueue(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'team',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold'],
        ]);
    }

    public function customerWaiting(Request $request)
    {
        return redirect()->route('tickets.index', [
            'selectedStatuses' => ['waiting_customer'],
        ]);
    }

    public function watched(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'watched',
        ]);
    }

    public function escalated(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'escalated',
            'selectedStatuses' => ['open', 'in_progress', 'waiting', 'on_hold'],
        ]);
    }

    public function merged(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'merged',
        ]);
    }

    public function archive(Request $request)
    {
        return redirect()->route('tickets.index', [
            'filter' => 'archived',
        ]);
    }

    public function timeBilling(Request $request)
    {
        $user = $request->user();
        $timeEntries = TicketTimeEntry::where('company_id', $user->company_id);

        if (! $user->hasRole('admin')) {
            $timeEntries->where('user_id', $user->id);
        }

        $timeEntries = $timeEntries->with(['ticket', 'user', 'ticket.client'])
            ->orderBy('work_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $statistics = [
            'total_hours' => $timeEntries->sum('hours_worked'),
            'billable_hours' => $timeEntries->where('billable', true)->sum('hours_worked'),
            'total_amount' => $timeEntries->sum('amount'),
        ];

        return view('tickets.time-billing', compact('timeEntries', 'statistics'));
    }

    public function analytics(Request $request)
    {
        $companyId = $request->user()->company_id;

        $metrics = [
            'total_tickets' => Ticket::where('company_id', $companyId)->count(),
            'open_tickets' => Ticket::where('company_id', $companyId)
                ->whereIn('status', ['open', 'in_progress'])->count(),
            'avg_resolution_time' => Ticket::where('company_id', $companyId)
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600) as avg_hours')
                ->value('avg_hours'),
            'tickets_by_priority' => Ticket::where('company_id', $companyId)
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'tickets_by_status' => Ticket::where('company_id', $companyId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        return view('tickets.analytics', compact('metrics'));
    }

    public function knowledgeBase(Request $request)
    {
        $articles = [];
        $categories = [];

        return view('tickets.knowledge-base', compact('articles', 'categories'));
    }

    public function automationRules(Request $request)
    {
        $workflows = TicketWorkflow::where('company_id', $request->user()->company_id)
            ->with(['creator'])
            ->orderBy('name')
            ->paginate(20);

        return view('tickets.automation-rules', compact('workflows'));
    }

    private function getTodayTimeStatistics($user)
    {
        $query = TicketTimeEntry::where('company_id', $user->company_id)
            ->where('work_date', today());

        if (! $user->hasRole('admin') && ! $user->hasPermission('tickets.view-all-timers')) {
            $query->where('user_id', $user->id);
        }

        return [
            'total_hours' => $query->sum('hours_worked'),
            'billable_hours' => $query->billable()->sum('hours_worked'),
            'entries_count' => $query->count(),
        ];
    }
}
