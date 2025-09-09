<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;

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
        $alerts = collect();
        
        // Critical tickets
        $criticalTickets = Ticket::where('company_id', $companyId)
            ->where('priority', 'Critical')
            ->whereIn('status', ['Open', 'In Progress'])
            ->with('client')
            ->get();
            
        foreach ($criticalTickets as $ticket) {
            $alerts->push([
                'id' => 'ticket_' . $ticket->id,
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
        
        // Overdue invoices
        $overdueInvoices = Invoice::where('company_id', $companyId)
            ->where('status', 'Sent')
            ->where('due_date', '<', now())
            ->with('client')
            ->orderBy('due_date')
            ->limit(5)
            ->get();
            
        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = now()->diffInDays($invoice->due_date);
            $severity = $daysOverdue > 30 ? 'critical' : ($daysOverdue > 14 ? 'high' : 'medium');
            
            $alerts->push([
                'id' => 'invoice_' . $invoice->id,
                'type' => 'financial',
                'severity' => $severity,
                'title' => 'Overdue Invoice',
                'message' => "Invoice #{$invoice->invoice_number} is {$daysOverdue} days overdue",
                'details' => "Client: {$invoice->client->name} - Amount: \${$invoice->amount}",
                'created_at' => $invoice->due_date,
                'action_url' => $this->getSafeRoute('invoices.show', $invoice->id),
                'action_text' => 'View Invoice',
                'icon' => 'currency-dollar',
                'dismissible' => true,
            ]);
        }
        
        // Inactive clients
        $inactiveClients = Client::where('company_id', $companyId)
            ->where('status', true)
            ->whereDoesntHave('tickets', function($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })
            ->whereDoesntHave('payments', function($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })
            ->limit(3)
            ->get();
            
        foreach ($inactiveClients as $client) {
            $alerts->push([
                'id' => 'client_inactive_' . $client->id,
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
        
        // Check for assets with monitoring issues (only if Asset model has monitoring fields)
        try {
            if (class_exists('\App\Models\Asset')) {
                // Look for assets that haven't been checked recently or have issues
                $failedAssets = \App\Models\Asset::where('company_id', $companyId)
                    ->where(function($query) {
                        $query->where('status', 'offline')
                            ->orWhere('last_check_at', '<', now()->subHours(24))
                            ->orWhereNull('last_check_at');
                    })
                    ->limit(5)
                    ->get();
                
                if ($failedAssets->count() > 0) {
                    $alerts->push([
                        'id' => 'asset_monitoring_issues',
                        'type' => 'asset',
                        'severity' => $failedAssets->where('status', 'offline')->count() > 0 ? 'high' : 'medium',
                        'title' => 'Asset Monitoring Alert',
                        'message' => $failedAssets->count() . ' asset(s) require attention',
                        'details' => 'Some assets are offline or haven\'t been checked recently',
                        'created_at' => now(),
                        'action_url' => $this->getSafeRoute('assets.index'),
                        'action_text' => 'View Assets',
                        'icon' => 'server',
                        'dismissible' => true,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Asset monitoring not available or error occurred
        }
        
        // Check for actual system issues from logs or monitoring
        try {
            // Check if backup verification is needed (if we have a backup logs table)
            if (\Schema::hasTable('backup_logs')) {
                $lastBackup = \DB::table('backup_logs')
                    ->where('company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                if (!$lastBackup || Carbon::parse($lastBackup->created_at)->lt(now()->subDay())) {
                    $alerts->push([
                        'id' => 'backup_overdue',
                        'type' => 'system',
                        'severity' => 'medium',
                        'title' => 'Backup Overdue',
                        'message' => 'No backup completed in the last 24 hours',
                        'details' => $lastBackup ? 'Last backup: ' . Carbon::parse($lastBackup->created_at)->diffForHumans() : 'No backup records found',
                        'created_at' => now(),
                        'action_url' => $this->getSafeRoute('system.backups'),
                        'action_text' => 'Check Backups',
                        'icon' => 'shield-exclamation',
                        'dismissible' => true,
                    ]);
                }
            }
            
            // Check for disk space issues (if system monitoring is available)
            if (\Schema::hasTable('system_metrics')) {
                $diskUsage = \DB::table('system_metrics')
                    ->where('metric_type', 'disk_usage')
                    ->where('created_at', '>=', now()->subHour())
                    ->orderBy('created_at', 'desc')
                    ->first();
                    
                if ($diskUsage && $diskUsage->value > 90) {
                    $alerts->push([
                        'id' => 'disk_space_warning',
                        'type' => 'system',
                        'severity' => $diskUsage->value > 95 ? 'critical' : 'high',
                        'title' => 'Low Disk Space',
                        'message' => 'Server disk usage at ' . round($diskUsage->value) . '%',
                        'details' => 'Consider cleaning up old files or expanding storage',
                        'created_at' => Carbon::parse($diskUsage->created_at),
                        'action_url' => $this->getSafeRoute('system.metrics'),
                        'action_text' => 'View Metrics',
                        'icon' => 'database',
                        'dismissible' => false,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // System monitoring not available or error occurred
        }
        
        // Filter by severity
        if ($this->filter !== 'all') {
            $alerts = $alerts->filter(function ($alert) {
                return $alert['severity'] === $this->filter;
            });
        }
        
        // Sort by severity and date
        $severityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        
        $this->alerts = $alerts->sortBy([
            fn($alert) => $severityOrder[$alert['severity']] ?? 99,
            fn($alert) => $alert['created_at']->timestamp * -1, // Newest first
        ])->take($this->limit);
        
        $this->loading = false;
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