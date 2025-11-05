<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Billing Cycle & Terms</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure default billing settings</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Default Billing Cycle</flux:label>
                <flux:select name="billing_cycle">
                    <option value="monthly" {{ ($settings['billing_cycle'] ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ ($settings['billing_cycle'] ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="semi-annually" {{ ($settings['billing_cycle'] ?? '') === 'semi-annually' ? 'selected' : '' }}>Semi-Annually</option>
                    <option value="annually" {{ ($settings['billing_cycle'] ?? '') === 'annually' ? 'selected' : '' }}>Annually</option>
                </flux:select>
                <flux:text size="sm" variant="muted">Default billing frequency for new customers</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Payment Terms (days)</flux:label>
                <flux:input 
                    type="number" 
                    name="payment_terms" 
                    value="{{ $settings['payment_terms'] ?? 30 }}" 
                    min="0" 
                    max="365" 
                />
                <flux:text size="sm" variant="muted">Number of days customers have to pay invoices (e.g., Net 30)</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Invoice Numbering</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure invoice number format</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Invoice Prefix</flux:label>
                <flux:input 
                    type="text" 
                    name="invoice_prefix" 
                    value="{{ $settings['invoice_prefix'] ?? 'INV-' }}" 
                    placeholder="INV-" 
                    maxlength="10"
                />
                <flux:text size="sm" variant="muted">Optional prefix for invoice numbers (e.g., INV-2024-0001)</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Starting Invoice Number</flux:label>
                <flux:input 
                    type="number" 
                    name="invoice_starting_number" 
                    value="{{ $settings['invoice_starting_number'] ?? 1 }}" 
                    min="1" 
                />
                <flux:text size="sm" variant="muted">First invoice number (only used for new invoices)</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Late Fees</flux:heading>
        <flux:text variant="muted" class="mb-6">Automatically apply late fees to overdue invoices</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Late Fees</flux:label>
                <flux:switch name="late_fee_enabled" :checked="$settings['late_fee_enabled'] ?? false" />
                <flux:text size="sm" variant="muted">Automatically charge late fees on overdue invoices</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Late Fee Type</flux:label>
                <flux:select name="late_fee_type">
                    <option value="fixed" {{ ($settings['late_fee_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                    <option value="percentage" {{ ($settings['late_fee_type'] ?? '') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Late Fee Amount</flux:label>
                <flux:input 
                    type="number" 
                    name="late_fee_amount" 
                    value="{{ $settings['late_fee_amount'] ?? 0 }}" 
                    min="0" 
                    step="0.01"
                />
                <flux:text size="sm" variant="muted">Amount in dollars (fixed) or percentage of invoice total</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Automation</flux:heading>
        <flux:text variant="muted" class="mb-6">Automate billing and payment processes</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Auto-Charge Enabled</flux:label>
                <flux:switch name="auto_charge_enabled" :checked="$settings['auto_charge_enabled'] ?? false" />
                <flux:text size="sm" variant="muted">Automatically charge saved payment methods when invoices are due</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Send Invoices Automatically</flux:label>
                <flux:switch name="send_invoice_automatically" :checked="$settings['send_invoice_automatically'] ?? true" />
                <flux:text size="sm" variant="muted">Automatically email invoices to customers when generated</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Send Payment Reminders</flux:label>
                <flux:switch name="send_payment_reminders" :checked="$settings['send_payment_reminders'] ?? true" />
                <flux:text size="sm" variant="muted">Automatically send reminders for upcoming and overdue invoices</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Payment Reminders</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure when to send payment reminders</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Remind Before Due Date (days)</flux:label>
                <flux:input 
                    type="text" 
                    name="reminder_days_before" 
                    value="{{ is_array($settings['reminder_days_before'] ?? null) ? implode(', ', $settings['reminder_days_before']) : '7, 3, 1' }}" 
                    placeholder="7, 3, 1"
                />
                <flux:text size="sm" variant="muted">Comma-separated list of days before due date to send reminders</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Remind After Due Date (days)</flux:label>
                <flux:input 
                    type="text" 
                    name="reminder_days_after" 
                    value="{{ is_array($settings['reminder_days_after'] ?? null) ? implode(', ', $settings['reminder_days_after']) : '1, 7, 14, 30' }}" 
                    placeholder="1, 7, 14, 30"
                />
                <flux:text size="sm" variant="muted">Comma-separated list of days after due date to send overdue reminders</flux:text>
            </flux:field>
        </div>
    </flux:card>
</div>
