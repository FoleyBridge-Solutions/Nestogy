<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Accepted Payment Methods</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure which payment methods you accept from customers</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Accept Credit Cards</flux:label>
                <flux:switch name="accept_credit_cards" :checked="$settings['accept_credit_cards'] ?? true" />
                <flux:text size="sm" variant="muted">Accept credit card payments (Visa, MasterCard, American Express, etc.)</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Accept ACH/Bank Transfers</flux:label>
                <flux:switch name="accept_ach" :checked="$settings['accept_ach'] ?? true" />
                <flux:text size="sm" variant="muted">Accept direct bank transfers (ACH) for lower fees</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Accept Checks</flux:label>
                <flux:switch name="accept_checks" :checked="$settings['accept_checks'] ?? true" />
                <flux:text size="sm" variant="muted">Accept physical or digital check payments</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Accept Cash</flux:label>
                <flux:switch name="accept_cash" :checked="$settings['accept_cash'] ?? true" />
                <flux:text size="sm" variant="muted">Accept cash payments for in-person transactions</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Accept Wire Transfers</flux:label>
                <flux:switch name="accept_wire_transfer" :checked="$settings['accept_wire_transfer'] ?? true" />
                <flux:text size="sm" variant="muted">Accept international wire transfers for large payments</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Payment Processing</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure payment processing fees and limits</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Payment Processing Fee (%)</flux:label>
                <flux:input 
                    type="number" 
                    name="payment_processing_fee" 
                    value="{{ $settings['payment_processing_fee'] ?? 0 }}" 
                    min="0" 
                    max="100"
                    step="0.01"
                />
                <flux:text size="sm" variant="muted">Standard processing fee percentage charged by your payment processor</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Minimum Payment Amount ($)</flux:label>
                <flux:input 
                    type="number" 
                    name="minimum_payment_amount" 
                    value="{{ $settings['minimum_payment_amount'] ?? 0 }}" 
                    min="0" 
                    step="0.01"
                />
                <flux:text size="sm" variant="muted">Minimum amount customers must pay per transaction (0 for no minimum)</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Convenience Fees</flux:heading>
        <flux:text variant="muted" class="mb-6">Pass processing fees to customers as convenience fees</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Convenience Fees</flux:label>
                <flux:switch name="convenience_fee_enabled" :checked="$settings['convenience_fee_enabled'] ?? false" />
                <flux:text size="sm" variant="muted">Charge customers a convenience fee to cover payment processing costs</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Convenience Fee Percentage (%)</flux:label>
                <flux:input 
                    type="number" 
                    name="convenience_fee_percentage" 
                    value="{{ $settings['convenience_fee_percentage'] ?? 0 }}" 
                    min="0" 
                    max="100"
                    step="0.01"
                />
                <flux:text size="sm" variant="muted">Percentage added to payments to cover processing fees (typically 2-3%)</flux:text>
            </flux:field>

            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <flux:icon.information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <flux:text size="sm" class="text-blue-900 dark:text-blue-100 font-medium">Convenience Fee Notice</flux:text>
                        <flux:text size="sm" class="text-blue-700 dark:text-blue-300 mt-1">
                            Convenience fees must comply with card network rules and state laws. Some states restrict or prohibit convenience fees. 
                            Always disclose fees clearly to customers before charging.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>
</div>
