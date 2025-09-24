@extends('layouts.app')

@section('title', 'Mail Tracking')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <flux:heading size="xl">Mail Tracking</flux:heading>
        <flux:text class="text-zinc-500">
            Track the delivery status of all physical mail
            @if(isset($selectedClient) && $selectedClient)
                for {{ $selectedClient->name }}
            @endif
        </flux:text>
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
                
                @if(!isset($selectedClient) || !$selectedClient)
                    <flux:select name="client">
                        <flux:select.option value="">All Clients</flux:select.option>
                        @foreach(\App\Models\Client::orderBy('name')->get() as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <input type="hidden" name="client" value="{{ $selectedClient->id }}">
                    <div class="px-3 py-2 bg-gray-100 rounded-lg">
                        <flux:text size="sm" class="text-gray-500">Client</flux:text>
                        <flux:text class="font-medium">{{ $selectedClient->name }}</flux:text>
                    </div>
                @endif
                
                <flux:input type="date" name="date_from" placeholder="From Date" />
                <flux:input type="date" name="date_to" placeholder="To Date" />
            </div>
            
            <div class="mt-4 flex gap-2">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors" onclick="applyFilters()">
                    Apply Filters
                </button>
                <flux:button variant="ghost" onclick="resetFilters()">
                    Reset
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Tracking Map View -->
    <flux:card class="mb-6">
        <div>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Delivery Map</flux:heading>
                    <flux:text size="sm" class="text-zinc-500">Visual representation of mail in transit</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" icon="arrow-path" onclick="refreshMap()">Refresh</flux:button>
                    <flux:button size="sm" variant="ghost" icon="arrows-pointing-out" onclick="toggleFullscreen()">Fullscreen</flux:button>
                </div>
            </div>
        </div>
        <div>
            <!-- Map Container -->
            <div id="mail-tracking-map" class="h-96 bg-zinc-100 rounded-lg relative">
                <div id="map-loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <flux:text class="block mt-2 text-zinc-600">Loading map...</flux:text>
                    </div>
                </div>
            </div>
            
            <!-- Map Legend -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <flux:heading size="sm" class="mb-2">Legend</flux:heading>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                        <flux:text size="xs">Pending</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                        <flux:text size="xs">Processing</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-purple-500"></div>
                        <flux:text size="xs">In Transit</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-green-500"></div>
                        <flux:text size="xs">Delivered</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Tracking List -->
    <flux:card>
        <div>
            <flux:heading size="lg">Mail Items</flux:heading>
            @if($orders && $orders->count() > 0)
                <flux:badge variant="neutral">{{ $orders->total() }} items</flux:badge>
            @else
                <flux:badge variant="neutral">0 items</flux:badge>
            @endif
        </div>
        
        @if($orders && $orders->count() > 0)
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
                                    {{ ucfirst($order->mailable_type ?? 'letter') }}
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
                                    {{ ucfirst(str_replace('_', ' ', $order->status ?? 'unknown')) }}
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
                                    <flux:button size="sm" variant="ghost" icon="eye" onclick="showDetails('{{ $order->id }}')"></flux:button>
                                    @if($order->pdf_url)
                                        <flux:button size="sm" variant="ghost" icon="document" onclick="window.open('{{ $order->pdf_url }}', '_blank')"></flux:button>
                                    @endif
                                    @if($order->status === 'pending' && config('physical_mail.postgrid.test_mode'))
                                        <flux:button size="sm" variant="ghost" icon="arrow-path" onclick="progressTest('{{ $order->id }}')" title="Progress test order"></flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            
            @if($orders->hasPages())
                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <div class="py-8 text-center">
                <flux:text class="text-zinc-400">No mail orders with tracking information yet.</flux:text>
            </div>
        @endif
    </flux:card>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
<style>
    .leaflet-popup-content {
        min-width: 200px;
    }
    .mail-marker {
        background-color: white;
        border: 2px solid currentColor;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .marker-pending { color: #EAB308; }
    .marker-processing { color: #3B82F6; }
    .marker-printed { color: #06B6D4; }
    .marker-mailed, .marker-in_transit { color: #A855F7; }
    .marker-delivered { color: #22C55E; }
    .marker-returned { color: #EF4444; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
<script>
// Global map variable
let map = null;
let markers = [];
let markerCluster = null;

// Initialize the map when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
    loadMapData();
});

function initializeMap() {
    // Create the map centered on the US
    map = L.map('mail-tracking-map').setView([39.8283, -98.5795], 4);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);
    
    // Hide loading indicator
    document.getElementById('map-loading').style.display = 'none';
}

function loadMapData() {
    // Build URL with client filter if selected
    let url = '/api/physical-mail?include_locations=true';
    @if(isset($selectedClient) && $selectedClient)
        url += '&client_id={{ $selectedClient->id }}';
    @endif
    
    // Fetch mail orders with location data
    fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.data && data.data.length > 0) {
                plotMailItems(data.data);
            } else {
                showEmptyMapState();
            }
        })
        .catch(error => {
            console.error('Error loading map data:', error);
            showEmptyMapState();
        });
}

function showEmptyMapState() {
    // Hide loading
    document.getElementById('map-loading').style.display = 'none';
    
    // Show message on map
    const mapContainer = document.getElementById('mail-tracking-map');
    const emptyMessage = document.createElement('div');
    emptyMessage.className = 'absolute inset-0 flex items-center justify-center z-20 bg-zinc-100';
    emptyMessage.innerHTML = `
        <div class="text-center p-6">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            <p class="text-gray-600 font-medium text-lg">No mail items to display</p>
            <p class="text-sm text-gray-500 mt-1">Mail items with tracking will appear here once sent</p>
        </div>
    `;
    mapContainer.innerHTML = '';
    mapContainer.appendChild(emptyMessage);
}

function plotMailItems(orders) {
    // Clear existing markers
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    // Create markers for each order
    orders.forEach(order => {
        if (order.lat && order.lng) {
            const marker = createMailMarker(order);
            markers.push(marker);
            marker.addTo(map);
        }
    });
    
    // Fit map to show all markers if there are any
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

function createMailMarker(order) {
    // Create custom icon based on status
    const statusColors = {
        'pending': '#EAB308',
        'processing': '#3B82F6',
        'printed': '#06B6D4',
        'mailed': '#A855F7',
        'in_transit': '#A855F7',
        'delivered': '#22C55E',
        'returned': '#EF4444'
    };
    
    const color = statusColors[order.status] || '#6B7280';
    
    // Create a custom div icon
    const icon = L.divIcon({
        className: 'custom-mail-marker',
        html: `<div class="mail-marker marker-${order.status}" style="color: ${color};">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                  </svg>
               </div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -15]
    });
    
    // Create marker
    const marker = L.marker([order.lat, order.lng], { icon: icon });
    
    // Add popup with order details
    const popupContent = `
        <div class="p-2">
            <h4 class="font-bold text-sm mb-1">${order.client?.name || 'Unknown Client'}</h4>
            <p class="text-xs text-gray-600 mb-2">${order.address || 'No address'}</p>
            <div class="space-y-1">
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Tracking:</span>
                    <span class="font-medium">${order.tracking_number || order.id}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">Status:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" 
                          style="background-color: ${color}20; color: ${color};">
                        ${order.status.replace('_', ' ')}
                    </span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t">
                <button onclick="showDetails('${order.id}')" 
                        class="text-xs text-blue-600 hover:text-blue-800">
                    View Details →
                </button>
            </div>
        </div>
    `;
    
    marker.bindPopup(popupContent);
    
    return marker;
}

function refreshMap() {
    // Show loading
    document.getElementById('map-loading').style.display = 'flex';
    
    // Reload data
    loadMapData();
    
    // Hide loading after a delay
    setTimeout(() => {
        document.getElementById('map-loading').style.display = 'none';
    }, 500);
}

function toggleFullscreen() {
    const mapContainer = document.getElementById('mail-tracking-map');
    
    if (!document.fullscreenElement) {
        mapContainer.requestFullscreen().then(() => {
            mapContainer.style.height = '100vh';
            map.invalidateSize();
        });
    } else {
        document.exitFullscreen().then(() => {
            mapContainer.style.height = '24rem'; // h-96
            map.invalidateSize();
        });
    }
}

function applyFilters() {
    // Get filter values
    const status = document.querySelector('[name="status"]').value;
    const clientField = document.querySelector('[name="client"]');
    const client = clientField ? clientField.value : '{{ isset($selectedClient) && $selectedClient ? $selectedClient->id : '' }}';
    const dateFrom = document.querySelector('[name="date_from"]').value;
    const dateTo = document.querySelector('[name="date_to"]').value;
    
    // Build query parameters
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    if (client) params.append('client_id', client);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    // Reload page with filters
    window.location.href = `/mail/tracking?${params.toString()}`;
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