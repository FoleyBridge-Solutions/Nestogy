@extends('layouts.app')

@section('title', 'Mail Tracking')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <flux:heading size="xl">Mail Tracking</flux:heading>
        <flux:text class="text-zinc-500">Track the delivery status of all physical mail</flux:text>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:select name="status">
                    <flux:select.option value="">All Statuses</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="processing">Processing</flux:select.option>
                    <flux:select.option value="printed">Printed</flux:select.option>
                    <flux:select.option value="mailed">Mailed</flux:select.option>
                    <flux:select.option value="in_transit">In Transit</flux:select.option>
                    <flux:select.option value="delivered">Delivered</flux:select.option>
                    <flux:select.option value="returned">Returned</flux:select.option>
                </flux:select>
                
                <flux:select name="client">
                    <flux:select.option value="">All Clients</flux:select.option>
                    @foreach(\App\Models\Client::orderBy('name')->get() as $client)
                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:input type="date" name="date_from" placeholder="From Date" />
                <flux:input type="date" name="date_to" placeholder="To Date" />
            </div>
            
            <div class="mt-4 flex gap-2">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                    Apply Filters
                </button>
                <flux:button variant="ghost" onclick="resetFilters()">
                    Reset
                </button>
            </div>
        </div>
    </flux:card>

    <!-- Tracking Map View (Placeholder) -->
    <flux:card class="mb-6">
        <div>
            <flux:heading size="lg">Delivery Map</flux:heading>
            <flux:text size="sm" class="text-zinc-500">Visual representation of mail in transit</flux:text>
        </div>
        <div>
            <div class="h-64 bg-zinc-100 rounded-lg flex items-center justify-center">
                <flux:text class="text-zinc-400">Map view coming soon</flux:text>
            </div>
        </div>
    </flux:card>

    <!-- Tracking List -->
    @php
        $orders = \App\Domains\PhysicalMail\Models\PhysicalMailOrder::with(['client', 'createdBy'])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    @endphp

    <flux:card>
        <div>
            <flux:heading size="lg">Mail Items</flux:heading>
            <flux:badge variant="neutral">{{ $orders->total() }} items</flux:badge>
        </div>
        
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Tracking #</flux:table.column>
                <flux:table.column>Client</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Sent Date</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Est. Delivery</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @foreach($orders as $order)
                    <flux:table.row>
                        <flux:table.cell>
                            @if($order->tracking_number)
                                <a href="#" onclick="showTracking('{{ $order->id }}')" class="text-blue-500 hover:underline">
                                    {{ $order->tracking_number }}
                                </a>
                            @else
                                <flux:text size="sm" class="text-zinc-400">{{ Str::limit($order->postgrid_id ?? 'Pending', 12) }}</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($order->client)
                                {{ $order->client->name }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="neutral">
                                {{ ucfirst($order->mailable_type) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $order->created_at->format('M d, Y') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusInfo = [
                                    'pending' => ['color' => 'yellow', 'icon' => 'clock'],
                                    'processing' => ['color' => 'blue', 'icon' => 'cog'],
                                    'printed' => ['color' => 'cyan', 'icon' => 'printer'],
                                    'mailed' => ['color' => 'purple', 'icon' => 'mail'],
                                    'in_transit' => ['color' => 'indigo', 'icon' => 'truck'],
                                    'delivered' => ['color' => 'green', 'icon' => 'check-circle'],
                                    'returned' => ['color' => 'red', 'icon' => 'x-circle'],
                                ];
                                $info = $statusInfo[$order->status] ?? ['color' => 'zinc', 'icon' => 'question-mark-circle'];
                            @endphp
                            <flux:badge variant="{{ $info['color'] }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($order->estimated_delivery_date)
                                {{ $order->estimated_delivery_date->format('M d, Y') }}
                            @elseif(in_array($order->status, ['mailed', 'in_transit']))
                                {{ $order->created_at->addDays(5)->format('M d, Y') }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" 
                                    onclick="showDetails('{{ $order->id }}')">
                                </button>
                                @if($order->pdf_url)
                                    <flux:button size="sm" variant="ghost" icon="document" 
                                        onclick="window.open('{{ $order->pdf_url }}', '_blank')">
                                    </button>
                                @endif
                                @if($order->status === 'pending' && config('physical_mail.postgrid.test_mode'))
                                    <flux:button size="sm" variant="ghost" icon="arrow-path" 
                                        onclick="progressTest('{{ $order->id }}')"
                                        title="Progress test order">
                                    </button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        
        @if($orders->hasPages())
            <div>
                {{ $orders->links() }}
            </div>
        @endif
    </flux:card>
</div>

@push('scripts')
<script>
function applyFilters() {
    // Implement filter logic
    console.log('Applying filters...');
}

function resetFilters() {
    document.querySelector('[name="status"]').value = '';
    document.querySelector('[name="client"]').value = '';
    document.querySelector('[name="date_from"]').value = '';
    document.querySelector('[name="date_to"]').value = '';
    applyFilters();
}

function showTracking(orderId) {
    fetch(`/api/physical-mail/${orderId}/tracking`)
        .then(response => response.json())
        .then(data => {
            console.log('Tracking data:', data);
            // Show tracking modal
            alert('Tracking information:\n' + JSON.stringify(data.tracking, null, 2));
        });
}

function showDetails(orderId) {
    fetch(`/api/physical-mail/${orderId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Order details:', data);
            // Show details modal
            alert('Order details:\n' + JSON.stringify(data.order, null, 2));
        });
}

function progressTest(orderId) {
    if (!confirm('Progress this test order to the next status?')) {
        return;
    }
    
    fetch(`/api/physical-mail/${orderId}/progress-test`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order progressed to: ' + data.status);
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to progress order'));
        }
    });
}
</script>
@endpush
@endsection