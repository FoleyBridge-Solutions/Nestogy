@extends('layouts.app')

@section('title', ($domainInfo['name'] ?? ucfirst($domain)) . ' Settings')

@section('content')
<flux:container>
    <!-- Breadcrumb -->
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="{{ route('settings.index') }}" icon="home">
            Settings
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            {{ $domainInfo['name'] ?? ucfirst($domain) }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Header -->
    <flux:card class="mb-6">
        <flux:heading size="xl">{{ $domainInfo['name'] ?? ucfirst($domain) }} Settings</flux:heading>
        @if($domainInfo['description'] ?? null)
            <flux:text class="mt-2">{{ $domainInfo['description'] }}</flux:text>
        @endif
    </flux:card>

    <!-- Category Cards -->
    @if(!empty($categories))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $categoryKey => $categoryData)
                <flux:card href="{{ route('settings.category.show', ['domain' => $domain, 'category' => $categoryKey]) }}" class="hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-4">
                        @if($categoryData['metadata']['icon'] ?? null)
                            <flux:avatar icon="{{ $categoryData['metadata']['icon'] }}" size="md" />
                        @else
                            <flux:avatar icon="folder" size="md" />
                        @endif
                        <flux:icon name="chevron-right" class="w-5 h-5 text-gray-400" />
                    </div>
                    
                    <flux:heading size="md">
                        {{ $categoryData['metadata']['name'] ?? ucfirst(str_replace('_', ' ', $categoryKey)) }}
                    </flux:heading>
                    
                    @if($categoryData['metadata']['description'] ?? null)
                        <flux:text size="sm" class="mt-1">
                            {{ $categoryData['metadata']['description'] }}
                        </flux:text>
                    @endif
                    
                    <!-- Settings Preview -->
                    @if(is_array($categoryData['settings']) && count($categoryData['settings']) > 0)
                        <flux:separator class="my-3" />
                        <div class="space-y-1">
                            @php
                                $previewSettings = array_slice($categoryData['settings'], 0, 3);
                            @endphp
                            @foreach($previewSettings as $key => $value)
                                <flux:text size="xs" variant="muted" class="truncate">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                    {{ is_bool($value) ? ($value ? 'Yes' : 'No') : (is_array($value) ? json_encode($value) : $value) }}
                                </flux:text>
                            @endforeach
                            
                            @if(count($categoryData['settings']) > 3)
                                <flux:text size="xs" variant="primary">
                                    +{{ count($categoryData['settings']) - 3 }} more settings
                                </flux:text>
                            @endif
                        </div>
                    @else
                        <flux:separator class="my-3" />
                        <flux:text size="xs" variant="muted">
                            No settings configured yet
                        </flux:text>
                    @endif
                </flux:card>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="text-center py-12">
            <flux:icon name="folder-open" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <flux:heading size="md">No settings categories</flux:heading>
            <flux:text class="mt-2">This domain doesn't have any settings categories yet.</flux:text>
            <flux:button href="{{ route('settings.index') }}" variant="primary" class="mt-6">
                Back to Settings
            </flux:button>
        </flux:card>
    @endif
</flux:container>
@endsection