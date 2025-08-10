<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Invoice;
use App\Models\Asset;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientDashboardService
{
    /**
     * Get comprehensive dashboard data for a client
     */
    public function getDashboardData(Client $client): array
    {
        return [
            'overview' => $this->getClientOverview($client),
            'tickets' => $this->getTicketStats($client),
            'financial' => $this->getFinancialStats($client),
            'assets' => $this->getAssetStats($client),
            'projects' => $this->getProjectStats($client),
            'recent_activity' => $this->getRecentActivity($client),
            'upcoming_events' => $this->getUpcomingEvents($client),
            'expiring_items' => $this->getExpiringItems($client),
        ];
    }

    /**
     * Get client overview statistics
     */
    protected function getClientOverview(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'status' => $client->status ?? 'active',
            'created_at' => $client->created_at,
            'primary_contact' => $client->contacts()->where('primary', true)->first(),
            'total_contacts' => $client->contacts()->count(),
            'total_locations' => $client->locations()->count(),
            'primary_location' => $client->locations()->where('primary', true)->first(),
            'account_manager' => $client->accountManager ?? null,
            'tags' => $client->tags ?? [],
        ];
    }

    /**
     * Get ticket statistics for the client
     */
    protected function getTicketStats(Client $client): array
    {
        $tickets = $client->tickets();
        
        return [
            'total' => $tickets->count(),
            'open' => $tickets->whereIn('status', ['open', 'in_progress'])->count(),
            'closed' => $tickets->where('status', 'closed')->count(),
            'pending' => $tickets->where('status', 'pending')->count(),
            'this_month' => $tickets->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'average_resolution_time' => $this->calculateAverageResolutionTime($client),
            'sla_compliance' => $this->calculateSLACompliance($client),
            'recent_tickets' => $tickets->latest()->limit(5)->get(),
        ];
    }

    /**
     * Get financial statistics for the client
     */
    protected function getFinancialStats(Client $client): array
    {
        $invoices = $client->invoices();
        $currentYear = Carbon::now()->year;
        
        return [
            'total_revenue' => $invoices->sum('total'),
            'outstanding_balance' => $invoices->where('status', 'unpaid')->sum('balance'),
            'revenue_this_year' => $invoices->whereYear('created_at', $currentYear)->sum('total'),
            'revenue_this_month' => $invoices->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->sum('total'),
            'total_invoices' => $invoices->count(),
            'unpaid_invoices' => $invoices->where('status', 'unpaid')->count(),
            'overdue_invoices' => $invoices->where('status', 'overdue')->count(),
            'mrr' => $this->calculateMRR($client),
            'arr' => $this->calculateARR($client),
            'payment_history' => $client->payments()->latest()->limit(5)->get(),
            'upcoming_renewals' => $this->getUpcomingRenewals($client),
        ];
    }

    /**
     * Get asset statistics for the client
     */
    protected function getAssetStats(Client $client): array
    {
        $assets = $client->assets();
        
        return [
            'total_assets' => $assets->count(),
            'active_assets' => $assets->where('status', 'active')->count(),
            'inactive_assets' => $assets->where('status', 'inactive')->count(),
            'total_value' => $assets->sum('purchase_price'),
            'depreciated_value' => $this->calculateDepreciatedValue($client),
            'warranties_expiring' => $assets->whereHas('warranty', function ($q) {
                $q->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(90)]);
            })->count(),
            'maintenance_due' => $assets->whereHas('maintenances', function ($q) {
                $q->where('next_maintenance_date', '<=', Carbon::now()->addDays(30));
            })->count(),
            'by_category' => $assets->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category'),
            'recent_assets' => $assets->latest()->limit(5)->get(),
        ];
    }

    /**
     * Get project statistics for the client
     */
    protected function getProjectStats(Client $client): array
    {
        $projects = $client->projects();
        
        return [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'active')->count(),
            'completed_projects' => $projects->where('status', 'completed')->count(),
            'on_hold_projects' => $projects->where('status', 'on_hold')->count(),
            'total_project_value' => $projects->sum('budget'),
            'completion_rate' => $this->calculateProjectCompletionRate($client),
            'overdue_projects' => $projects->where('end_date', '<', Carbon::now())
                ->where('status', '!=', 'completed')->count(),
            'recent_projects' => $projects->latest()->limit(5)->get(),
        ];
    }

    /**
     * Get recent activity for the client
     */
    protected function getRecentActivity(Client $client): Collection
    {
        $activities = collect();

        // Recent tickets
        $recentTickets = $client->tickets()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'type' => 'ticket',
                    'description' => "Ticket #{$ticket->id}: {$ticket->subject}",
                    'date' => $ticket->created_at,
                    'status' => $ticket->status,
                    'url' => route('tickets.show', $ticket),
                ];
            });

        // Recent invoices
        $recentInvoices = $client->invoices()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'type' => 'invoice',
                    'description' => "Invoice #{$invoice->number}",
                    'date' => $invoice->created_at,
                    'status' => $invoice->status,
                    'amount' => $invoice->total,
                    'url' => route('invoices.show', $invoice),
                ];
            });

        // Recent payments
        $recentPayments = $client->payments()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment',
                    'description' => "Payment received",
                    'date' => $payment->created_at,
                    'amount' => $payment->amount,
                    'url' => route('payments.show', $payment),
                ];
            });

        return $activities
            ->merge($recentTickets)
            ->merge($recentInvoices)
            ->merge($recentPayments)
            ->sortByDesc('date')
            ->take(10);
    }

    /**
     * Get upcoming events for the client
     */
    protected function getUpcomingEvents(Client $client): Collection
    {
        $events = collect();

        // Calendar events
        if ($client->calendarEvents) {
            $calendarEvents = $client->calendarEvents()
                ->where('start_date', '>=', Carbon::now())
                ->orderBy('start_date')
                ->limit(5)
                ->get();
            $events = $events->merge($calendarEvents);
        }

        // Project milestones
        $projectMilestones = $client->projects()
            ->join('project_milestones', 'projects.id', '=', 'project_milestones.project_id')
            ->where('project_milestones.due_date', '>=', Carbon::now())
            ->orderBy('project_milestones.due_date')
            ->limit(5)
            ->select('project_milestones.*', 'projects.name as project_name')
            ->get();
        
        $events = $events->merge($projectMilestones);

        return $events->sortBy('start_date')->take(10);
    }

    /**
     * Get expiring items for the client
     */
    protected function getExpiringItems(Client $client): array
    {
        $expiringItems = [];

        // Expiring licenses
        if ($client->licenses) {
            $expiringItems['licenses'] = $client->licenses()
                ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(90)])
                ->orderBy('expiry_date')
                ->get();
        }

        // Expiring certificates
        if ($client->certificates) {
            $expiringItems['certificates'] = $client->certificates()
                ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(90)])
                ->orderBy('expiry_date')
                ->get();
        }

        // Expiring domains
        if ($client->domains) {
            $expiringItems['domains'] = $client->domains()
                ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(90)])
                ->orderBy('expiry_date')
                ->get();
        }

        // Expiring warranties
        $expiringItems['warranties'] = $client->assets()
            ->join('asset_warranties', 'assets.id', '=', 'asset_warranties.asset_id')
            ->whereBetween('asset_warranties.expiry_date', [Carbon::now(), Carbon::now()->addDays(90)])
            ->orderBy('asset_warranties.expiry_date')
            ->select('assets.*', 'asset_warranties.expiry_date as warranty_expiry')
            ->get();

        return $expiringItems;
    }

    /**
     * Calculate average ticket resolution time
     */
    protected function calculateAverageResolutionTime(Client $client): ?float
    {
        $resolvedTickets = $client->tickets()
            ->where('status', 'closed')
            ->whereNotNull('resolved_at')
            ->get();

        if ($resolvedTickets->isEmpty()) {
            return null;
        }

        $totalHours = $resolvedTickets->sum(function ($ticket) {
            return $ticket->created_at->diffInHours($ticket->resolved_at);
        });

        return round($totalHours / $resolvedTickets->count(), 2);
    }

    /**
     * Calculate SLA compliance percentage
     */
    protected function calculateSLACompliance(Client $client): float
    {
        $ticketsWithSLA = $client->tickets()
            ->whereNotNull('sla_response_due')
            ->where('status', 'closed')
            ->get();

        if ($ticketsWithSLA->isEmpty()) {
            return 100.0;
        }

        $compliantTickets = $ticketsWithSLA->filter(function ($ticket) {
            return $ticket->first_response_at <= $ticket->sla_response_due;
        })->count();

        return round(($compliantTickets / $ticketsWithSLA->count()) * 100, 2);
    }

    /**
     * Calculate Monthly Recurring Revenue
     */
    protected function calculateMRR(Client $client): float
    {
        return $client->recurringInvoices()
            ->where('status', 'active')
            ->where('frequency', 'monthly')
            ->sum('total_amount');
    }

    /**
     * Calculate Annual Recurring Revenue
     */
    protected function calculateARR(Client $client): float
    {
        $mrr = $this->calculateMRR($client);
        $yearlyRecurring = $client->recurringInvoices()
            ->where('status', 'active')
            ->where('frequency', 'yearly')
            ->sum('total_amount');

        return ($mrr * 12) + $yearlyRecurring;
    }

    /**
     * Get upcoming renewals
     */
    protected function getUpcomingRenewals(Client $client): Collection
    {
        return $client->recurringInvoices()
            ->where('status', 'active')
            ->whereBetween('next_invoice_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->orderBy('next_invoice_date')
            ->get();
    }

    /**
     * Calculate total depreciated value of assets
     */
    protected function calculateDepreciatedValue(Client $client): float
    {
        return $client->assets()
            ->join('asset_depreciations', 'assets.id', '=', 'asset_depreciations.asset_id')
            ->sum('asset_depreciations.current_value');
    }

    /**
     * Calculate project completion rate
     */
    protected function calculateProjectCompletionRate(Client $client): float
    {
        $totalProjects = $client->projects()->count();
        
        if ($totalProjects === 0) {
            return 0.0;
        }

        $completedProjects = $client->projects()
            ->where('status', 'completed')
            ->count();

        return round(($completedProjects / $totalProjects) * 100, 2);
    }
}