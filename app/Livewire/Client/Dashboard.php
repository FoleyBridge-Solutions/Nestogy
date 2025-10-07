<?php

namespace App\Livewire\Client;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSignature;
use App\Livewire\Client\Concerns\HasDashboardAssets;
use App\Livewire\Client\Concerns\HasDashboardContracts;
use App\Livewire\Client\Concerns\HasDashboardInvoices;
use App\Livewire\Client\Concerns\HasDashboardPermissions;
use App\Livewire\Client\Concerns\HasDashboardProjects;
use App\Livewire\Client\Concerns\HasDashboardTickets;
use App\Models\Contact;
use Livewire\Component;

class Dashboard extends Component
{
    use HasDashboardPermissions;
    use HasDashboardContracts;
    use HasDashboardInvoices;
    use HasDashboardTickets;
    use HasDashboardAssets;
    use HasDashboardProjects;

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
}
