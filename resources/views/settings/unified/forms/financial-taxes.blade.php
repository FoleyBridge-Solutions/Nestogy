<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">General Tax Settings</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure basic tax settings for invoices</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Tax</flux:label>
                <flux:switch name="tax_enabled" :checked="$settings['tax_enabled'] ?? true" />
                <flux:text size="sm" variant="muted">Enable tax calculations on invoices and products</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Tax Name</flux:label>
                <flux:input 
                    type="text" 
                    name="tax_name" 
                    value="{{ $settings['tax_name'] ?? 'Sales Tax' }}" 
                    placeholder="Sales Tax" 
                    maxlength="50"
                />
                <flux:text size="sm" variant="muted">Display name for tax on invoices (e.g., "Sales Tax", "VAT", "GST")</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Tax Rate (%)</flux:label>
                <flux:input 
                    type="number" 
                    name="tax_rate" 
                    value="{{ $settings['tax_rate'] ?? 0 }}" 
                    min="0" 
                    max="100"
                    step="0.001"
                />
                <flux:text size="sm" variant="muted">Default tax rate as a percentage (e.g., 8.25 for 8.25%)</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Tax ID / Registration Number</flux:label>
                <flux:input 
                    type="text" 
                    name="tax_number" 
                    value="{{ $settings['tax_number'] ?? '' }}" 
                    placeholder="XX-XXXXXXX" 
                    maxlength="50"
                />
                <flux:text size="sm" variant="muted">Your business tax ID, VAT number, or sales tax registration number</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Tax Calculation Rules</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure how taxes are calculated and displayed</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Tax Inclusive Pricing</flux:label>
                <flux:switch name="tax_inclusive" :checked="$settings['tax_inclusive'] ?? false" />
                <flux:text size="sm" variant="muted">
                    <strong>Enabled:</strong> Prices include tax (tax-inclusive)<br>
                    <strong>Disabled:</strong> Tax is added on top of prices (tax-exclusive)
                </flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Compound Tax</flux:label>
                <flux:switch name="tax_compound" :checked="$settings['tax_compound'] ?? false" />
                <flux:text size="sm" variant="muted">Calculate tax on top of other taxes (tax-on-tax, common in Canada)</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Apply Tax on Shipping</flux:label>
                <flux:switch name="tax_on_shipping" :checked="$settings['tax_on_shipping'] ?? false" />
                <flux:text size="sm" variant="muted">Include shipping costs when calculating tax</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">VoIP Tax Integration</flux:heading>
        <flux:text variant="muted" class="mb-6">Automatically calculate telecommunications taxes for VoIP services</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable VoIP Tax API</flux:label>
                <flux:switch name="voip_tax_enabled" :checked="$settings['voip_tax_enabled'] ?? false" />
                <flux:text size="sm" variant="muted">Use automated service to calculate federal, state, and local telecom taxes</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>VoIP Tax API Key</flux:label>
                <flux:input 
                    type="password" 
                    name="voip_tax_api_key" 
                    value="{{ $settings['voip_tax_api_key'] ?? '' }}" 
                    placeholder="Enter API key"
                />
                <flux:text size="sm" variant="muted">API key from your VoIP tax calculation service provider</flux:text>
            </flux:field>

            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <flux:icon.information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <flux:text size="sm" class="text-blue-900 dark:text-blue-100 font-medium">About VoIP Taxes</flux:text>
                        <flux:text size="sm" class="text-blue-700 dark:text-blue-300 mt-1">
                            VoIP services are subject to various federal, state, and local telecommunications taxes including:
                            Universal Service Fund (USF), 911 fees, state telecom taxes, and local surcharges. 
                            These taxes vary by jurisdiction and are automatically calculated based on customer location.
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Tax Exemptions</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure tax exemption rules and certificates</flux:text>
        
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                <div>
                    <flux:text size="sm" class="text-amber-900 dark:text-amber-100 font-medium">Tax Exemption Certificates</flux:text>
                    <flux:text size="sm" class="text-amber-700 dark:text-amber-300 mt-1">
                        Tax exemption certificates are managed per customer. You can upload and manage exemption certificates 
                        in the customer details page. Exempt customers will not be charged tax on their invoices.
                    </flux:text>
                </div>
            </div>
        </div>
    </flux:card>
</div>
