<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Physical Mail Configuration</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure physical mail services like PostGrid or Lob</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Physical Mail</flux:label>
                <flux:switch name="enabled" :checked="$settings['enabled'] ?? false" />
                <flux:text size="sm" variant="muted">Enable physical mail sending capabilities</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Provider</flux:label>
                <flux:select name="provider">
                    <option value="postgrid" {{ ($settings['provider'] ?? 'postgrid') === 'postgrid' ? 'selected' : '' }}>PostGrid</option>
                    <option value="lob" {{ ($settings['provider'] ?? '') === 'lob' ? 'selected' : '' }}>Lob</option>
                </flux:select>
                <flux:text size="sm" variant="muted">Select your physical mail provider</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>API Key</flux:label>
                <flux:input 
                    type="password" 
                    name="api_key" 
                    value="{{ $settings['api_key'] ?? '' }}" 
                    placeholder="Enter your API key" 
                />
                <flux:text size="sm" variant="muted">Your provider's API key (will be encrypted)</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Return Address</flux:heading>
        <flux:text variant="muted" class="mb-6">Default return address for physical mail</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>From Name</flux:label>
                <flux:input 
                    type="text" 
                    name="from_name" 
                    value="{{ $settings['from_name'] ?? '' }}" 
                    placeholder="Company Name" 
                />
            </flux:field>

            <flux:field>
                <flux:label>Address Line 1</flux:label>
                <flux:input 
                    type="text" 
                    name="from_address_line1" 
                    value="{{ $settings['from_address_line1'] ?? '' }}" 
                    placeholder="123 Main St" 
                />
            </flux:field>

            <flux:field>
                <flux:label>City</flux:label>
                <flux:input 
                    type="text" 
                    name="from_city" 
                    value="{{ $settings['from_city'] ?? '' }}" 
                    placeholder="City" 
                />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>State/Province</flux:label>
                    <flux:input 
                        type="text" 
                        name="from_state" 
                        value="{{ $settings['from_state'] ?? '' }}" 
                        placeholder="CA" 
                    />
                </flux:field>

                <flux:field>
                    <flux:label>Postal Code</flux:label>
                    <flux:input 
                        type="text" 
                        name="from_postal_code" 
                        value="{{ $settings['from_postal_code'] ?? '' }}" 
                        placeholder="12345" 
                    />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Country</flux:label>
                <flux:select name="from_country">
                    <option value="US" {{ ($settings['from_country'] ?? 'US') === 'US' ? 'selected' : '' }}>United States</option>
                    <option value="CA" {{ ($settings['from_country'] ?? '') === 'CA' ? 'selected' : '' }}>Canada</option>
                    <option value="GB" {{ ($settings['from_country'] ?? '') === 'GB' ? 'selected' : '' }}>United Kingdom</option>
                    <option value="AU" {{ ($settings['from_country'] ?? '') === 'AU' ? 'selected' : '' }}>Australia</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>
</div>
