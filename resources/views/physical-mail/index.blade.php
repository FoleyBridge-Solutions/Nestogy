@extends('layouts.app')

@section('title', 'Physical Mail Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header with Actions -->
    <div class="mb-6">
        <flux:heading size="xl">Physical Mail Dashboard</flux:heading>
        <flux:text class="text-zinc-500">
            Manage and track all physical mail
            @if(isset($selectedClient) && $selectedClient)
                for {{ $selectedClient->name }}
            @else
                sent to clients
            @endif
        </flux:text>
        
        <div class="mt-4 flex gap-2">
            <flux:button href="{{ route('mail.send') }}" icon="paper-airplane">
                Send New Mail
            </flux:button>
            <flux:button variant="filled" href="{{ route('mail.templates') }}" icon="document-text">
                Templates
            </flux:button>
            <flux:button variant="filled" href="{{ route('mail.tracking') }}" icon="map">
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
            <div>
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
            </div>
        </flux:card>

        <flux:card>
            <div>
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
            </div>
        </flux:card>

        <flux:card>
            <div>
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
            </div>
        </flux:card>

        <flux:card>
            <div>
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
            </div>
        </flux:card>
    </div>

    <!-- Recent Mail Orders Table -->
    @php
        $recentOrders = \App\Domains\PhysicalMail\Models\PhysicalMailOrder::with(['client', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
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

    <flux:card>
        <div>
            <flux:heading size="lg">Recent Mail Orders</flux:heading>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if($recentOrders->count() > 0)
                        @foreach($recentOrders as $order)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $order->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($order->client)
                                    {{ $order->client->name }}
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ ucfirst($order->mailable_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $statusColors[$order->status] ?? 'gray' }}-100 text-{{ $statusColors[$order->status] ?? 'gray' }}-800">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($order->tracking_number)
                                    <a href="#" class="text-blue-500 hover:underline">
                                        {{ $order->tracking_number }}
                                    </a>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-2">
                                    <button type="button" class="text-gray-400 hover:text-gray-600" 
                                        onclick="viewOrder('{{ $order->id }}')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    @if($order->pdf_url)
                                        <button type="button" class="text-gray-400 hover:text-gray-600" 
                                            onclick="window.open('{{ $order->pdf_url }}', '_blank')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>
                                    @endif
                                    @if(in_array($order->status ?? '', ['pending', 'ready']))
                                        <button type="button" class="text-gray-400 hover:text-red-600" 
                                            onclick="cancelOrder('{{ $order->id }}')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-400">
                                No mail orders yet. <a href="{{ route('mail.send') }}" class="text-blue-500 hover:underline">Send your first mail</a>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
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