@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<flux:container>
    <!-- Header Section -->
    <flux:card class="mb-6">
        <div class="flex items-start justify-between">
            <div>
                <flux:heading size="xl">Settings</flux:heading>
                <flux:text class="mt-2">Manage your company configuration and preferences</flux:text>
            </div>
            <flux:button.group>
                <flux:button icon="arrow-down-tray" onclick="exportSettings()">
                    Export Settings
                </flux:button>
                <flux:button icon="arrow-up-tray" onclick="document.getElementById('import-file').click()">
                    Import Settings
                </flux:button>
            </flux:button.group>
        </div>
        
        <form id="import-form" action="{{ route('settings.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
            @csrf
            <input type="file" id="import-file" name="file" accept=".json" onchange="this.form.submit()">
        </form>
    </flux:card>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <flux:toast variant="success" dismissible class="mb-6">
            {{ session('success') }}
        </flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger" dismissible class="mb-6">
            {{ session('error') }}
        </flux:toast>
    @endif

    <!-- Settings Domains Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($domains as $domainKey => $domain)
            @php
                $configured = $summary[$domainKey]['configured'] ?? false;
                $lastUpdated = $summary[$domainKey]['last_updated'] ?? null;
            @endphp
            
            <a href="{{ route('settings.domain.index', $domainKey) }}" class="block">
                <flux:card class="hover:shadow-lg hover:border-blue-300 dark:hover:border-blue-700 transition-all cursor-pointer h-full">
                    <div class="flex items-center justify-between mb-4">
                        <flux:avatar icon="{{ $domain['icon'] ?? 'cog-6-tooth' }}" size="lg" />
                        @if($configured)
                            <flux:badge variant="success" icon="check">Configured</flux:badge>
                        @endif
                    </div>
                    
                    <flux:heading size="md">{{ $domain['name'] }}</flux:heading>
                    <flux:text size="sm" class="mt-1">{{ $domain['description'] }}</flux:text>
                    
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($domain['categories'] as $category)
                            <flux:badge color="zinc">
                                {{ str_replace('_', ' ', ucfirst($category)) }}
                            </flux:badge>
                        @endforeach
                    </div>
                    
                    @if($lastUpdated)
                        <flux:text size="xs" variant="muted" class="mt-4">
                            Last updated {{ $lastUpdated }}
                        </flux:text>
                    @endif
                </flux:card>
            </a>
        @endforeach
    </div>

    <!-- Quick Access Section -->
    <flux:card>
        <flux:heading size="lg">Quick Access</flux:heading>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <a href="{{ route('settings.category.show', ['domain' => 'communication', 'category' => 'email']) }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="envelope" class="w-5 h-5" />
                <span>Email Settings</span>
            </a>
            
            <a href="{{ route('settings.category.show', ['domain' => 'financial', 'category' => 'billing']) }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="credit-card" class="w-5 h-5" />
                <span>Billing</span>
            </a>
            
            <a href="{{ route('settings.permissions.manage') }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="user-group" class="w-5 h-5" />
                <span>Roles & Permissions</span>
            </a>
            
            <a href="{{ route('settings.category.show', ['domain' => 'company', 'category' => 'general']) }}" class="flex items-center gap-2 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="building-office-2" class="w-5 h-5" />
                <span>Company Info</span>
            </a>
        </div>
    </flux:card>
</flux:container>

@push('scripts')
<script>
function exportSettings() {
    window.location.href = '{{ route('settings.export') }}';
}
</script>
@endpush
@endsection