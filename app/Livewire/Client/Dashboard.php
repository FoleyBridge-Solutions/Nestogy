<?php

namespace App\Livewire\Client;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractMilestone;
use App\Domains\Contract\Models\ContractSignature;
use App\Models\Contact;
use Livewire\Component;

class Dashboard extends Component
{
    public Contact $contact;
    public $client;
    public $roles;
    public $permissions;

    public function mount()
    {
        $this->contact = auth('client')->user();
        $this->client = $this->contact->client;
        $this->roles = $this->contact->getRoles();
        $this->permissions = $this->contact->portal_permissions ?? [];
    }

    public function render()
    {
        $data = [
            'contractStats' => $this->canViewContracts() ? $this->getContractStats() : null,
            'contracts' => $this->canViewContracts() ? $this->getContracts() : null,
            'invoiceStats' => $this->canViewInvoices() ? $this->getInvoiceStats() : null,
            'invoices' => $this->canViewInvoices() ? $this->getInvoices() : null,
            'ticketStats' => $this->canViewTickets() ? $this->getTicketStats() : null,
            'tickets' => $this->canViewTickets() ? $this->getTickets() : null,
            'assetStats' => $this->canViewAssets() ? $this->getAssetStats() : null,
            'assets' => $this->canViewAssets() ? $this->getAssets() : null,
            'projectStats' => $this->canViewProjects() ? $this->getProjectStats() : null,
            'projects' => $this->canViewProjects() ? $this->getProjects() : null,
            'recentActivity' => $this->getRecentActivity(),
            'upcomingMilestones' => $this->getUpcomingMilestones(),
            'pendingActions' => $this->getPendingActions(),
            'notifications' => $this->getNotifications(),
            'recentTickets' => $this->canViewTickets() ? $this->getRecentTickets() : null,
            'paymentHistory' => $this->canViewInvoices() ? $this->getPaymentHistory() : null,
            'ticketTrends' => $this->canViewTickets() ? $this->getTicketTrends() : null,
            'spendingTrends' => $this->canViewInvoices() ? $this->getSpendingTrends() : null,
            'serviceStatus' => $this->getServiceStatus(),
            'recentDocuments' => $this->getRecentDocuments(),
            'activeProjects' => $this->canViewProjects() ? $this->getActiveProjects() : null,
            'knowledgeBaseArticles' => $this->getKnowledgeBaseArticles(),
            'assetHealth' => $this->canViewAssets() ? $this->getAssetHealth() : null,
        ];

        return view('livewire.client.dashboard', $data);
    }

    protected function canViewContracts(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isBilling() ||
               in_array('can_view_contracts', $this->contact->portal_permissions ?? []);
    }

    protected function canViewInvoices(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isBilling() ||
               in_array('can_view_invoices', $this->contact->portal_permissions ?? []);
    }

    protected function canViewTickets(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isTechnical() ||
               in_array('can_view_tickets', $this->contact->portal_permissions ?? []);
    }

    protected function canViewAssets(): bool
    {
        return $this->contact->isPrimary() ||
               $this->contact->isTechnical() ||
               in_array('can_view_assets', $this->contact->portal_permissions ?? []);
    }

    protected function canViewProjects(): bool
    {
        return $this->contact->isPrimary() ||
               in_array('can_view_projects', $this->contact->portal_permissions ?? []);
    }

    protected function getContracts()
    {
        if (! $this->client) {
            return collect();
        }

        return Contract::where('client_id', $this->client->id)
            ->with(['contractMilestones', 'signatures', 'invoices'])
            ->get();
    }

    protected function getContractStats(): array
    {
        $contracts = $this->getContracts();

        return [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'pending_signatures' => $contracts->flatMap->signatures->where('status', 'pending')->count(),
            'overdue_milestones' => $contracts->flatMap->contractMilestones
                ->where('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
            'total_contract_value' => $contracts->sum('contract_value'),
        ];
    }

    protected function getInvoices()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->invoices()
            ->with(['payments'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getInvoiceStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $invoices = $this->client->invoices();

        return [
            'total_invoices' => $invoices->count(),
            'outstanding_amount' => $invoices->where('status', 'sent')->sum('amount'),
            'overdue_count' => $invoices->where('status', 'sent')->where('due_date', '<', now())->count(),
            'paid_this_month' => $invoices->where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->sum('amount'),
        ];
    }

    protected function getTickets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->tickets()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getTicketStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $tickets = $this->client->tickets();

        return [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->whereIn('status', ['Open', 'In Progress', 'Waiting', 'On Hold'])->count(),
            'resolved_this_month' => $tickets->whereIn('status', ['Resolved', 'Closed'])
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'avg_response_time' => '< 1h',
        ];
    }

    protected function getAssets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->assets()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getAssetStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $assets = $this->client->assets();

        return [
            'total_assets' => $assets->count(),
            'active_assets' => $assets->where('status', 'active')->count(),
            'maintenance_due' => $assets->where('status', 'active')->where('next_maintenance_date', '<=', now()->addDays(30))->count(),
            'warranty_expiring' => $assets->where('status', 'active')->where('warranty_expire', '<=', now()->addDays(60))->count(),
        ];
    }

    protected function getProjects()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->projects()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getProjectStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $projects = $this->client->projects();

        return [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->whereNull('completed_at')->count(),
            'completed_projects' => $projects->whereNotNull('completed_at')->count(),
            'projects_on_time' => $projects->whereNull('completed_at')
                ->where('due', '>', now())
                ->count(),
        ];
    }

    protected function getRecentActivity(): array
    {
        if (! $this->client) {
            return [];
        }

        $activities = [];

        if ($this->canViewContracts()) {
            $contracts = Contract::where('client_id', $this->client->id)->get();
            foreach ($contracts as $contract) {
                $activities = array_merge($activities, $contract->getAuditHistory());
            }
        }

        usort($activities, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($activities, 0, 10);
    }

    protected function getUpcomingMilestones(): array
    {
        if (! $this->canViewContracts() || ! $this->client) {
            return [];
        }

        return ContractMilestone::whereHas('contract', function ($query) {
            $query->where('client_id', $this->client->id);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getPendingActions(): array
    {
        $actions = [];

        if (! $this->client) {
            return $actions;
        }

        if ($this->canViewContracts()) {
            $pendingSignatures = ContractSignature::whereHas('contract', function ($query) {
                $query->where('client_id', $this->client->id);
            })
                ->where('status', 'pending')
                ->count();

            if ($pendingSignatures > 0) {
                $actions[] = [
                    'type' => 'signature',
                    'count' => $pendingSignatures,
                    'message' => "You have {$pendingSignatures} contract(s) pending signature.",
                    'action_url' => route('client.contracts'),
                ];
            }
        }

        if ($this->canViewInvoices()) {
            $overdueInvoices = $this->client->invoices()
                ->where('status', 'sent')
                ->where('due_date', '<', now())
                ->count();

            if ($overdueInvoices > 0) {
                $actions[] = [
                    'type' => 'invoice',
                    'count' => $overdueInvoices,
                    'message' => "You have {$overdueInvoices} overdue invoice(s).",
                    'action_url' => route('client.invoices'),
                ];
            }
        }

        return $actions;
    }

    protected function getNotifications()
    {
        if (! $this->client) {
            return collect();
        }

        return \App\Models\PortalNotification::where('client_id', $this->client->id)
            ->where('show_in_portal', true)
            ->where('is_dismissed', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('priority', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getRecentTickets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->tickets()
            ->with(['assignedTo', 'contact'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    protected function getPaymentHistory()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->invoices()
            ->with('payments')
            ->whereHas('payments')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($invoice) {
                return [
                    'date' => $invoice->payments->first()->created_at ?? $invoice->created_at,
                    'amount' => $invoice->payments->sum('amount'),
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'method' => $invoice->payments->first()->payment_method ?? 'Unknown',
                ];
            });
    }

    protected function getTicketTrends()
    {
        if (! $this->client) {
            return [];
        }

        $months = [];
        $open = [];
        $closed = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M');

            $open[] = $this->client->tickets()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->whereIn('status', ['Open', 'In Progress', 'Waiting', 'On Hold'])
                ->count();

            $closed[] = $this->client->tickets()
                ->whereYear('updated_at', $month->year)
                ->whereMonth('updated_at', $month->month)
                ->whereIn('status', ['Resolved', 'Closed'])
                ->count();
        }

        return [
            'labels' => $months,
            'open' => $open,
            'closed' => $closed,
        ];
    }

    protected function getSpendingTrends()
    {
        if (! $this->client) {
            return [];
        }

        $months = [];
        $amounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');

            $amounts[] = $this->client->invoices()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
        }

        return [
            'labels' => $months,
            'amounts' => $amounts,
        ];
    }

    protected function getServiceStatus()
    {
        if (! $this->client) {
            return [];
        }

        $contracts = $this->canViewContracts() ? Contract::where('client_id', $this->client->id)->where('status', 'active')->get() : collect();

        $services = [];
        foreach ($contracts as $contract) {
            $services[] = [
                'name' => $contract->name ?? 'Service Agreement',
                'status' => $contract->status === 'active' ? 'operational' : 'inactive',
                'health' => 100,
                'lastChecked' => now(),
            ];
        }

        return $services;
    }

    protected function getRecentDocuments()
    {
        if (! $this->client) {
            return collect();
        }

        $documents = collect();

        if ($this->canViewContracts()) {
            $contractDocs = Contract::where('client_id', $this->client->id)
                ->whereNotNull('document_path')
                ->orderBy('updated_at', 'desc')
                ->limit(3)
                ->get()
                ->map(fn ($contract) => [
                    'name' => $contract->name ?? 'Contract',
                    'type' => 'Contract',
                    'date' => $contract->updated_at,
                    'url' => route('client.contracts.download', $contract->id),
                    'icon' => 'document-text',
                ]);
            
            $documents = $documents->merge($contractDocs);
        }

        if ($this->canViewInvoices()) {
            $invoiceDocs = $this->client->invoices()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get()
                ->map(fn ($invoice) => [
                    'name' => 'Invoice #'.$invoice->invoice_number,
                    'type' => 'Invoice',
                    'date' => $invoice->created_at,
                    'url' => route('client.invoices.download', $invoice->id),
                    'icon' => 'banknotes',
                ]);
            
            $documents = $documents->merge($invoiceDocs);
        }

        return $documents->sortByDesc('date')->take(5);
    }

    protected function getActiveProjects()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->projects()
            ->whereNull('completed_at')
            ->with(['tasks' => function ($query) {
                $query->whereIn('status', ['pending', 'in_progress']);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($project) {
                $totalTasks = $project->tasks()->count();
                $completedTasks = $project->tasks()->where('status', 'completed')->count();
                $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status ?? 'active',
                    'progress' => round($progress, 0),
                    'due_date' => $project->due,
                    'tasks_remaining' => $totalTasks - $completedTasks,
                ];
            });
    }

    protected function getKnowledgeBaseArticles()
    {
        return collect([
            [
                'title' => 'Getting Started with Your Client Portal',
                'category' => 'Portal Guide',
                'views' => 1245,
                'helpful' => true,
            ],
            [
                'title' => 'How to Submit a Support Ticket',
                'category' => 'Support',
                'views' => 890,
                'helpful' => true,
            ],
            [
                'title' => 'Understanding Your Invoice',
                'category' => 'Billing',
                'views' => 654,
                'helpful' => true,
            ],
        ]);
    }

    protected function getAssetHealth()
    {
        if (! $this->client) {
            return [];
        }

        $assets = $this->client->assets()->where('status', 'active')->get();
        
        $criticalCount = $assets->filter(function ($asset) {
            return $asset->warranty_expire && $asset->warranty_expire->isPast();
        })->count();

        $warningCount = $assets->filter(function ($asset) {
            return $asset->warranty_expire && 
                   $asset->warranty_expire->isFuture() && 
                   $asset->warranty_expire->diffInDays(now()) <= 60;
        })->count();

        $healthyCount = $assets->count() - $criticalCount - $warningCount;

        $overallHealth = $assets->count() > 0 
            ? round((($healthyCount * 100) + ($warningCount * 50)) / $assets->count(), 0)
            : 100;

        return [
            'overall' => $overallHealth,
            'total' => $assets->count(),
            'healthy' => $healthyCount,
            'warning' => $warningCount,
            'critical' => $criticalCount,
            'categories' => $assets->groupBy('type')->map->count()->toArray(),
        ];
    }
}
