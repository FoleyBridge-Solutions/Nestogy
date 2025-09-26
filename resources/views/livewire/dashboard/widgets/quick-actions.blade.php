<flux:card class="h-full">
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.bolt class="size-5 text-yellow-500" />
                Quick Actions
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Common tasks and shortcuts
            </flux:text>
        </div>
        
        <!-- Manage Actions Button -->
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" wire:click="openCreateModal">
                <flux:icon.plus class="size-4" />
                Add
            </flux:button>
            <flux:button variant="ghost" size="sm" wire:click="$set('showManageModal', true)">
                <flux:icon.cog-6-tooth class="size-4" />
                Manage
            </flux:button>
        </div>
    </div>
    
    <!-- Actions Grid -->
    @if(!empty($actions))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($actions as $index => $action)
                @php
                    // Determine if this should be a link or button
                    $isLink = false;
                    if (isset($action['custom_id'])) {
                        // Custom actions
                        if ($action['type'] === 'url' || $action['type'] === 'route') {
                            $isLink = true;
                        }
                    } elseif (isset($action['route'])) {
                        // System actions with routes
                        $isLink = true;
                    }
                    $component = $isLink ? 'a' : 'button';
                @endphp
                
                <div class="relative group">
                    <!-- Favorite Star -->
                    <button
                        @if(isset($action['custom_id']))
                            wire:click="toggleFavorite({{ $action['custom_id'] }})"
                        @else
                            wire:click="toggleFavorite('{{ $action['route'] ?? $action['action'] ?? '' }}')"
                        @endif
                        class="absolute -top-1 -right-1 z-10 p-1 rounded-full bg-white dark:bg-zinc-800 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
                        type="button"
                    >
                        @if($action['is_favorite'] ?? false)
                            <flux:icon.star class="size-4 text-yellow-500 fill-current" />
                        @else
                            <flux:icon.star class="size-4 text-zinc-400 hover:text-yellow-500 transition-colors" />
                        @endif
                    </button>
                    
                    <!-- Custom Action Edit/Delete -->
                    @if(isset($action['custom_id']))
                        @php
                            $customAction = \App\Models\CustomQuickAction::find($action['custom_id']);
                            $canEdit = false;
                            if ($customAction) {
                                // User can edit if they created it OR if they're a super-admin and it's a company action
                                $canEdit = $customAction->user_id === auth()->id() || 
                                          (\Bouncer::is(auth()->user())->an('super-admin') && $customAction->visibility === 'company');
                            }
                        @endphp
                        @if($canEdit)
                            <div class="absolute -top-1 -left-1 z-10 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    wire:click="openEditModal({{ $action['custom_id'] }})"
                                    class="p-1 rounded-full bg-white dark:bg-zinc-800 shadow-sm"
                                    type="button"
                                    title="Edit quick action"
                                >
                                    <flux:icon.pencil class="size-3 text-zinc-500 hover:text-blue-500 transition-colors" />
                                </button>
                                <button
                                    wire:click="deleteCustomAction({{ $action['custom_id'] }})"
                                    wire:confirm="Are you sure you want to delete this action?"
                                    class="p-1 rounded-full bg-white dark:bg-zinc-800 shadow-sm"
                                    type="button"
                                    title="Delete quick action"
                                >
                                    <flux:icon.trash class="size-3 text-zinc-500 hover:text-red-500 transition-colors" />
                                </button>
                            </div>
                        @endif
                    @endif
                    
                    <{{ $component }} 
                        @if(isset($action['custom_id']))
                            @if($action['type'] === 'url')
                                href="{{ $action['target'] }}"
                                @if($action['open_in'] === 'new_tab')
                                    target="_blank"
                                @endif
                            @elseif($action['type'] === 'route')
                                @php
                                    try {
                                        $customRouteUrl = route($action['target'], $action['parameters'] ?? []);
                                        $canAccess = true;
                                    } catch (\Exception $e) {
                                        $customRouteUrl = '#';
                                        $canAccess = false;
                                    }
                                @endphp
                                @if($canAccess)
                                    href="{{ $customRouteUrl }}"
                                    wire:click="recordUsage({{ $action['custom_id'] }})"
                                    @if($action['open_in'] === 'new_tab')
                                        target="_blank"
                                    @endif
                                @else
                                    wire:click="$dispatch('notify', { type: 'error', message: 'Route not available: {{ $action['target'] }}' })"
                                    type="button"
                                @endif
                            @else
                                wire:click="executeAction(null, {{ $action['custom_id'] }})"
                                type="button"
                            @endif
                        @elseif(isset($action['route']))
                            @php
                                try {
                                    $routeUrl = route($action['route']);
                                } catch (\Exception $e) {
                                    $routeUrl = '#';
                                }
                            @endphp
                            @if($routeUrl !== '#')
                                href="{{ $routeUrl }}"
                            @else
                                wire:click="$dispatch('notify', { type: 'error', message: 'This action is not available' })"
                                type="button"
                            @endif
                        @elseif(isset($action['action']))
                            wire:click="executeAction('{{ $action['action'] }}')"
                            type="button"
                        @endif
                        class="block w-full p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:shadow-md hover:border-{{ $action['color'] }}-300 dark:hover:border-{{ $action['color'] }}-600 transition-all duration-200 text-left {{ ($action['is_favorite'] ?? false) ? 'ring-2 ring-yellow-300 dark:ring-yellow-600' : '' }}"
                        wire:key="action-{{ $index }}"
                    >
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 p-2 rounded-lg transition-colors
                            @switch($action['color'])
                                @case('blue')
                                    bg-blue-100 dark:bg-blue-900/30 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50
                                    @break
                                @case('green')
                                    bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50
                                    @break
                                @case('purple')
                                    bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50
                                    @break
                                @case('orange')
                                    bg-orange-100 dark:bg-orange-900/30 group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50
                                    @break
                                @case('red')
                                    bg-red-100 dark:bg-red-900/30 group-hover:bg-red-200 dark:group-hover:bg-red-900/50
                                    @break
                                @case('yellow')
                                    bg-yellow-100 dark:bg-yellow-900/30 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50
                                    @break
                                @default
                                    bg-zinc-100 dark:bg-zinc-900/30 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-900/50
                            @endswitch
                        ">
                            @php
                                $iconColorClass = match($action['color']) {
                                    'blue' => 'text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300',
                                    'green' => 'text-green-600 dark:text-green-400 group-hover:text-green-700 dark:group-hover:text-green-300',
                                    'purple' => 'text-purple-600 dark:text-purple-400 group-hover:text-purple-700 dark:group-hover:text-purple-300',
                                    'orange' => 'text-orange-600 dark:text-orange-400 group-hover:text-orange-700 dark:group-hover:text-orange-300',
                                    'red' => 'text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300',
                                    'yellow' => 'text-yellow-600 dark:text-yellow-400 group-hover:text-yellow-700 dark:group-hover:text-yellow-300',
                                    default => 'text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'
                                };
                            @endphp
                            
                            <x-heroicon :name="$action['icon'] ?? 'bolt'" class="size-5 transition-colors {{ $iconColorClass }}" />
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            @php
                                $headingHoverClass = match($action['color']) {
                                    'blue' => 'group-hover:text-blue-600',
                                    'green' => 'group-hover:text-green-600',
                                    'purple' => 'group-hover:text-purple-600',
                                    'orange' => 'group-hover:text-orange-600',
                                    'red' => 'group-hover:text-red-600',
                                    'yellow' => 'group-hover:text-yellow-600',
                                    default => 'group-hover:text-zinc-600'
                                };
                                $arrowHoverClass = match($action['color']) {
                                    'blue' => 'group-hover:text-blue-500',
                                    'green' => 'group-hover:text-green-500',
                                    'purple' => 'group-hover:text-purple-500',
                                    'orange' => 'group-hover:text-orange-500',
                                    'red' => 'group-hover:text-red-500',
                                    'yellow' => 'group-hover:text-yellow-500',
                                    default => 'group-hover:text-zinc-500'
                                };
                            @endphp
                            <flux:heading size="sm" class="{{ $headingHoverClass }} transition-colors">
                                {{ $action['title'] }}
                            </flux:heading>
                            <flux:text size="xs" class="text-zinc-500 mt-1 line-clamp-2">
                                {{ $action['description'] }}
                            </flux:text>
                        </div>
                        
                        <!-- Arrow -->
                        <flux:icon.chevron-right class="size-4 text-zinc-400 {{ $arrowHoverClass }} transition-colors" />
                    </div>
                    </{{ $component }}>
                </div>
            @endforeach
        </div>
        
        <!-- Additional Actions -->
        <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:text size="sm" class="text-zinc-500">
                    Need help? Visit our documentation
                </flux:text>
                
                <flux:button variant="ghost" size="sm" onclick="document.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', metaKey: true, bubbles: true}))">
                    <flux:icon.command-line class="size-4" />
                    ⌘K
                </flux:button>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="flex items-center justify-center h-32">
            <div class="text-center">
                <flux:icon.bolt class="size-8 text-zinc-300 mx-auto mb-2" />
                <flux:text class="text-zinc-500">No quick actions available</flux:text>
            </div>
        </div>
    @endif
    
    <!-- Create/Edit Modal -->
    <flux:modal wire:model="showCreateModal" class="max-w-lg">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ $actionForm['id'] ? 'Edit' : 'Create' }} Quick Action</flux:heading>
                
                <!-- Icon Preview -->
                @if($actionForm['icon'])
                    <div class="p-3 rounded-lg bg-{{ $actionForm['color'] }}-100 dark:bg-{{ $actionForm['color'] }}-900/30">
                        <x-heroicon :name="$actionForm['icon']" class="size-6 text-{{ $actionForm['color'] }}-600 dark:text-{{ $actionForm['color'] }}-400" />
                    </div>
                @endif
            </div>
            
            <div class="space-y-4">
                <!-- Title -->
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="actionForm.title" placeholder="e.g., Create Invoice" maxlength="50" />
                    <flux:error name="actionForm.title" />
                </flux:field>
                
                <!-- Description -->
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:input wire:model="actionForm.description" placeholder="Brief description of the action" maxlength="255" />
                    <flux:error name="actionForm.description" />
                </flux:field>
                
                <!-- Icon and Color -->
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Icon</flux:label>
                        <flux:select wire:model="actionForm.icon" variant="listbox" searchable placeholder="Search icons...">
                            @php
                                $icons = [
                                    'academic-cap', 'adjustments-horizontal', 'adjustments-vertical', 'archive-box', 
                                    'arrow-down', 'arrow-left', 'arrow-path', 'arrow-right', 'arrow-up',
                                    'arrow-down-tray', 'arrow-up-tray', 'arrow-trending-up', 'arrow-trending-down',
                                    'at-symbol', 'banknotes', 'bars-3', 'beaker', 'bell', 'bell-alert',
                                    'bolt', 'book-open', 'bookmark', 'briefcase', 'bug-ant',
                                    'building-library', 'building-office', 'building-office-2', 'building-storefront',
                                    'cake', 'calculator', 'calendar', 'calendar-days', 'camera',
                                    'chart-bar', 'chart-pie', 'chat-bubble-left', 'chat-bubble-oval-left',
                                    'check', 'check-circle', 'check-badge', 'chevron-down', 'chevron-right',
                                    'clipboard', 'clipboard-document', 'clipboard-document-check', 'clock', 'cloud',
                                    'code-bracket', 'cog', 'cog-6-tooth', 'cog-8-tooth', 'command-line',
                                    'computer-desktop', 'cpu-chip', 'credit-card', 'cube', 'currency-dollar',
                                    'cursor-arrow-rays', 'device-phone-mobile', 'document', 'document-plus', 
                                    'document-text', 'envelope', 'envelope-open', 'exclamation-circle', 
                                    'exclamation-triangle', 'eye', 'face-smile', 'film', 'fire', 'flag',
                                    'folder', 'folder-open', 'funnel', 'gift', 'globe-alt',
                                    'hand-raised', 'hand-thumb-up', 'heart', 'home', 'identification',
                                    'inbox', 'information-circle', 'key', 'light-bulb', 'link',
                                    'list-bullet', 'lock-closed', 'lock-open', 'magnifying-glass', 'map',
                                    'megaphone', 'microphone', 'moon', 'newspaper', 'paper-airplane',
                                    'paper-clip', 'pencil', 'pencil-square', 'phone', 'photo',
                                    'play', 'plus', 'plus-circle', 'power', 'presentation-chart-bar',
                                    'printer', 'puzzle-piece', 'qr-code', 'question-mark-circle', 'radio',
                                    'receipt-percent', 'rocket-launch', 'scale', 'server', 'server-stack',
                                    'share', 'shield-check', 'shopping-bag', 'shopping-cart', 'signal',
                                    'sparkles', 'speaker-wave', 'square-3-stack-3d', 'star', 'sun',
                                    'table-cells', 'tag', 'ticket', 'trash', 'trophy', 'truck',
                                    'user', 'user-circle', 'user-group', 'user-plus', 'users',
                                    'video-camera', 'wallet', 'wifi', 'wrench', 'wrench-screwdriver',
                                    'x-circle', 'x-mark'
                                ];
                                sort($icons);
                            @endphp
                            @foreach($icons as $icon)
                                <flux:select.option value="{{ $icon }}">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon :name="$icon" class="size-4 text-zinc-500" />
                                        <span>{{ ucwords(str_replace('-', ' ', $icon)) }}</span>
                                    </div>
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="actionForm.icon" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Color</flux:label>
                        <flux:select wire:model="actionForm.color">
                            <flux:select.option value="blue">Blue</flux:select.option>
                            <flux:select.option value="green">Green</flux:select.option>
                            <flux:select.option value="purple">Purple</flux:select.option>
                            <flux:select.option value="orange">Orange</flux:select.option>
                            <flux:select.option value="red">Red</flux:select.option>
                            <flux:select.option value="yellow">Yellow</flux:select.option>
                            <flux:select.option value="gray">Gray</flux:select.option>
                        </flux:select>
                        <flux:error name="actionForm.color" />
                    </flux:field>
                </div>
                
                <!-- Action Type -->
                <flux:field>
                    <flux:label>Action Type</flux:label>
                    <flux:radio.group wire:model.live="actionForm.type">
                        <flux:radio value="route" label="Internal Page (Route)" />
                        <flux:radio value="url" label="External URL" />
                    </flux:radio.group>
                    <flux:error name="actionForm.type" />
                </flux:field>
                
                <!-- Target -->
                <flux:field>
                    <flux:label>{{ $actionForm['type'] === 'url' ? 'URL' : 'Route Name' }}</flux:label>
                    @if($actionForm['type'] === 'url')
                        <flux:input wire:model="actionForm.target" placeholder="https://example.com" type="url" />
                    @else
                        <flux:input wire:model="actionForm.target" placeholder="e.g., financial.invoices.create" />
                        <flux:text size="xs" class="text-zinc-500 mt-1">
                            Examples: tickets.create, clients.create, financial.invoices.create, assets.index
                        </flux:text>
                    @endif
                    <flux:error name="actionForm.target" />
                </flux:field>
                
                <!-- Open In -->
                <flux:field>
                    <flux:label>Open In</flux:label>
                    <flux:radio.group wire:model="actionForm.open_in">
                        <flux:radio value="same_tab" label="Same Tab" />
                        <flux:radio value="new_tab" label="New Tab" />
                    </flux:radio.group>
                    <flux:error name="actionForm.open_in" />
                </flux:field>
                
                <!-- Visibility -->
                <flux:field>
                    <flux:label>Visibility</flux:label>
                    <flux:radio.group wire:model="actionForm.visibility">
                        <flux:radio value="private" label="Private (Only Me)" />
                        <flux:radio value="company" label="Company (Everyone in Company)" />
                    </flux:radio.group>
                    <flux:error name="actionForm.visibility" />
                </flux:field>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="saveCustomAction">
                    {{ $actionForm['id'] ? 'Update' : 'Create' }} Action
                </flux:button>
            </div>
        </div>
    </flux:modal>
    
    <!-- Manage Actions Modal -->
    <flux:modal wire:model="showManageModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Manage Quick Actions</flux:heading>
            </div>
            
            <div class="space-y-4">
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                    Customize your quick actions by adding new ones or managing existing custom actions.
                    You can also favorite actions to pin them to the top.
                </flux:text>
                
                @if(count($customActions) > 0)
                    <div>
                        <flux:heading size="sm" class="mb-3">Your Custom Actions</flux:heading>
                        <div class="space-y-2">
                            @foreach($customActions as $action)
                                <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/30">
                                            <x-heroicon :name="$action['icon'] ?? 'bolt'" class="size-4 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" />
                                        </div>
                                        <div>
                                            <flux:text weight="medium">{{ $action['title'] }}</flux:text>
                                            <flux:text size="xs" class="text-zinc-500">{{ $action['description'] }}</flux:text>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <flux:button variant="ghost" size="sm" wire:click="openEditModal({{ $action['custom_id'] }})">
                                            <flux:icon.pencil class="size-4" />
                                        </flux:button>
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm" 
                                            wire:click="deleteCustomAction({{ $action['custom_id'] }})"
                                            wire:confirm="Are you sure you want to delete this action?"
                                        >
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <flux:card class="text-center py-8">
                        <flux:icon.bolt class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:text>You haven't created any custom actions yet.</flux:text>
                        <flux:text size="sm" class="text-zinc-500 mt-1">
                            Create your first custom action to streamline your workflow.
                        </flux:text>
                    </flux:card>
                @endif
                
                <div>
                    <flux:heading size="sm" class="mb-3">Tips</flux:heading>
                    <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <li class="flex items-start gap-2">
                            <flux:icon.star class="size-4 text-yellow-500 mt-0.5 flex-shrink-0" />
                            <span>Click the star icon on any action to favorite it and pin it to the top.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <flux:icon.plus-circle class="size-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <span>Create custom actions for your most frequently used tasks.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <flux:icon.user-group class="size-4 text-blue-500 mt-0.5 flex-shrink-0" />
                            <span>Set visibility to "Company" to share actions with your team.</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="openCreateModal">
                    <flux:icon.plus class="size-4" />
                    Create New Action
                </flux:button>
            </div>
        </div>
    </flux:modal>
</flux:card>

@push('scripts')
<script>
    window.addEventListener('open-url', event => {
        window.open(event.detail.url, event.detail.target || '_self');
    });
</script>
@endpush
