<?php

namespace App\Livewire\PhysicalMail;

use App\Domains\PhysicalMail\Models\PhysicalMailOrder;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class MailHistory extends Component
{
    use WithPagination;
    
    public ?Client $client = null;
    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 10;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];
    
    public function mount(?Client $client = null)
    {
        $this->client = $client;
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingTypeFilter()
    {
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }
    
    public function viewDetails($orderId)
    {
        $this->dispatch('openMailDetails', ['order_id' => $orderId]);
    }
    
    public function cancelOrder($orderId)
    {
        try {
            $order = PhysicalMailOrder::findOrFail($orderId);
            
            if (!in_array($order->status, ['pending', 'processing'])) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Cannot cancel mail that has already been sent',
                ]);
                return;
            }
            
            // Cancel via PostGrid API
            $service = app(\App\Domains\PhysicalMail\Services\PhysicalMailService::class);
            $service->cancel($order);
            
            $order->update(['status' => 'cancelled']);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Mail order cancelled successfully',
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to cancel mail order: ' . $e->getMessage(),
            ]);
        }
    }
    
    public function resendMail($orderId)
    {
        try {
            $order = PhysicalMailOrder::findOrFail($orderId);
            
            // Clone the order and resend
            $service = app(\App\Domains\PhysicalMail\Services\PhysicalMailService::class);
            $newOrder = $service->resend($order);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Mail queued for resending',
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to resend mail: ' . $e->getMessage(),
            ]);
        }
    }
    
    public function downloadPdf($orderId)
    {
        $order = PhysicalMailOrder::findOrFail($orderId);
        
        if (!$order->pdf_url) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'PDF not available for this mail',
            ]);
            return;
        }
        
        return redirect()->away($order->pdf_url);
    }
    
    public function getStatusColor($status)
    {
        return match($status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'in_transit' => 'indigo',
            'delivered' => 'green',
            'returned' => 'orange',
            'cancelled' => 'zinc',
            'failed' => 'red',
            default => 'zinc',
        };
    }
    
    public function getStatusIcon($status)
    {
        return match($status) {
            'pending' => 'clock',
            'processing' => 'arrow-path',
            'in_transit' => 'truck',
            'delivered' => 'check-circle',
            'returned' => 'arrow-uturn-left',
            'cancelled' => 'x-circle',
            'failed' => 'exclamation-circle',
            default => 'question-mark-circle',
        };
    }
    
    public function render()
    {
        $query = PhysicalMailOrder::query()
            ->with(['mailable', 'letters', 'contact']);
        
        // Filter by client if provided
        if ($this->client) {
            $query->where('client_id', $this->client->id);
        }
        
        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $this->search . '%')
                  ->orWhere('postgrid_id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('contact', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('company', 'like', '%' . $this->search . '%');
                  });
            });
        }
        
        // Status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        // Type filter
        if ($this->typeFilter) {
            $query->where('mail_type', $this->typeFilter);
        }
        
        // Date range filter
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
        
        return view('livewire.physical-mail.mail-history', [
            'orders' => $orders,
            'statuses' => [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'in_transit' => 'In Transit',
                'delivered' => 'Delivered',
                'returned' => 'Returned',
                'cancelled' => 'Cancelled',
                'failed' => 'Failed',
            ],
            'types' => [
                'letter' => 'Letter',
                'postcard' => 'Postcard',
                'cheque' => 'Check',
            ],
        ]);
    }
}