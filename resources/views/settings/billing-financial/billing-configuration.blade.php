<x-settings.form-section title="Billing Preferences">
    <x-slot name="icon">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Billing Cycle
                </label>
                <select x-model="formData.billing_cycle"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="semi-annual">Semi-Annual</option>
                    <option value="annual">Annual</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Payment Terms (Days)
                </label>
                <input type="number" 
                       x-model="formData.payment_terms"
                       min="0"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Late Fee (%)
                </label>
                <input type="number" 
                       x-model="formData.late_fee_percentage"
                       step="0.01"
                       min="0"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Grace Period (Days)
                </label>
                <input type="number" 
                       x-model="formData.grace_period"
                       min="0"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Automated Billing</h3>
            <div class="space-y-3">
                <x-settings.toggle-switch 
                    model="formData.auto_billing_enabled"
                    label="Enable Automatic Billing"
                    description="Automatically charge customers on their billing date" />
                
                <x-settings.toggle-switch 
                    model="formData.auto_retry_failed"
                    label="Automatically Retry Failed Payments"
                    description="Retry failed payments up to 3 times over 7 days" />
                
                <x-settings.toggle-switch 
                    model="formData.send_payment_reminders"
                    label="Send Payment Reminders"
                    description="Send email reminders before payment due dates" />
            </div>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Payment Methods</h3>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.accept_credit_cards"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Credit/Debit Cards</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.accept_ach"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">ACH/Bank Transfer</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.accept_paypal"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">PayPal</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.accept_checks"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Checks</span>
                </label>
            </div>
        </div>
    </div>
</x-settings.form-section>