<div>
    <div class="w-full">
        <div class="flex justify-between items-center mb-6">
            <div>
                <flux:heading size="xl">Contract Action Buttons</flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400">Configure custom action buttons for contract management</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:modal.trigger name="action-button-modal">
                    <flux:button variant="primary" icon="plus">
                        Add Action Button
                    </flux:button>
                </flux:modal.trigger>
                <flux:button variant="ghost" icon="sparkles" wire:click="createDefaultButtons">
                    Create Default Buttons
                </flux:button>
            </div>
        </div>

        <flux:card>
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex justify-between items-center">
                    <flux:heading size="lg">Action Buttons</flux:heading>
                    <flux:switch wire:model.live="enableSorting">
                        Enable Sorting
                    </flux:switch>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-2" 
                     @if($enableSorting) wire:sortable="updateSortOrder" @endif>
                    @forelse($actionButtons as $button)
                        <div class="flex justify-between items-start p-4 border rounded-lg dark:border-gray-700" 
                             wire:key="button-{{ $button->id }}"
                             @if($enableSorting) wire:sortable.item="{{ $button->id }}" @endif>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    @if($enableSorting)
                                        <flux:icon name="bars-3" class="text-gray-400 cursor-move" wire:sortable.handle />
                                    @endif
                                    @if($button->icon)
                                        <flux:icon name="{{ $button->icon }}" />
                                    @endif
                                    <flux:heading size="md">{{ $button->label }}</flux:heading>
                                    @if(!$button->is_active)
                                        <flux:badge variant="subtle">Inactive</flux:badge>
                                    @endif
                                </div>
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                                    Type: <code>{{ $button->action_type }}</code> | 
                                    Slug: <code>{{ $button->slug }}</code>
                                    @if($button->permissions)
                                        | Permissions: <code>{{ implode(', ', $button->permissions) }}</code>
                                    @endif
                                </flux:text>
                                @if($button->visibility_conditions)
                                    <div class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                        <flux:icon name="eye" class="w-4 h-4 inline" />
                                        {{ count($button->visibility_conditions) }} visibility condition(s)
                                    </div>
                                @endif
                            </div>
                            <flux:button.group>
                                <flux:button size="sm" variant="ghost" icon="pencil" 
                                            wire:click="editButton({{ $button->id }})" />
                                <flux:button size="sm" variant="ghost" icon="eye" 
                                            wire:click="previewButton({{ $button->id }})" />
                                <flux:button size="sm" variant="ghost" icon="trash" 
                                            wire:click="deleteButton({{ $button->id }})" />
                            </flux:button.group>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <flux:icon name="cursor-arrow-rays" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <flux:heading size="lg" class="text-gray-500 mb-2">No Action Buttons Configured</flux:heading>
                            <flux:text class="text-gray-400 mb-4">Get started by creating default action buttons or adding custom ones.</flux:text>
                            <flux:button variant="primary" icon="sparkles" wire:click="createDefaultButtons">
                                Create Default Buttons
                            </flux:button>
                        </div>
                    @endforelse
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Action Button Modal --}}
    <flux:modal wire:model="showModal" name="action-button-modal" class="max-w-2xl">
        <flux:modal.header>
            <flux:heading>{{ $editingButton ? 'Edit' : 'Add' }} Action Button</flux:heading>
        </flux:modal.header>
        
        <flux:modal.body>
            <!-- Modal content will go here -->
        </flux:modal.body>
        
        <flux:modal.footer>
            <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveButton">Save</flux:button>
        </flux:modal.footer>
    </flux:modal>
</div>
