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
                <flux:label>Legal Name</flux:label>
                <flux:input name="legal_name" value="{{ $settings['legal_name'] ?? '' }}" placeholder="Legal Company Name" />
            </flux:field>

            <flux:field>
                <flux:label>Business Type</flux:label>
                <flux:select name="business_type">
                    <flux:select.option value="">Select type...</flux:select.option>
                    <flux:select.option value="sole_proprietorship" :selected="($settings['business_type'] ?? '') === 'sole_proprietorship'">Sole Proprietorship</flux:select.option>
                    <flux:select.option value="partnership" :selected="($settings['business_type'] ?? '') === 'partnership'">Partnership</flux:select.option>
                    <flux:select.option value="llc" :selected="($settings['business_type'] ?? '') === 'llc'">LLC</flux:select.option>
                    <flux:select.option value="corporation" :selected="($settings['business_type'] ?? '') === 'corporation'">Corporation</flux:select.option>
                    <flux:select.option value="nonprofit" :selected="($settings['business_type'] ?? '') === 'nonprofit'">Non-Profit</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Tax ID / EIN</flux:label>
                <flux:input name="tax_id" value="{{ $settings['tax_id'] ?? '' }}" placeholder="XX-XXXXXXX" />
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

            <flux:field>
                <flux:label>Address Line 2</flux:label>
                <flux:input name="address_line2" value="{{ $settings['address_line2'] ?? '' }}" placeholder="Suite 100" />
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

    <flux:card>
        <flux:heading size="lg">Social Media</flux:heading>
        <flux:text variant="muted" class="mb-6">Company social media profiles</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>LinkedIn</flux:label>
                <flux:input name="linkedin_url" value="{{ $settings['linkedin_url'] ?? '' }}" placeholder="https://linkedin.com/company/..." />
            </flux:field>

            <flux:field>
                <flux:label>Twitter / X</flux:label>
                <flux:input name="twitter_url" value="{{ $settings['twitter_url'] ?? '' }}" placeholder="https://twitter.com/..." />
            </flux:field>

            <flux:field>
                <flux:label>Facebook</flux:label>
                <flux:input name="facebook_url" value="{{ $settings['facebook_url'] ?? '' }}" placeholder="https://facebook.com/..." />
            </flux:field>
        </div>
    </flux:card>
</div>
