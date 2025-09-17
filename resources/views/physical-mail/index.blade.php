@extends('layouts.app')

@section('title', 'Physical Mail Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header with Actions -->
    <div class="mb-6">
        <flux:heading size="xl">Physical Mail Dashboard</flux:heading>
        <flux:text class="text-zinc-500">Manage and track all physical mail sent to clients</flux:text>
        
        <div class="mt-4 flex gap-2">
            <flux:button href="{{ route('mail.send') }}" icon="paper-airplane">
                Send New Mail
            </flux:button>
            <flux:button variant="secondary" href="{{ route('mail.templates') }}" icon="document-text">
                Templates
            </flux:button>
            <flux:button variant="secondary" href="{{ route('mail.tracking') }}" icon="map">
                Tracking
            </flux:button>
        </div>
    </div>

    <!-- Statistics Cards -->
    @php
        $stats = [
            'total' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::count(),
            'month' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::whereMonth('created_at', now()->month)->count(),
            'pending' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::whereIn('status', ['pending', 'processing'])->count(),
            'delivered' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('status', 'delivered')->count(),
        ];
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <flux:card.body>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Total Sent</flux:text>
                        <flux:heading size="2xl">{{ number_format($stats['total']) }}</flux:heading>
                    </div>
                    <div class="text-blue-500">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>

        <flux:card>
            <flux:card.body>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">This Month</flux:text>
                        <flux:heading size="2xl">{{ number_format($stats['month']) }}</flux:heading>
                    </div>
                    <div class="text-green-500">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>

        <flux:card>
            <flux:card.body>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">In Transit</flux:text>
                        <flux:heading size="2xl">{{ number_format($stats['pending']) }}</flux:heading>
                    </div>
                    <div class="text-yellow-500">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
                        </svg>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>

        <flux:card>
            <flux:card.body>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">Delivered</flux:text>
                        <flux:heading size="2xl">{{ number_format($stats['delivered']) }}</flux:heading>
                    </div>
                    <div class="text-purple-500">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </flux:card.body>
        </flux:card>
    </div>

    <!-- Recent Mail Orders Table -->
    @php
        $recentOrders = \App\Domains\PhysicalMail\Models\PhysicalMailOrder::with(['client', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    @endphp

    <flux:card>
        <flux:card.header>
            <flux:card.title>Recent Mail Orders</flux:card.title>
        </flux:card.header>
        
        <flux:table>
            <flux:columns>
                <flux:column>Date</flux:column>
                <flux:column>Client</flux:column>
                <flux:column>Type</flux:column>
                <flux:column>Status</flux:column>
                <flux:column>Tracking</flux:column>
                <flux:column>Actions</flux:column>
            </flux:columns>
            
            <flux:rows>
                @forelse($recentOrders as $order)
                    <flux:row>
                        <flux:cell>{{ $order->created_at->format('M d, Y') }}</flux:cell>
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
                            @php
                                $statusColors = [
                                    'pending' => 'yellow',
                                    'processing' => 'blue',
                                    'printed' => 'cyan',
                                    'mailed' => 'purple',
                                    'delivered' => 'green',
                                    'cancelled' => 'zinc',
                                    'failed' => 'red',
                                ];
                            @endphp
                            <flux:badge variant="{{ $statusColors[$order->status] ?? 'neutral' }}">
                                {{ ucfirst($order->status) }}
                            </flux:badge>
                        </flux:cell>
                        <flux:cell>
                            @if($order->tracking_number)
                                <a href="#" class="text-blue-500 hover:underline">
                                    {{ $order->tracking_number }}
                                </a>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" icon="eye" 
                                    onclick="viewOrder('{{ $order->id }}')">
                                </flux:button>
                                @if($order->pdf_url)
                                    <flux:button size="sm" variant="ghost" icon="document" 
                                        onclick="window.open('{{ $order->pdf_url }}', '_blank')">
                                    </flux:button>
                                @endif
                                @if($order->canBeCancelled())
                                    <flux:button size="sm" variant="ghost" icon="x-mark" 
                                        onclick="cancelOrder('{{ $order->id }}')">
                                    </flux:button>
                                @endif
                            </div>
                        </flux:cell>
                    </flux:row>
                @empty
                    <flux:row>
                        <flux:cell colspan="6" class="text-center text-zinc-400 py-8">
                            No mail orders yet. <a href="{{ route('mail.send') }}" class="text-blue-500 hover:underline">Send your first mail</a>
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>
    </flux:card>
</div>

@push('scripts')
<script>
function viewOrder(orderId) {
    // Implement order details modal
    console.log('View order:', orderId);
}

function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this mail order?')) {
        return;
    }
    
    fetch(`/api/physical-mail/${orderId}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to cancel order');
        }
    });
}
</script>
@endpush
@endsection