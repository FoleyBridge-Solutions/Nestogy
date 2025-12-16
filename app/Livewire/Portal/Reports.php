<?php

namespace App\Livewire\Portal;

use App\Domains\Client\Models\Contact;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Reports extends Component
{
    public $activeTab = 'support';

    public $dateRange = '6months';

    public $startDate;

    public $endDate;

    public Contact $contact;

    public $client;

    public $permissions = [];

    public function mount()
    {
        $this->contact = auth('client')->user();
        $this->client = $this->contact->client;
        $this->permissions = $this->contact->portal_permissions ?? [];

        // Check if user has reports permission
        if (! in_array('can_view_reports', $this->permissions)) {
            abort(403, 'You do not have permission to view reports.');
        }

        // Calculate initial date range
        $this->calculateDateRange();

        // Set first available tab based on permissions
        $availableTabs = $this->getAvailableTabs();
        if (! empty($availableTabs)) {
            $this->activeTab = $availableTabs[0]['id'];
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function setDateRange($range)
    {
        $this->dateRange = $range;
        $this->calculateDateRange();
    }

    protected function calculateDateRange(): void
    {
        $this->endDate = now();
        $this->startDate = match ($this->dateRange) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            '12months' => now()->subMonths(12),
            default => now()->subMonths(6),
        };
    }

    public function getAvailableTabs(): array
    {
        $tabs = [];

        if (in_array('can_view_tickets', $this->permissions)) {
            $tabs[] = ['id' => 'support', 'name' => 'Support Analytics', 'icon' => 'lifebuoy'];
        }

        if (in_array('can_view_invoices', $this->permissions)) {
            $tabs[] = ['id' => 'financial', 'name' => 'Financial Reports', 'icon' => 'banknotes'];
        }

        if (in_array('can_view_assets', $this->permissions)) {
            $tabs[] = ['id' => 'assets', 'name' => 'Asset Reports', 'icon' => 'server-stack'];
        }

        if (in_array('can_view_projects', $this->permissions)) {
            $tabs[] = ['id' => 'projects', 'name' => 'Project Reports', 'icon' => 'briefcase'];
        }

        if (in_array('can_view_contracts', $this->permissions)) {
            $tabs[] = ['id' => 'contracts', 'name' => 'Contract Reports', 'icon' => 'document-text'];
        }

        if (in_array('can_view_quotes', $this->permissions)) {
            $tabs[] = ['id' => 'quotes', 'name' => 'Quote Reports', 'icon' => 'clipboard-document-check'];
        }

        return $tabs;
    }

    // ============================================
    // SUPPORT ANALYTICS
    // ============================================

    #[Computed]
    public function ticketStats()
    {
        if (! $this->client) {
            return null;
        }

        $tickets = $this->client->tickets();
        $periodTickets = $this->client->tickets()->whereBetween('created_at', [$this->startDate, $this->endDate]);

        return [
            'total_tickets' => $periodTickets->count(),
            'open_tickets' => $tickets->whereRaw('LOWER(status) IN (?, ?, ?, ?)', ['open', 'in progress', 'waiting', 'on hold'])->count(),
            'avg_resolution_time' => $this->calculateAvgResolutionTime(),
            'satisfaction_score' => $this->calculateSatisfactionScore(),
        ];
    }

    #[Computed]
    public function ticketTrends()
    {
        if (! $this->client) {
            return null;
        }

        $openedTickets = $this->client->tickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $closedTickets = $this->client->tickets()
            ->where(function ($query) {
                $query->whereBetween('closed_at', [$this->startDate, $this->endDate])
                    ->orWhereBetween('resolved_at', [$this->startDate, $this->endDate]);
            })
            ->whereRaw('LOWER(status) IN (?, ?)', ['resolved', 'closed'])
            ->selectRaw('
                EXTRACT(YEAR FROM COALESCE(closed_at, resolved_at)) as year,
                EXTRACT(MONTH FROM COALESCE(closed_at, resolved_at)) as month,
                COUNT(*) as count
            ')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $months = [];
        $opened = [];
        $closed = [];

        $period = Carbon::parse($this->startDate);
        while ($period <= $this->endDate) {
            $months[] = $period->format('M Y');
            $key = $period->format('Y-m');
            $opened[] = $openedTickets->get($key)?->count ?? 0;
            $closed[] = $closedTickets->get($key)?->count ?? 0;
            $period->addMonth();
        }

        return [
            'labels' => $months,
            'opened' => $opened,
            'closed' => $closed,
        ];
    }

    #[Computed]
    public function ticketsByStatus()
    {
        if (! $this->client) {
            return null;
        }

        $tickets = $this->client->tickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('LOWER(status) as status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return [
            'labels' => $tickets->pluck('status')->map(fn ($s) => ucwords($s))->toArray(),
            'data' => $tickets->pluck('count')->toArray(),
        ];
    }

    #[Computed]
    public function ticketsByPriority()
    {
        if (! $this->client) {
            return null;
        }

        $tickets = $this->client->tickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('LOWER(priority) as priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        return [
            'labels' => $tickets->pluck('priority')->map(fn ($p) => ucfirst($p))->toArray(),
            'data' => $tickets->pluck('count')->toArray(),
        ];
    }

    #[Computed]
    public function recentTickets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->tickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function calculateAvgResolutionTime()
    {
        $tickets = $this->client->tickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereNotNull('resolved_at')
            ->get();

        if ($tickets->isEmpty()) {
            return 'N/A';
        }

        $totalHours = $tickets->sum(function ($ticket) {
            return $ticket->created_at->diffInHours($ticket->resolved_at);
        });

        $avgHours = round($totalHours / $tickets->count(), 1);

        if ($avgHours < 24) {
            return $avgHours.'h';
        }

        return round($avgHours / 24, 1).'d';
    }

    protected function calculateSatisfactionScore()
    {
        $ratings = $this->client->tickets()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereHas('ratings')
            ->with('ratings')
            ->get()
            ->flatMap->ratings;

        if ($ratings->isEmpty()) {
            return 'N/A';
        }

        return round($ratings->avg('rating'), 1).'/5';
    }

    // ============================================
    // FINANCIAL REPORTS
    // ============================================

    #[Computed]
    public function invoiceStats()
    {
        if (! $this->client) {
            return null;
        }

        $invoices = $this->client->invoices();
        $periodInvoices = $this->client->invoices()->whereBetween('date', [$this->startDate, $this->endDate]);

        return [
            'total_invoiced' => $periodInvoices->sum('amount'),
            'outstanding_balance' => $invoices->where('status', 'sent')->sum('amount'),
            'payments_made' => $periodInvoices->whereHas('payments')->with('payments')->get()->sum(fn ($inv) => $inv->payments->sum('amount')),
            'overdue_amount' => $invoices->where('status', 'sent')->where('due_date', '<', now())->sum('amount'),
        ];
    }

    #[Computed]
    public function spendingTrends()
    {
        if (! $this->client) {
            return null;
        }

        $invoiceData = $this->client->invoices()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $months = [];
        $amounts = [];

        $period = Carbon::parse($this->startDate);
        while ($period <= $this->endDate) {
            $months[] = $period->format('M Y');
            $key = $period->format('Y-m');
            $amounts[] = (float) ($invoiceData->get($key)?->total ?? 0);
            $period->addMonth();
        }

        return [
            'labels' => $months,
            'amounts' => $amounts,
        ];
    }

    #[Computed]
    public function invoiceAging()
    {
        if (! $this->client) {
            return null;
        }

        $invoices = $this->client->invoices()->where('status', 'sent')->get();

        $aging = [
            'current' => 0,
            '1-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
        ];

        foreach ($invoices as $invoice) {
            $daysOverdue = now()->diffInDays($invoice->due_date, false);

            if ($daysOverdue >= 0) {
                $aging['current'] += $invoice->amount;
            } elseif ($daysOverdue >= -30) {
                $aging['1-30'] += $invoice->amount;
            } elseif ($daysOverdue >= -60) {
                $aging['31-60'] += $invoice->amount;
            } elseif ($daysOverdue >= -90) {
                $aging['61-90'] += $invoice->amount;
            } else {
                $aging['90+'] += $invoice->amount;
            }
        }

        return [
            'labels' => array_keys($aging),
            'data' => array_values($aging),
        ];
    }

    #[Computed]
    public function paymentMethods()
    {
        if (! $this->client) {
            return null;
        }

        $payments = $this->client->invoices()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereHas('payments')
            ->with('payments')
            ->get()
            ->flatMap->payments;

        $methods = $payments->groupBy('payment_method')->map->sum('amount');

        return [
            'labels' => $methods->keys()->toArray(),
            'data' => $methods->values()->toArray(),
        ];
    }

    #[Computed]
    public function recentInvoices()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->invoices()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
    }

    // ============================================
    // ASSET REPORTS
    // ============================================

    #[Computed]
    public function assetStats()
    {
        if (! $this->client) {
            return null;
        }

        $assets = $this->client->assets();

        return [
            'total_assets' => $assets->count(),
            'active_assets' => $assets->where('status', 'active')->count(),
            'warranty_expiring' => $assets->where('warranty_expire', '<=', now()->addDays(60))->where('warranty_expire', '>', now())->count(),
            'maintenance_due' => $assets->where('next_maintenance_date', '<=', now()->addDays(30))->where('next_maintenance_date', '>', now())->count(),
        ];
    }

    #[Computed]
    public function assetsByType()
    {
        if (! $this->client) {
            return null;
        }

        $assets = $this->client->assets()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        return [
            'labels' => $assets->pluck('type')->map(fn ($t) => $t ?? 'Unknown')->toArray(),
            'data' => $assets->pluck('count')->toArray(),
        ];
    }

    #[Computed]
    public function assetsByStatus()
    {
        if (! $this->client) {
            return null;
        }

        $assets = $this->client->assets()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return [
            'labels' => $assets->pluck('status')->map(fn ($s) => ucwords($s ?? 'Unknown'))->toArray(),
            'data' => $assets->pluck('count')->toArray(),
        ];
    }

    #[Computed]
    public function assetList()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->assets()
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    // ============================================
    // PROJECT REPORTS
    // ============================================

    #[Computed]
    public function projectStats()
    {
        if (! $this->client) {
            return null;
        }

        $projects = $this->client->projects();
        $periodProjects = $this->client->projects()->whereBetween('created_at', [$this->startDate, $this->endDate]);

        $activeProjects = $projects->whereNull('completed_at')->get();
        $onSchedule = $activeProjects->filter(fn ($p) => $p->due && Carbon::parse($p->due)->isFuture())->count();

        return [
            'active_projects' => $activeProjects->count(),
            'completed_projects' => $periodProjects->whereNotNull('completed_at')->count(),
            'on_schedule_percent' => $activeProjects->count() > 0 ? round(($onSchedule / $activeProjects->count()) * 100) : 0,
            'budget_utilization' => $this->calculateBudgetUtilization(),
        ];
    }

    #[Computed]
    public function projectsByStatus()
    {
        if (! $this->client) {
            return null;
        }

        $projects = $this->client->projects()->get();

        $statusCounts = [
            'Active' => $projects->where('status', 'active')->count(),
            'Completed' => $projects->where('status', 'completed')->count(),
            'On Hold' => $projects->where('status', 'on_hold')->count(),
            'Planning' => $projects->where('status', 'planning')->count(),
        ];

        return [
            'labels' => array_keys($statusCounts),
            'data' => array_values($statusCounts),
        ];
    }

    #[Computed]
    public function projectProgress()
    {
        if (! $this->client) {
            return null;
        }

        $projects = $this->client->projects()
            ->whereNull('completed_at')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return [
            'labels' => $projects->pluck('name')->toArray(),
            'data' => $projects->pluck('progress_percentage')->toArray(),
        ];
    }

    #[Computed]
    public function projectList()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->projects()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function calculateBudgetUtilization()
    {
        $projects = $this->client->projects()->whereNull('completed_at')->get();

        if ($projects->isEmpty()) {
            return 0;
        }

        $totalBudget = $projects->sum('budget');
        $totalSpent = $projects->sum('actual_cost');

        if ($totalBudget == 0) {
            return 0;
        }

        return round(($totalSpent / $totalBudget) * 100);
    }

    // ============================================
    // CONTRACT REPORTS
    // ============================================

    #[Computed]
    public function contractStats()
    {
        if (! $this->client) {
            return null;
        }

        $contracts = $this->client->contracts();

        return [
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'total_value' => $contracts->where('status', 'active')->sum('contract_value'),
            'expiring_soon' => $contracts->where('end_date', '<=', now()->addDays(90))->where('end_date', '>', now())->count(),
            'pending_signatures' => $contracts->where('signature_status', 'pending')->count(),
        ];
    }

    #[Computed]
    public function contractsByStatus()
    {
        if (! $this->client) {
            return null;
        }

        $contracts = $this->client->contracts()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return [
            'labels' => $contracts->pluck('status')->map(fn ($s) => ucwords($s ?? 'Unknown'))->toArray(),
            'data' => $contracts->pluck('count')->toArray(),
        ];
    }

    #[Computed]
    public function renewalPipeline()
    {
        if (! $this->client) {
            return null;
        }

        $contracts = $this->client->contracts()
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addMonths(12))
            ->get();

        $pipeline = [];
        for ($i = 0; $i < 12; $i++) {
            $month = now()->addMonths($i);
            $monthKey = $month->format('M Y');
            $count = $contracts->filter(function ($contract) use ($month) {
                return Carbon::parse($contract->end_date)->isSameMonth($month);
            })->count();
            $pipeline[$monthKey] = $count;
        }

        return [
            'labels' => array_keys($pipeline),
            'data' => array_values($pipeline),
        ];
    }

    #[Computed]
    public function contractList()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->contracts()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    // ============================================
    // QUOTE REPORTS
    // ============================================

    #[Computed]
    public function quoteStats()
    {
        if (! $this->client) {
            return null;
        }

        $quotes = $this->client->quotes();
        $periodQuotes = $this->client->quotes()->whereBetween('date', [$this->startDate, $this->endDate]);

        $totalSent = $periodQuotes->whereIn('status', ['Sent', 'Viewed', 'Accepted', 'Declined'])->count();
        $accepted = $periodQuotes->where('status', 'Accepted')->count();

        return [
            'total_quotes' => $periodQuotes->count(),
            'pending_quotes' => $quotes->whereIn('status', ['Sent', 'Viewed'])->count(),
            'acceptance_rate' => $totalSent > 0 ? round(($accepted / $totalSent) * 100) : 0,
            'total_value' => $periodQuotes->where('status', 'Accepted')->sum('amount'),
        ];
    }

    #[Computed]
    public function quotesByStatus()
    {
        if (! $this->client) {
            return null;
        }

        $quotes = $this->client->quotes()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return [
            'labels' => $quotes->pluck('status')->toArray(),
            'data' => $quotes->pluck('count')->toArray(),
        ];
    }

    #[Computed]
    public function quoteTrends()
    {
        if (! $this->client) {
            return null;
        }

        $sentQuotes = $this->client->quotes()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $acceptedQuotes = $this->client->quotes()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', 'Accepted')
            ->selectRaw('EXTRACT(YEAR FROM date) as year, EXTRACT(MONTH FROM date) as month, COUNT(*) as count')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $months = [];
        $sent = [];
        $accepted = [];

        $period = Carbon::parse($this->startDate);
        while ($period <= $this->endDate) {
            $months[] = $period->format('M Y');
            $key = $period->format('Y-m');
            $sent[] = $sentQuotes->get($key)?->count ?? 0;
            $accepted[] = $acceptedQuotes->get($key)?->count ?? 0;
            $period->addMonth();
        }

        return [
            'labels' => $months,
            'sent' => $sent,
            'accepted' => $accepted,
        ];
    }

    #[Computed]
    public function quoteList()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->quotes()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.portal.reports')->layout('client-portal.layouts.app');
    }
}
