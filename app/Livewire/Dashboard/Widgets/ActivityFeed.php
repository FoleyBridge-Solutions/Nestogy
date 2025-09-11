<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Lazy;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\User;
use App\Traits\LazyLoadable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

#[Lazy(isolate: false)]
class ActivityFeed extends Component
{
    use LazyLoadable;
    public Collection $activities;
    public bool $loading = true;
    public ?int $clientId = null;
    public string $filter = 'all'; // all, tickets, financial, clients, system
    public int $limit = 6;
    public bool $autoRefresh = true;
    
    public function mount(?int $clientId = null)
    {
        $this->clientId = $clientId;
        $this->activities = collect();
        $this->loadActivities();
    }
    
    #[On('refresh-activity-feed')]
    public function loadActivities()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        $activities = collect();
        
        // Load different types of activities based on filter
        if (in_array($this->filter, ['all', 'tickets'])) {
            $activities = $activities->merge($this->getTicketActivities($companyId));
        }
        
        if (in_array($this->filter, ['all', 'financial'])) {
            $activities = $activities->merge($this->getFinancialActivities($companyId));
        }
        
        if (in_array($this->filter, ['all', 'clients'])) {
            $activities = $activities->merge($this->getClientActivities($companyId));
        }
        
        if (in_array($this->filter, ['all', 'system'])) {
            $activities = $activities->merge($this->getSystemActivities($companyId));
        }
        
        // Sort by timestamp and limit
        $this->activities = $activities
            ->sortByDesc('timestamp')
            ->take($this->limit);
            
        $this->loading = false;
    }
    
    protected function getTicketActivities($companyId)
    {
        $query = Ticket::where('company_id', $companyId)
            ->with(['client', 'assignee'])
            ->orderByDesc('updated_at')
            ->limit(6);
            
        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }
        
        $tickets = $query->get();
        
        return $tickets->map(function ($ticket) {
            $icon = 'ticket';
            $color = 'orange';
            $action = 'updated';
            
            if ($ticket->created_at->eq($ticket->updated_at)) {
                $action = 'created';
                $icon = 'plus-circle';
                $color = 'green';
            } elseif ($ticket->status === 'Closed') {
                $action = 'closed';
                $icon = 'check-circle';
                $color = 'gray';
            } elseif ($ticket->status === 'Resolved') {
                $action = 'resolved';
                $icon = 'check-circle';
                $color = 'green';
            }
            
            return [
                'id' => 'ticket_' . $ticket->id,
                'type' => 'ticket',
                'icon' => $icon,
                'color' => $color,
                'title' => "Ticket #{$ticket->id} {$action}",
                'description' => $ticket->subject,
                'user' => $ticket->assignee?->name ?? 'System',
                'client' => $ticket->client?->name,
                'timestamp' => $ticket->updated_at,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'link' => route('tickets.show', $ticket->id),
            ];
        });
    }
    
    protected function getFinancialActivities($companyId)
    {
        $activities = collect();
        
        // Recent invoices
        $invoiceQuery = Invoice::where('company_id', $companyId)
            ->with('client')
            ->orderByDesc('created_at')
            ->limit(6);
            
        if ($this->clientId) {
            $invoiceQuery->where('client_id', $this->clientId);
        }
        
        $invoices = $invoiceQuery->get();
        
        $activities = $activities->merge($invoices->map(function ($invoice) {
            return [
                'id' => 'invoice_' . $invoice->id,
                'type' => 'financial',
                'icon' => 'document-text',
                'color' => 'blue',
                'title' => "Invoice #{$invoice->invoice_number} created",
                'description' => '$' . number_format($invoice->amount, 2) . ' - ' . $invoice->description,
                'user' => $invoice->createdBy?->name ?? 'System',
                'client' => $invoice->client?->name,
                'timestamp' => $invoice->created_at,
                'status' => $invoice->status,
                'link' => route('financial.invoices.show', $invoice->id),
            ];
        }));
        
        // Recent payments
        $paymentQuery = Payment::where('company_id', $companyId)
            ->with(['client', 'invoice'])
            ->orderByDesc('created_at')
            ->limit(6);
            
        if ($this->clientId) {
            $paymentQuery->where('client_id', $this->clientId);
        }
        
        $payments = $paymentQuery->get();
        
        $activities = $activities->merge($payments->map(function ($payment) {
            return [
                'id' => 'payment_' . $payment->id,
                'type' => 'financial',
                'icon' => 'currency-dollar',
                'color' => 'green',
                'title' => 'Payment received',
                'description' => '$' . number_format($payment->amount, 2) . ' for Invoice #' . $payment->invoice?->invoice_number,
                'user' => 'System',
                'client' => $payment->client?->name,
                'timestamp' => $payment->created_at,
                'method' => $payment->payment_method,
                'link' => $payment->invoice_id ? route('financial.invoices.show', $payment->invoice_id) : '#',
            ];
        }));
        
        return $activities;
    }
    
    protected function getClientActivities($companyId)
    {
        $query = Client::where('company_id', $companyId)
            ->orderByDesc('updated_at')
            ->limit(5);
            
        if ($this->clientId) {
            $query->where('id', $this->clientId);
        }
        
        $clients = $query->get();
        
        return $clients->map(function ($client) {
            $action = $client->created_at->eq($client->updated_at) ? 'added' : 'updated';
            
            return [
                'id' => 'client_' . $client->id,
                'type' => 'client',
                'icon' => $action === 'added' ? 'user-plus' : 'user',
                'color' => $action === 'added' ? 'purple' : 'blue',
                'title' => "Client {$action}",
                'description' => $client->name,
                'user' => $client->createdBy?->name ?? 'System',
                'client' => $client->name,
                'timestamp' => $client->updated_at,
                'status' => $client->status,
                'link' => route('clients.index', ['client' => $client->id]),
            ];
        });
    }
    
    protected function getSystemActivities($companyId)
    {
        // Get real system activities from logs or audit trails
        // For now, return empty collection until proper system logging is implemented
        return collect();

        // Future implementation could include:
        // - User login/logout activities
        // - System backup completions
        // - Data synchronization events
        // - Security events
        // - Configuration changes
    }
    
    public function setFilter($filter)
    {
        if (in_array($filter, ['all', 'tickets', 'financial', 'clients', 'system'])) {
            $this->filter = $filter;
            $this->loadActivities();
        }
    }
    
    public function loadMore()
    {
        $this->limit += 10;
        $this->loadActivities();
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.activity-feed');
    }
}