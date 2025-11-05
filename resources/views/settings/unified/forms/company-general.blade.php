<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Company Information</flux:heading>
        <flux:text variant="muted" class="mb-6">Basic company information and contact details</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Company Name</flux:label>
                <flux:input name="company_name" value="{{ $settings['company_name'] ?? '' }}" placeholder="Your Company Name" />
            </flux:field>

            <flux:field>
                <flux:label>Website</flux:label>
                <flux:input type="url" name="website" value="{{ $settings['website'] ?? '' }}" placeholder="https://example.com" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" name="email" value="{{ $settings['email'] ?? '' }}" placeholder="contact@example.com" />
            </flux:field>

            <flux:field>
                <flux:label>Phone</flux:label>
                <flux:input type="tel" name="phone" value="{{ $settings['phone'] ?? '' }}" placeholder="+1 (555) 123-4567" />
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Address</flux:heading>
        <flux:text variant="muted" class="mb-6">Company physical address</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Street Address</flux:label>
                <flux:input name="address_line1" value="{{ $settings['address_line1'] ?? '' }}" placeholder="123 Main Street" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input name="city" value="{{ $settings['city'] ?? '' }}" />
                </flux:field>

                <flux:field>
                    <flux:label>State / Province</flux:label>
                    <flux:input name="state" value="{{ $settings['state'] ?? '' }}" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Postal Code</flux:label>
                    <flux:input name="postal_code" value="{{ $settings['postal_code'] ?? '' }}" />
                </flux:field>

                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:input name="country" value="{{ $settings['country'] ?? 'United States' }}" />
                </flux:field>
            </div>
        </div>
    </flux:card>
</div>
