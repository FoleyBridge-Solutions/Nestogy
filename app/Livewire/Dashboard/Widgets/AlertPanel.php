<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\Ticket;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AlertPanel extends Component
{
    public Collection $alerts;

    public bool $loading = true;

    public string $filter = 'all'; // all, critical, high, medium

    public int $limit = 10;

    public function mount()
    {
        $this->alerts = collect();
        $this->loadAlerts();
    }

    #[On('refresh-alert-panel')]
    public function loadAlerts()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        $alerts = collect()
            ->merge($this->getCriticalTicketAlerts($companyId))
            ->merge($this->getOverdueInvoiceAlerts($companyId))
            ->merge($this->getInactiveClientAlerts($companyId))
            ->merge($this->getAssetMonitoringAlerts($companyId))
            ->merge($this->getSystemAlerts($companyId));

        $alerts = $this->applyFilter($alerts);
        $this->alerts = $this->sortAndLimit($alerts);
        $this->loading = false;
    }

    protected function getCriticalTicketAlerts(int $companyId): Collection
    {
        $criticalTickets = Ticket::where('company_id', $companyId)
            ->where('priority', 'Critical')
            ->whereIn('status', ['Open', 'In Progress'])
            ->with('client')
            ->get();

        return $criticalTickets->map(fn ($ticket) => [
            'id' => 'ticket_'.$ticket->id,
            'type' => 'ticket',
            'severity' => 'critical',
            'title' => 'Critical Ticket',
            'message' => "Ticket #{$ticket->id}: {$ticket->subject}",
            'details' => "Client: {$ticket->client->name}",
            'created_at' => $ticket->created_at,
            'action_url' => $this->getSafeRoute('tickets.show', $ticket->id),
            'action_text' => 'View Ticket',
            'icon' => 'exclamation-triangle',
            'dismissible' => false,
        ]);
    }

    protected function getOverdueInvoiceAlerts(int $companyId): Collection
    {
        $overdueInvoices = Invoice::where('company_id', $companyId)
            ->where('status', 'Sent')
            ->where('due_date', '<', now())
            ->with('client')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return $overdueInvoices->map(function ($invoice) {
            $daysOverdue = (int) Carbon::parse($invoice->due_date)->diffInDays(now(), false);
            $severity = $daysOverdue > 30 ? 'critical' : ($daysOverdue > 14 ? 'high' : 'medium');
            
            $invoiceNumber = $invoice->prefix ? "{$invoice->prefix}{$invoice->number}" : $invoice->number;
            $formattedAmount = number_format($invoice->amount, 2);

            return [
                'id' => 'invoice_'.$invoice->id,
                'type' => 'financial',
                'severity' => $severity,
                'title' => 'Overdue Invoice',
                'message' => "Invoice #{$invoiceNumber} is {$daysOverdue} days overdue",
                'details' => "Client: {$invoice->client->name} - Amount: \${$formattedAmount}",
                'created_at' => $invoice->due_date,
                'action_url' => $this->getSafeRoute('financial.invoices.show', $invoice->id),
                'action_text' => 'View Invoice',
                'icon' => 'currency-dollar',
                'dismissible' => true,
            ];
        });
    }

    protected function getInactiveClientAlerts(int $companyId): Collection
    {
        $inactiveClients = Client::where('company_id', $companyId)
            ->where('status', true)
            ->whereDoesntHave('tickets', function ($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })
            ->whereDoesntHave('payments', function ($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })
            ->limit(3)
            ->get();

        return $inactiveClients->map(fn ($client) => [
            'id' => 'client_inactive_'.$client->id,
            'type' => 'client',
            'severity' => 'medium',
            'title' => 'Inactive Client',
            'message' => "No activity from {$client->name} in 90+ days",
            'details' => 'Consider reaching out to maintain engagement',
            'created_at' => now()->subDays(90),
            'action_url' => $this->getSafeRoute('clients.show', $client->id),
            'action_text' => 'View Client',
            'icon' => 'user-minus',
            'dismissible' => true,
        ]);
    }

    protected function getAssetMonitoringAlerts(int $companyId): Collection
    {
        try {
            if (! class_exists('\App\Models\Asset')) {
                return collect();
            }

            $failedAssets = \App\Models\Asset::where('company_id', $companyId)
                ->where(function ($query) {
                    $query->where('status', 'offline')
                        ->orWhere('last_check_at', '<', now()->subHours(24))
                        ->orWhereNull('last_check_at');
                })
                ->limit(5)
                ->get();

            if ($failedAssets->isEmpty()) {
                return collect();
            }

            return collect([[
                'id' => 'asset_monitoring_issues',
                'type' => 'asset',
                'severity' => $failedAssets->where('status', 'offline')->count() > 0 ? 'high' : 'medium',
                'title' => 'Asset Monitoring Alert',
                'message' => $failedAssets->count().' asset(s) require attention',
                'details' => 'Some assets are offline or haven\'t been checked recently',
                'created_at' => now(),
                'action_url' => $this->getSafeRoute('assets.index'),
                'action_text' => 'View Assets',
                'icon' => 'server',
                'dismissible' => true,
            ]]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    protected function getSystemAlerts(int $companyId): Collection
    {
        $alerts = collect();

        try {
            $alerts = $alerts->merge($this->getBackupAlerts($companyId));
            $alerts = $alerts->merge($this->getDiskSpaceAlerts());
        } catch (\Exception $e) {
        }

        return $alerts;
    }

    protected function getBackupAlerts(int $companyId): Collection
    {
        if (! \Schema::hasTable('backup_logs')) {
            return collect();
        }

        $lastBackup = \DB::table('backup_logs')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastBackup && Carbon::parse($lastBackup->created_at)->gte(now()->subDay())) {
            return collect();
        }

        return collect([[
            'id' => 'backup_overdue',
            'type' => 'system',
            'severity' => 'medium',
            'title' => 'Backup Overdue',
            'message' => 'No backup completed in the last 24 hours',
            'details' => $lastBackup ? 'Last backup: '.Carbon::parse($lastBackup->created_at)->diffForHumans() : 'No backup records found',
            'created_at' => now(),
            'action_url' => $this->getSafeRoute('system.backups'),
            'action_text' => 'Check Backups',
            'icon' => 'shield-exclamation',
            'dismissible' => true,
        ]]);
    }

    protected function getDiskSpaceAlerts(): Collection
    {
        if (! \Schema::hasTable('system_metrics')) {
            return collect();
        }

        $diskUsage = \DB::table('system_metrics')
            ->where('metric_type', 'disk_usage')
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $diskUsage || $diskUsage->value <= 90) {
            return collect();
        }

        return collect([[
            'id' => 'disk_space_warning',
            'type' => 'system',
            'severity' => $diskUsage->value > 95 ? 'critical' : 'high',
            'title' => 'Low Disk Space',
            'message' => 'Server disk usage at '.round($diskUsage->value).'%',
            'details' => 'Consider cleaning up old files or expanding storage',
            'created_at' => Carbon::parse($diskUsage->created_at),
            'action_url' => $this->getSafeRoute('system.metrics'),
            'action_text' => 'View Metrics',
            'icon' => 'database',
            'dismissible' => false,
        ]]);
    }

    protected function applyFilter(Collection $alerts): Collection
    {
        if ($this->filter === 'all') {
            return $alerts;
        }

        return $alerts->filter(fn ($alert) => $alert['severity'] === $this->filter);
    }

    protected function sortAndLimit(Collection $alerts): Collection
    {
        $severityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];

        return $alerts->sortBy([
            fn ($alert) => $severityOrder[$alert['severity']] ?? 99,
            fn ($alert) => $alert['created_at']->timestamp * -1,
        ])->take($this->limit);
    }

    public function setFilter($filter)
    {
        if (in_array($filter, ['all', 'critical', 'high', 'medium', 'low'])) {
            $this->filter = $filter;
            $this->loadAlerts();
        }
    }

    public function dismissAlert($alertId)
    {
        $this->alerts = $this->alerts->reject(function ($alert) use ($alertId) {
            return $alert['id'] === $alertId;
        });

        // Here you would typically store the dismissal in the database
        session()->put("dismissed_alert_{$alertId}", true);
    }

    public function loadMore()
    {
        $this->limit += 5;
        $this->loadAlerts();
    }

    protected function getSafeRoute($name, $parameters = [])
    {
        try {
            return route($name, $parameters);
        } catch (\Exception $e) {
            return '#';
        }
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.alert-panel');
    }
}
