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
        <flux:card.body>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:select name="status">
                    <flux:option value="">All Statuses</flux:option>
                    <flux:option value="pending">Pending</flux:option>
                    <flux:option value="processing">Processing</flux:option>
                    <flux:option value="printed">Printed</flux:option>
                    <flux:option value="mailed">Mailed</flux:option>
                    <flux:option value="in_transit">In Transit</flux:option>
                    <flux:option value="delivered">Delivered</flux:option>
                    <flux:option value="returned">Returned</flux:option>
                </flux:select>
                
                <flux:select name="client">
                    <flux:option value="">All Clients</flux:option>
                    @foreach(\App\Models\Client::orderBy('name')->get() as $client)
                        <flux:option value="{{ $client->id }}">{{ $client->name }}</flux:option>
                    @endforeach
                </flux:select>
                
                <flux:input type="date" name="date_from" placeholder="From Date" />
                <flux:input type="date" name="date_to" placeholder="To Date" />
            </div>
            
            <div class="mt-4 flex gap-2">
                <flux:button variant="secondary" onclick="applyFilters()">
                    Apply Filters
                </flux:button>
                <flux:button variant="ghost" onclick="resetFilters()">
                    Reset
                </flux:button>
            </div>
        </flux:card.body>
    </flux:card>

    <!-- Tracking Map View (Placeholder) -->
    <flux:card class="mb-6">
        <flux:card.header>
            <flux:card.title>Delivery Map</flux:card.title>
            <flux:text size="sm" class="text-zinc-500">Visual representation of mail in transit</flux:text>
        </flux:card.header>
        <flux:card.body>
            <div class="h-64 bg-zinc-100 rounded-lg flex items-center justify-center">
                <flux:text class="text-zinc-400">Map view coming soon</flux:text>
            </div>
        </flux:card.body>
    </flux:card>

    <!-- Tracking List -->
    @php
        $orders = \App\Domains\PhysicalMail\Models\PhysicalMailOrder::with(['client', 'createdBy'])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    @endphp

    <flux:card>
        <flux:card.header>
            <flux:card.title>Mail Items</flux:card.title>
            <flux:badge variant="neutral">{{ $orders->total() }} items</flux:badge>
        </flux:card.header>
        
        <flux:table>
            <flux:columns>
                <flux:column>Tracking #</flux:column>
                <flux:column>Client</flux:column>
                <flux:column>Type</flux:column>
                <flux:column>Sent Date</flux:column>
                <flux:column>Status</flux:column>
                <flux:column>Est. Delivery</flux:column>
                <flux:column>Actions</flux:column>
            </flux:columns>
            
            <flux:rows>
                @foreach($orders as $order)
                    <flux:row>
                        <flux:cell>
                            @if($order->tracking_number)
                                <a href="#" onclick="showTracking('{{ $order->id }}')" class="text-blue-500 hover:underline">
                                    {{ $order->tracking_number }}
                                </a>
                            @else
                                <flux:text size="sm" class="text-zinc-400">{{ Str::limit($order->postgrid_id ?? 'Pending', 12) }}</flux:text>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            @if($order->client)
                                {{ $order->client->name }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            <flux:badge variant="neutral">
                                {{ ucfirst($order->mailable_type) }}
                            </flux:badge>
                        </flux:cell>
                        <flux:cell>
                            {{ $order->created_at->format('M d, Y') }}
                        </flux:cell>
                        <flux:cell>
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
                        </flux:cell>
                        <flux:cell>
                            @if($order->estimated_delivery_date)
                                {{ $order->estimated_delivery_date->format('M d, Y') }}
                            @elseif(in_array($order->status, ['mailed', 'in_transit']))
                                {{ $order->created_at->addDays(5)->format('M d, Y') }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" 
                                    onclick="showDetails('{{ $order->id }}')">
                                </flux:button>
                                @if($order->pdf_url)
                                    <flux:button size="sm" variant="ghost" icon="document" 
                                        onclick="window.open('{{ $order->pdf_url }}', '_blank')">
                                    </flux:button>
                                @endif
                                @if($order->status === 'pending' && config('physical_mail.postgrid.test_mode'))
                                    <flux:button size="sm" variant="ghost" icon="arrow-path" 
                                        onclick="progressTest('{{ $order->id }}')"
                                        title="Progress test order">
                                    </flux:button>
                                @endif
                            </div>
                        </flux:cell>
                    </flux:row>
                @endforeach
            </flux:rows>
        </flux:table>
        
        @if($orders->hasPages())
            <flux:card.footer>
                {{ $orders->links() }}
            </flux:card.footer>
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