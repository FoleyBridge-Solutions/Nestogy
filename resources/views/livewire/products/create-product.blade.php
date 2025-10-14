<form wire:submit="save">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content - 2/3 width -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <flux:card>
                <div>
                    <flux:heading size="lg">Basic Information</flux:heading>
                    <flux:subheading>Core details about your {{ $type }}</flux:subheading>
                </div>

                <div class="space-y-6">
                    <!-- Name and SKU -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <flux:input
                                wire:model="name"
                                label="Product Name"
                                placeholder="Enter {{ $type }} name"
                                required />
                        </div>

                        <flux:field>
                            <flux:label>SKU</flux:label>
                            <flux:input
                                wire:model="sku"
                                placeholder="Auto-generated" />
                            <flux:description>Leave empty to auto-generate</flux:description>
                        </flux:field>
                    </div>

                    <!-- Description -->
                    <flux:textarea
                        wire:model="description"
                        label="Description"
                        placeholder="Detailed description of the {{ $type }}..."
                        rows="4" />

                    <!-- Type, Category, Unit Type -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label>Type</flux:label>
                            <div class="flex items-center px-3 py-2 bg-zinc-700 rounded-lg border border-zinc-600">
                                @if($type === 'service')
                                    <flux:icon.computer-desktop class="size-5 text-blue-500 mr-2" />
                                    <span class="text-sm font-medium text-white">Service</span>
                                @else
                                    <flux:icon.cube class="size-5 text-green-500 mr-2" />
                                    <span class="text-sm font-medium text-white">Product</span>
                                @endif
                            </div>
                        </flux:field>

                        <flux:field>
                            <flux:label>Category</flux:label>
                            <flux:select wire:model="category_id" placeholder="Select a category" required>
                                @foreach($categories as $category)
                                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>Unit Type</flux:label>
                            <flux:select wire:model="unit_type" required>
                                <flux:select.option value="units">Units</flux:select.option>
                                <flux:select.option value="hours">Hours</flux:select.option>
                                <flux:select.option value="days">Days</flux:select.option>
                                <flux:select.option value="weeks">Weeks</flux:select.option>
                                <flux:select.option value="months">Months</flux:select.option>
                                <flux:select.option value="years">Years</flux:select.option>
                                <flux:select.option value="fixed">Fixed</flux:select.option>
                                <flux:select.option value="subscription">Subscription</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    <!-- Short Description -->
                    <flux:field>
                        <flux:label>Short Description</flux:label>
                        <flux:textarea
                            wire:model="short_description"
                            placeholder="Brief summary for previews and listings (max 500 characters)"
                            maxlength="500"
                            rows="2" />
                        <flux:description>Used in summaries and previews (max 500 characters)</flux:description>
                    </flux:field>
                </div>
            </flux:card>

            <!-- Pricing Configuration -->
            <flux:card>
                <div>
                    <flux:heading size="lg">Pricing Configuration</flux:heading>
                    <flux:subheading>Set pricing and billing details</flux:subheading>
                </div>

                <div class="space-y-6">
                    <!-- Pricing Fields - 2x2 Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Currency</flux:label>
                            <flux:select wire:model="currency_code">
                                <flux:select.option value="USD">USD</flux:select.option>
                                <flux:select.option value="EUR">EUR</flux:select.option>
                                <flux:select.option value="GBP">GBP</flux:select.option>
                                <flux:select.option value="CAD">CAD</flux:select.option>
                            </flux:select>
                        </flux:field>

                        <flux:input
                            wire:model="base_price"
                            label="Base Price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            required />

                        <flux:input
                            wire:model="cost"
                            label="Cost"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00" />

                        <flux:field>
                            <flux:label>Pricing Model</flux:label>
                            <flux:select wire:model="pricing_model" required>
                                <flux:select.option value="fixed">Fixed Price</flux:select.option>
                                <flux:select.option value="tiered">Tiered Pricing</flux:select.option>
                                <flux:select.option value="volume">Volume Discount</flux:select.option>
                                <flux:select.option value="usage">Usage Based</flux:select.option>
                                <flux:select.option value="value">Value Based</flux:select.option>
                                <flux:select.option value="custom">Custom</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    <flux:separator />

                    <!-- Billing Configuration -->
                    <div class="space-y-4">
                        <flux:heading size="base">Billing Configuration</flux:heading>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label>Billing Model</flux:label>
                                <flux:select wire:model="billing_model" required>
                                    <flux:select.option value="one_time">One Time</flux:select.option>
                                    <flux:select.option value="subscription">Subscription</flux:select.option>
                                    <flux:select.option value="usage_based">Usage Based</flux:select.option>
                                    <flux:select.option value="hybrid">Hybrid</flux:select.option>
                                </flux:select>
                            </flux:field>

                            <flux:field>
                                <flux:label>Billing Cycle</flux:label>
                                <flux:select wire:model="billing_cycle" required>
                                    <flux:select.option value="one_time">One Time</flux:select.option>
                                    <flux:select.option value="hourly">Hourly</flux:select.option>
                                    <flux:select.option value="daily">Daily</flux:select.option>
                                    <flux:select.option value="weekly">Weekly</flux:select.option>
                                    <flux:select.option value="monthly">Monthly</flux:select.option>
                                    <flux:select.option value="quarterly">Quarterly</flux:select.option>
                                    <flux:select.option value="semi_annually">Semi-Annually</flux:select.option>
                                    <flux:select.option value="annually">Annually</flux:select.option>
                                </flux:select>
                            </flux:field>

                            <flux:input
                                wire:model="billing_interval"
                                label="Billing Interval"
                                type="number"
                                min="1" />
                        </div>
                    </div>

                    <flux:separator />

                    <!-- Tax Settings -->
                    <div class="space-y-4">
                        <flux:heading size="base">Tax & Discount Settings</flux:heading>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <flux:checkbox
                                    wire:model="is_taxable"
                                    label="Taxable" />

                                <div>
                                    <flux:checkbox
                                        wire:model="tax_inclusive"
                                        label="Tax Inclusive Pricing" />
                                    <flux:description class="ml-6">Price includes tax</flux:description>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <flux:checkbox
                                    wire:model="allow_discounts"
                                    label="Allow Discounts" />

                                <div>
                                    <flux:checkbox
                                        wire:model="requires_approval"
                                        label="Requires Approval" />
                                    <flux:description class="ml-6">Manager approval needed for orders</flux:description>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Sidebar - 1/3 width -->
        <div class="space-y-6">
            <!-- Status & Settings -->
            <flux:card>
                <div>
                    <flux:heading size="lg">Status & Settings</flux:heading>
                    <flux:subheading>Product visibility and ordering</flux:subheading>
                </div>

                <div class="space-y-4">
                    <div>
                        <flux:checkbox
                            wire:model="is_active"
                            label="Active" />
                        <flux:description class="ml-6">Product is available for selection</flux:description>
                    </div>

                    <div>
                        <flux:checkbox
                            wire:model="is_featured"
                            label="Featured Product" />
                        <flux:description class="ml-6">Highlight in product lists</flux:description>
                    </div>

                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input
                            wire:model="sort_order"
                            type="number"
                            min="0" />
                        <flux:description>Lower numbers appear first</flux:description>
                    </flux:field>
                </div>
            </flux:card>

            <!-- Inventory Management -->
            <flux:card>
                <div>
                    <flux:heading size="lg">Inventory Management</flux:heading>
                    <flux:subheading>Stock tracking and levels</flux:subheading>
                </div>

                <div class="space-y-4">
                    <div>
                        <flux:checkbox
                            wire:model.live="track_inventory"
                            label="Track Inventory" />
                        <flux:description class="ml-6">Monitor stock levels for this product</flux:description>
                    </div>

                    <!-- Inventory Fields (shown when tracking is enabled) -->
                    @if($track_inventory)
                        <div class="space-y-4 pt-4 border-t border-zinc-700">
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="current_stock"
                                    label="Current Stock"
                                    type="number"
                                    min="0" />

                                <flux:input
                                    wire:model="min_stock_level"
                                    label="Min Stock"
                                    type="number"
                                    min="0" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="reorder_level"
                                    label="Reorder Level"
                                    type="number"
                                    min="0" />

                                <flux:input
                                    wire:model="max_quantity_per_order"
                                    label="Max Per Order"
                                    type="number"
                                    min="1" />
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Actions -->
            <flux:card>
                <div class="space-y-4">
                    <flux:button
                        type="submit"
                        variant="primary"
                        class="w-full justify-center"
                        wire:loading.attr="disabled"
                        wire:target="save">
                        <flux:icon.check wire:loading.remove wire:target="save" class="size-4" />
                        <div wire:loading wire:target="save" class="animate-spin">
                            <flux:icon.arrow-path class="size-4" />
                        </div>
                        <span wire:loading.remove wire:target="save">Create Product</span>
                        <span wire:loading wire:target="save">Creating...</span>
                    </flux:button>

                    <flux:button
                        href="{{ route('products.index') }}"
                        variant="ghost"
                        class="w-full justify-center">
                        <flux:icon.x-mark class="size-4" />
                        Cancel
                    </flux:button>

                    <flux:separator />

                    <div class="flex items-center text-sm text-zinc-400">
                        <flux:icon.information-circle class="size-4 mr-1.5" />
                        <span>Fields marked with <span class="text-red-500">*</span> are required</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</form>
