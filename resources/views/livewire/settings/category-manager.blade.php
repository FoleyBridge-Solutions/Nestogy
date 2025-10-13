<div>
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Categories</flux:heading>
            <flux:text class="mt-1">Organize products, expenses, tickets, and content across your system</flux:text>
        </div>
        <flux:button wire:click="create" icon="plus">
            New Category
        </flux:button>
    </div>

    {{-- Success/Error Messages --}}
    @if (session('success'))
        <flux:toast variant="success" dismissible class="mb-6">
            {{ session('success') }}
        </flux:toast>
    @endif

    @if (session('error'))
        <flux:toast variant="danger" dismissible class="mb-6">
            {{ session('error') }}
        </flux:toast>
    @endif

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories..."
                    icon="magnifying-glass" />
            </div>
            <div class="w-full md:w-64">
                <flux:select wire:model.live="typeFilter">
                    <option value="all">All Types</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Categories Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Parent</flux:table.column>
                <flux:table.column>Active</flux:table.column>
                <flux:table.column class="text-right">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($categories as $category)
                    <flux:table.row wire:key="category-{{ $category->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @if($category->icon)
                                    <flux:icon name="{{ $category->icon }}" class="size-4" />
                                @endif
                                <span class="font-medium">{{ $category->name }}</span>
                            </div>
                            @if($category->description)
                                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($category->description, 60) }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge style="background-color: {{ $category->getColor() }}20; color: {{ $category->getColor() }}">
                                {{ $category->getTypeLabel() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($category->parent)
                                <span class="text-sm text-gray-600">{{ $category->parent->name }}</span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                wire:click="toggleActive({{ $category->id }})"
                                :checked="$category->is_active" />
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button.group>
                                <flux:button
                                    wire:click="edit({{ $category->id }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square" />
                                <flux:button
                                    wire:click="delete({{ $category->id }})"
                                    wire:confirm="Are you sure you want to delete this category?"
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    class="text-red-600 hover:text-red-700" />
                            </flux:button.group>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon name="folder-open" class="size-12 text-gray-300" />
                                <flux:text class="text-gray-500">
                                    @if($search || $typeFilter !== 'all')
                                        No categories found matching your filters
                                    @else
                                        No categories yet. Create your first category to get started!
                                    @endif
                                </flux:text>
                                @if(!$search && $typeFilter === 'all')
                                    <flux:button wire:click="create" icon="plus" class="mt-2">
                                        Create Category
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if($categories->count() > 0)
            <div class="mt-4 px-6 pb-4 text-sm text-gray-600">
                Showing {{ $categories->count() }} {{ Str::plural('category', $categories->count()) }}
            </div>
        @endif
    </flux:card>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <form wire:submit="save">
            <flux:heading size="lg" class="mb-6">
                {{ $editing ? 'Edit Category' : 'New Category' }}
            </flux:heading>

            <div class="space-y-4">
                {{-- Name --}}
                <flux:input
                    wire:model="form.name"
                    label="Name"
                    placeholder="e.g., Hardware, Software, Office Supplies"
                    required />

                {{-- Type --}}
                <flux:select
                    wire:model.live="form.type"
                    label="Type"
                    required>
                    <option value="">Select a type...</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                {{-- Parent Category --}}
                <flux:select
                    wire:model="form.parent_id"
                    label="Parent Category"
                    description="Optional: Create a hierarchical structure">
                    <option value="">None (Top Level)</option>
                    @foreach($parentOptions as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }} ({{ $parent->getTypeLabel() }})</option>
                    @endforeach
                </flux:select>

                {{-- Description --}}
                <flux:textarea
                    wire:model="form.description"
                    label="Description"
                    placeholder="Optional description for this category"
                    rows="3" />

                {{-- Color and Icon --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="form.color"
                        type="color"
                        label="Color"
                        description="Used for visual identification" />

                    <flux:input
                        wire:model="form.icon"
                        label="Icon"
                        placeholder="e.g., folder, computer-desktop"
                        description="Heroicon name" />
                </div>

                {{-- Expense Category Metadata --}}
                @if($form['type'] === 'expense_category')
                    <div class="border-t pt-4 mt-4">
                        <flux:heading size="sm" class="mb-4">Expense Settings</flux:heading>

                        <div class="space-y-3">
                            <flux:checkbox
                                wire:model="form.metadata.requires_approval"
                                label="Requires approval for expenses" />

                            @if(!empty($form['metadata']['requires_approval']))
                                <flux:input
                                    wire:model="form.metadata.approval_limit"
                                    type="number"
                                    step="0.01"
                                    label="Approval limit"
                                    prefix="$"
                                    description="Expenses above this amount require approval" />
                            @endif

                            <flux:checkbox
                                wire:model="form.metadata.is_billable_default"
                                label="Billable by default" />

                            @if(!empty($form['metadata']['is_billable_default']))
                                <flux:input
                                    wire:model="form.metadata.markup_percentage_default"
                                    type="number"
                                    step="0.01"
                                    label="Default markup percentage"
                                    suffix="%"
                                    description="Default markup when billing clients" />
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Active Status --}}
                <flux:checkbox
                    wire:model="form.is_active"
                    label="Active"
                    description="Inactive categories won't appear in selection lists" />
            </div>

            {{-- Modal Actions --}}
            <div class="mt-6 flex gap-2 justify-end">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editing ? 'Update' : 'Create' }} Category
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
