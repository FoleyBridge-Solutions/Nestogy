<x-settings.form-section title="Invoice Settings">
    <x-slot name="icon">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Invoice Prefix
                </label>
                <input type="text" 
                       x-model="formData.invoice_prefix"
                       placeholder="INV-"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Next Invoice Number
                </label>
                <input type="number" 
                       x-model="formData.next_invoice_number"
                       min="1"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Invoice Footer Text
            </label>
            <textarea x-model="formData.invoice_footer"
                      rows="3"
                      class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Thank you for your business!"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Invoice Template
            </label>
            <select x-model="formData.invoice_template"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="default">Default Template</option>
                <option value="modern">Modern Template</option>
                <option value="classic">Classic Template</option>
                <option value="minimal">Minimal Template</option>
            </select>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Invoice Options</h3>
            <div class="space-y-3">
                <x-settings.toggle-switch 
                    model="formData.show_tax_breakdown"
                    label="Show Tax Breakdown on Invoices"
                    description="Display detailed tax calculations on invoice" />
                
                <x-settings.toggle-switch 
                    model="formData.attach_terms"
                    label="Attach Terms & Conditions"
                    description="Include terms and conditions with each invoice" />
                
                <x-settings.toggle-switch 
                    model="formData.auto_send_invoice"
                    label="Automatically Send Invoices"
                    description="Email invoices to clients when created" />
                
                <x-settings.toggle-switch 
                    model="formData.enable_online_payments"
                    label="Enable Online Payments"
                    description="Allow clients to pay invoices online" />
            </div>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Due Date Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Default Due Days
                    </label>
                    <input type="number" 
                           x-model="formData.default_due_days"
                           min="0"
                           placeholder="30"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Days from invoice date until payment is due</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Reminder Days Before Due
                    </label>
                    <input type="number" 
                           x-model="formData.reminder_days_before"
                           min="0"
                           placeholder="7"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Send reminder this many days before due date</p>
                </div>
            </div>
        </div>
    </div>
</x-settings.form-section>