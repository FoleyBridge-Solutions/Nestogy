@extends('client-portal.layouts.app')

@section('title', 'Asset Details - ' . ($asset->name ?? 'Asset'))

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('client.assets') }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition">
            <flux:icon.arrow-left class="size-5" />
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $asset->name ?? 'Asset Details' }}</h1>
        <span class="px-3 py-1 text-sm font-semibold rounded-full 
            @if($asset->status === 'Deployed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
            @elseif($asset->status === 'Ready To Deploy') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
            @elseif(str_contains($asset->status, 'Broken')) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
            @elseif($asset->status === 'Archived') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
            @endif">
            {{ $asset->status ?? 'Unknown' }}
        </span>
    </div>
    <p class="text-gray-600 dark:text-gray-400 ml-8">{{ $asset->type ?? 'Asset' }} • Last updated {{ $asset->updated_at->diffForHumans() }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Asset Information -->
        <flux:card>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Asset Information</h2>
                @if($asset->type)
                    <span class="text-sm px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full">
                        <flux:icon.computer-desktop class="size-4 inline-block mr-1" />
                        {{ $asset->type }}
                    </span>
                @endif
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($asset->make || $asset->model)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Make & Model</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100 font-medium">
                        {{ $asset->make ?? '' }} {{ $asset->model ?? '' }}
                    </dd>
                </div>
                @endif
                
                @if($asset->serial)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Serial Number</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100 font-mono">{{ $asset->serial }}</dd>
                </div>
                @endif
                
                @if($asset->os)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Operating System</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">{{ $asset->os }}</dd>
                </div>
                @endif
                
                @if($asset->purchase_date)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Purchase Date</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">{{ $asset->purchase_date->format('M d, Y') }}</dd>
                </div>
                @endif
                
                @if($asset->install_date)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Install Date</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">{{ $asset->install_date->format('M d, Y') }}</dd>
                </div>
                @endif
                
                @if($asset->vendor)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Vendor</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">{{ $asset->vendor->name }}</dd>
                </div>
                @endif
            </div>
            
            @if($asset->description)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</dt>
                <dd class="text-base text-gray-700 dark:text-gray-300 leading-relaxed">{{ $asset->description }}</dd>
            </div>
            @endif
        </flux:card>

        <!-- Network Information -->
        @if($asset->ip || $asset->nat_ip || $asset->mac || $asset->uri || $asset->uri_2)
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">Network Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($asset->ip)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">IP Address</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100 font-mono">{{ $asset->ip }}</dd>
                </div>
                @endif
                
                @if($asset->nat_ip)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">NAT IP Address</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100 font-mono">{{ $asset->nat_ip }}</dd>
                </div>
                @endif
                
                @if($asset->mac)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">MAC Address</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100 font-mono uppercase">{{ $asset->mac }}</dd>
                </div>
                @endif
                
                @if($asset->network)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Network</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">{{ $asset->network->name ?? 'N/A' }}</dd>
                </div>
                @endif
                
                @if($asset->uri)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Primary URL</dt>
                    <dd class="text-base">
                        <a href="{{ $asset->uri }}" target="_blank" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline">
                            {{ $asset->uri }}
                            <flux:icon.arrow-top-right-on-square class="size-4 inline-block ml-1" />
                        </a>
                    </dd>
                </div>
                @endif
                
                @if($asset->uri_2)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Secondary URL</dt>
                    <dd class="text-base">
                        <a href="{{ $asset->uri_2 }}" target="_blank" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline">
                            {{ $asset->uri_2 }}
                            <flux:icon.arrow-top-right-on-square class="size-4 inline-block ml-1" />
                        </a>
                    </dd>
                </div>
                @endif
            </div>
        </flux:card>
        @endif

        <!-- Location & Assignment -->
        @if($asset->location || $asset->contact)
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">Location & Assignment</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($asset->location)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Location</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">
                        <div class="space-y-1">
                            <div class="font-semibold">{{ $asset->location->name }}</div>
                            @if($asset->location->address)
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $asset->location->address }}<br>
                                    {{ $asset->location->city }}, {{ $asset->location->state }} {{ $asset->location->zip }}
                                </div>
                            @endif
                        </div>
                    </dd>
                </div>
                @endif
                
                @if($asset->contact)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Assigned To</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">
                        <div class="space-y-1">
                            <div class="font-semibold">{{ $asset->contact->name }}</div>
                            @if($asset->contact->email)
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $asset->contact->email }}</div>
                            @endif
                            @if($asset->contact->phone)
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $asset->contact->phone }}</div>
                            @endif
                        </div>
                    </dd>
                </div>
                @endif
            </div>
        </flux:card>
        @endif

        <!-- Support Information -->
        @if($asset->support_status || $asset->supportingContract)
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">Support Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($asset->support_status)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Support Status</dt>
                    <dd class="text-base">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            @if($asset->support_status === 'supported') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($asset->support_status === 'unsupported') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @elseif($asset->support_status === 'pending_assignment') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $asset->support_status)) }}
                        </span>
                    </dd>
                </div>
                @endif
                
                @if($asset->support_level)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Support Level</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100 font-medium">{{ ucfirst($asset->support_level) }}</dd>
                </div>
                @endif
                
                @if($asset->supportingContract)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Supporting Contract</dt>
                    <dd class="text-base text-gray-900 dark:text-gray-100">{{ $asset->supportingContract->name }}</dd>
                </div>
                @endif
                
                @if($asset->support_notes)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Support Notes</dt>
                    <dd class="text-base text-gray-700 dark:text-gray-300">{{ $asset->support_notes }}</dd>
                </div>
                @endif
            </div>
        </flux:card>
        @endif

        <!-- Additional Notes -->
        @if($asset->notes)
        <flux:card>
            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Notes</h2>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">{{ $asset->notes }}</p>
        </flux:card>
        @endif
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Stats -->
        <flux:card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Quick Stats</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-2">
                        <flux:icon.clock class="size-5 text-gray-400" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Age</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        @if($asset->purchase_date)
                            {{ $asset->purchase_date->diffForHumans(null, true) }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                
                @if($asset->tickets()->count() > 0)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-2">
                        <flux:icon.ticket class="size-5 text-gray-400" />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Tickets</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $asset->tickets()->count() }}</span>
                </div>
                @endif
            </div>
        </flux:card>

        <!-- Warranty Status -->
        @if($asset->warranty_expire)
        <flux:card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                <flux:icon.shield-check class="size-5 inline-block mr-2" />
                Warranty
            </h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Expires</span>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                        {{ $asset->warranty_expire->format('M d, Y') }}
                    </p>
                    <p class="text-sm mt-1
                        @if($asset->warranty_expire->isPast()) text-red-600 dark:text-red-400
                        @elseif($asset->warranty_expire->diffInDays(now()) <= 60) text-yellow-600 dark:text-yellow-400
                        @else text-green-600 dark:text-green-400
                        @endif">
                        @if($asset->warranty_expire->isPast())
                            <flux:icon.exclamation-circle class="size-4 inline-block" />
                            Expired {{ $asset->warranty_expire->diffForHumans() }}
                        @elseif($asset->warranty_expire->diffInDays(now()) <= 60)
                            <flux:icon.exclamation-triangle class="size-4 inline-block" />
                            Expires in {{ $asset->warranty_expire->diffInDays(now()) }} days
                        @else
                            <flux:icon.check-circle class="size-4 inline-block" />
                            Active ({{ $asset->warranty_expire->diffInDays(now()) }} days remaining)
                        @endif
                    </p>
                </div>
            </div>
        </flux:card>
        @endif
        
        <!-- Maintenance -->
        @if($asset->next_maintenance_date)
        <flux:card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">
                <flux:icon.wrench-screwdriver class="size-5 inline-block mr-2" />
                Maintenance
            </h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Next Scheduled</span>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">
                        {{ $asset->next_maintenance_date->format('M d, Y') }}
                    </p>
                    <p class="text-sm mt-1 text-gray-600 dark:text-gray-400">
                        {{ $asset->next_maintenance_date->diffForHumans() }}
                    </p>
                </div>
            </div>
        </flux:card>
        @endif
        
        <!-- System Information -->
        <flux:card>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">System Information</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-600 dark:text-gray-400">Created</span>
                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $asset->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-gray-600 dark:text-gray-400">Last Updated</span>
                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $asset->updated_at->format('M d, Y') }}</span>
                </div>
                @if($asset->accessed_at)
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600 dark:text-gray-400">Last Accessed</span>
                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $asset->accessed_at->diffForHumans() }}</span>
                </div>
                @endif
            </div>
        </flux:card>
    </div>
</div>
@endsection
