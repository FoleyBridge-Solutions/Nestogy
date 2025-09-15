<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg" class="flex items-center">
            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            MSP Business Settings
        </flux:heading>
        <flux:text class="mt-2">
            Configure billing rates, business hours, and other MSP-specific settings.
        </flux:text>
    </div>

    <div class="space-y-8">
        <!-- Business Hours -->
        <div>
            <flux:heading size="sm" class="mb-4">Business Hours</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input 
                    wire:model.defer="business_hours_start" 
                    type="time"
                    label="Start Time" />

                <flux:input 
                    wire:model.defer="business_hours_end" 
                    type="time"
                    label="End Time" />
            </div>
        </div>

        <!-- Hourly Billing Rates -->
        <div>
            <flux:heading size="sm" class="mb-4">Hourly Billing Rates ($)</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <flux:input 
                    wire:model.defer="rate_standard" 
                    type="number"
                    min="0"
                    step="0.01"
                    label="Standard Rate"
                    placeholder="150.00" />

                <flux:input 
                    wire:model.defer="rate_after_hours" 
                    type="number"
                    min="0"
                    step="0.01"
                    label="After Hours Rate"
                    placeholder="225.00" />

                <flux:input 
                    wire:model.defer="rate_emergency" 
                    type="number"
                    min="0"
                    step="0.01"
                    label="Emergency Rate"
                    placeholder="300.00" />

                <flux:input 
                    wire:model.defer="rate_weekend" 
                    type="number"
                    min="0"
                    step="0.01"
                    label="Weekend Rate"
                    placeholder="200.00" />

                <flux:input 
                    wire:model.defer="rate_holiday" 
                    type="number"
                    min="0"
                    step="0.01"
                    label="Holiday Rate"
                    placeholder="250.00" />
            </div>
        </div>

        <!-- Time Tracking Settings -->
        <div>
            <flux:heading size="sm" class="mb-4">Time Tracking Settings</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:select 
                    wire:model.defer="minimum_billing_increment" 
                    label="Minimum Billing Increment (Hours)">
                    <flux:select.option value="0.25">15 minutes (0.25 hours)</flux:select.option>
                    <flux:select.option value="0.5">30 minutes (0.5 hours)</flux:select.option>
                    <flux:select.option value="1">1 hour</flux:select.option>
                </flux:select>

                <flux:select 
                    wire:model.defer="time_rounding_method" 
                    label="Time Rounding Method">
                    <flux:select.option value="nearest">Round to Nearest</flux:select.option>
                    <flux:select.option value="up">Round Up</flux:select.option>
                    <flux:select.option value="down">Round Down</flux:select.option>
                </flux:select>
            </div>
        </div>

        <!-- Ticket Settings -->
        <div>
            <flux:heading size="sm" class="mb-4">Ticket Settings</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input 
                    wire:model.defer="ticket_prefix" 
                    label="Ticket Number Prefix"
                    placeholder="TKT-"
                    maxlength="10" />

                <flux:input 
                    wire:model.defer="ticket_autoclose_hours" 
                    type="number"
                    min="1"
                    max="8760"
                    label="Auto-close Tickets After (Hours)"
                    placeholder="72" />
            </div>
        </div>

        <!-- Invoice Settings -->
        <div>
            <flux:heading size="sm" class="mb-4">Invoice Settings</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:input 
                    wire:model.defer="invoice_prefix" 
                    label="Invoice Number Prefix"
                    placeholder="INV-"
                    maxlength="10" />

                <flux:input 
                    wire:model.defer="invoice_starting_number" 
                    type="number"
                    min="1"
                    label="Starting Number"
                    placeholder="1000" />

                <flux:input 
                    wire:model.defer="invoice_late_fee_percent" 
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    label="Late Fee Percentage"
                    placeholder="1.5" />
            </div>
        </div>
    </div>
</flux:card>