<div class="space-y-6">
    <flux:card>
        <flux:heading size="lg">Permission Matrix</flux:heading>
        <flux:text class="mt-2 mb-4">View and manage permissions across all roles in a grid view</flux:text>
        
        {{-- Filters --}}
        <div class="flex gap-3 mb-6">
            <flux:input 
                icon="magnifying-glass" 
                placeholder="Search permissions..." 
                class="flex-1"
                wire:model.live.debounce.300ms="searchTerm"
            />
            <flux:select placeholder="All Domains" wire:model.live="filterDomain" class="w-48">
                <flux:select.option value="">All Domains</flux:select.option>
                @foreach(array_keys($abilitiesByCategory) as $domain)
                    <flux:select.option value="{{ $domain }}">{{ $domain }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Matrix Table --}}
        <div class="overflow-x-auto">
            <div class="min-w-full inline-block align-middle">
                <div class="border rounded-lg overflow-hidden">
                    {{-- Table Header with Role Names --}}
                    <div class="bg-zinc-50 dark:bg-zinc-900 border-b sticky top-0 z-10">
                        <div class="flex">
                            <div class="w-64 px-4 py-3 font-medium text-sm flex items-center border-r">
                                Permission
                            </div>
                            @foreach($roles as $role)
                                <div class="flex-1 min-w-[120px] px-4 py-3 text-center border-r last:border-r-0">
                                    <flux:text class="font-medium block">{{ $role->title ?? ucfirst($role->name) }}</flux:text>
                                    <flux:badge color="blue" size="sm" class="mt-1">{{ $role->users_count }}</flux:badge>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Table Body --}}
                    <div>
                        @forelse($matrix as $category => $abilities)
                            {{-- Category Header --}}
                            <div class="bg-zinc-100 dark:bg-zinc-800 border-b">
                                <button
                                    wire:click="toggleCategory('{{ $category }}')"
                                    class="w-full flex items-center justify-between px-4 py-2 text-left hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        <flux:icon 
                                            name="{{ in_array($category, $expandedCategories) ? 'chevron-down' : 'chevron-right' }}" 
                                            variant="micro" 
                                        />
                                        <flux:text class="font-semibold">{{ $category }}</flux:text>
                                        <flux:badge color="zinc" size="sm">{{ count($abilities) }}</flux:badge>
                                    </div>
                                </button>
                            </div>

                            {{-- Category Permissions --}}
                            @if(in_array($category, $expandedCategories))
                                @foreach($abilities as $abilityName => $abilityData)
                                    <div class="flex border-b hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                        <div class="w-64 px-4 py-3 border-r">
                                            <flux:text size="sm" class="font-medium">{{ $abilityData['title'] }}</flux:text>
                                            <flux:text size="xs" variant="muted" class="block mt-0.5">{{ $abilityName }}</flux:text>
                                        </div>
                                        @foreach($roles as $role)
                                            <div class="flex-1 min-w-[120px] px-4 py-3 flex items-center justify-center border-r last:border-r-0">
                                                @if(in_array($role->name, ['super-admin']))
                                                    <flux:icon name="check-circle" class="text-green-600" />
                                                @else
                                                    <flux:checkbox 
                                                        wire:click="togglePermission('{{ $role->name }}', '{{ $abilityName }}')"
                                                        :checked="$abilityData['roles'][$role->name] ?? false"
                                                    />
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        @empty
                            <div class="px-4 py-8 text-center">
                                <flux:text variant="muted">No permissions found matching your criteria</flux:text>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex items-center gap-4 text-sm">
            <flux:text variant="muted">Legend:</flux:text>
            <div class="flex items-center gap-2">
                <flux:checkbox checked disabled />
                <flux:text size="sm">Permission granted</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:checkbox disabled />
                <flux:text size="sm">Permission denied</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" variant="micro" class="text-green-600" />
                <flux:text size="sm">Always granted (super-admin)</flux:text>
            </div>
        </div>
    </flux:card>
</div>
