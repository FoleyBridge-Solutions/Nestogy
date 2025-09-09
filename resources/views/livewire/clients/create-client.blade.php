<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-start">
        <div>
            <flux:heading size="xl">Create {{ $isLead ? 'Lead' : 'Client' }}</flux:heading>
            <flux:text class="mt-1">Add a new {{ $isLead ? 'lead' : 'client' }} to your system</flux:text>
        </div>
        <flux:button variant="ghost" icon="arrow-left" href="{{ route('clients.index', $isLead ? ['lead' => 1] : []) }}">
            Back to {{ $isLead ? 'Leads' : 'Clients' }}
        </flux:button>
    </div>

    <!-- Client Form with Tabs -->
    <form wire:submit="save">
        <flux:tab.group>
            <flux:tabs wire:model="currentTab">
                <flux:tab name="basic">Basic Info</flux:tab>
                <flux:tab name="address">Address</flux:tab>
                <flux:tab name="billing">Billing</flux:tab>
                <flux:tab name="additional">Additional</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="basic">
                <flux:card class="space-y-6">
                    <!-- Client Type Selection -->
                    <div>
                        <flux:heading size="md" class="mb-3">Client Type</flux:heading>
                        <flux:radio.group wire:model.live="type" variant="segmented">
                            <flux:radio value="individual" icon="user" label="Individual" />
                            <flux:radio value="business" icon="building-office" label="Business" />
                        </flux:radio.group>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Basic Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name Field -->
                        <flux:field>
                            <flux:label badge="Required">
                                {{ $type === 'business' ? 'Company Name' : 'Full Name' }}
                            </flux:label>
                            <flux:input 
                                wire:model.blur="name" 
                                :placeholder="$type === 'business' ? 'Acme Corporation' : 'John Doe'"
                                required 
                            />
                            <flux:error name="name" />
                        </flux:field>

                        <!-- Company (for individuals) -->
                        @if($type === 'individual')
                            <flux:field>
                                <flux:label badge="Optional">Company</flux:label>
                                <flux:input 
                                    wire:model.blur="company" 
                                    placeholder="Where they work"
                                />
                                <flux:description>The company this person works for</flux:description>
                                <flux:error name="company" />
                            </flux:field>
                        @endif

                        <!-- Email -->
                        <flux:field>
                            <flux:label badge="Required">Email Address</flux:label>
                            <flux:input 
                                type="email" 
                                wire:model.blur="email" 
                                placeholder="email@example.com"
                                icon="envelope"
                                required 
                            />
                            <flux:description>Primary contact email</flux:description>
                            <flux:error name="email" />
                        </flux:field>

                        <!-- Phone -->
                        <flux:field>
                            <flux:label badge="Optional">Phone Number</flux:label>
                            <flux:input 
                                type="tel" 
                                wire:model.blur="phone" 
                                placeholder="(555) 555-5555"
                                icon="phone"
                            />
                            <flux:description>Include country code for international numbers</flux:description>
                            <flux:error name="phone" />
                        </flux:field>

                        <!-- Website -->
                        <flux:field>
                            <flux:label badge="Optional">Website</flux:label>
                            <flux:input 
                                type="url" 
                                wire:model.blur="website" 
                                placeholder="https://example.com"
                                icon="globe-alt"
                            />
                            <flux:error name="website" />
                        </flux:field>

                        <!-- Tax ID -->
                        <flux:field>
                            <flux:label badge="Optional">Tax ID / EIN</flux:label>
                            <flux:input 
                                wire:model.blur="tax_id_number" 
                                placeholder="XX-XXXXXXX"
                                icon="identification"
                            />
                            <flux:description>For tax reporting purposes</flux:description>
                            <flux:error name="tax_id_number" />
                        </flux:field>

                        <!-- Referral Source -->
                        <flux:field>
                            <flux:label badge="Optional">Referral Source</flux:label>
                            <flux:input 
                                wire:model.blur="referral" 
                                placeholder="How did they hear about us?"
                                icon="megaphone"
                            />
                            <flux:error name="referral" />
                        </flux:field>
                    </div>
                    
                    <!-- Navigation -->
                    <flux:separator variant="subtle" />
                    <div class="flex justify-end">
                        <flux:button type="button" wire:click="nextTab" icon="arrow-right">
                            Next
                        </flux:button>
                    </div>
                </flux:card>
            </flux:tab.panel>

            <flux:tab.panel name="address">
                <flux:card>
                    <flux:fieldset>
                        <flux:legend>Physical Address</flux:legend>
                        <div class="space-y-6">
                            <!-- Street Address -->
                            <flux:field>
                                <flux:label>Street Address</flux:label>
                                <flux:input 
                                    wire:model.blur="address" 
                                    placeholder="123 Main Street, Suite 100"
                                    icon="home"
                                />
                                <flux:error name="address" />
                            </flux:field>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- City -->
                                <flux:field>
                                    <flux:label>City</flux:label>
                                    <flux:input 
                                        wire:model.blur="city" 
                                        placeholder="San Francisco"
                                    />
                                    <flux:error name="city" />
                                </flux:field>

                                <!-- State -->
                                <flux:field>
                                    <flux:label>State / Province</flux:label>
                                    <flux:input 
                                        wire:model.blur="state" 
                                        placeholder="CA"
                                    />
                                    <flux:error name="state" />
                                </flux:field>

                                <!-- ZIP Code -->
                                <flux:field>
                                    <flux:label>ZIP / Postal Code</flux:label>
                                    <flux:input 
                                        wire:model.blur="zip_code" 
                                        placeholder="94102"
                                    />
                                    <flux:error name="zip_code" />
                                </flux:field>

                                <!-- Country -->
                                <flux:field>
                                    <flux:label>Country</flux:label>
                                    <flux:select 
                                        wire:model="country" 
                                        placeholder="Select country..."
                                    >
                                        <flux:select.option value="">Select country...</flux:select.option>
                                        <flux:select.option value="US">🇺🇸 United States</flux:select.option>
                                        <flux:select.option value="CA">🇨🇦 Canada</flux:select.option>
                                        <flux:select.option value="GB">🇬🇧 United Kingdom</flux:select.option>
                                        <flux:select.option value="AU">🇦🇺 Australia</flux:select.option>
                                    </flux:select>
                                    <flux:error name="country" />
                                </flux:field>
                            </div>
                        </div>
                    </flux:fieldset>
                    
                    <!-- Navigation -->
                    <flux:separator variant="subtle" class="mt-6" />
                    <div class="flex justify-between mt-6">
                        <flux:button type="button" variant="ghost" wire:click="previousTab" icon="arrow-left">
                            Previous
                        </flux:button>
                        <flux:button type="button" wire:click="nextTab" icon="arrow-right">
                            Next
                        </flux:button>
                    </div>
                </flux:card>
            </flux:tab.panel>

            <flux:tab.panel name="billing">
                <flux:card class="space-y-6">
                    <!-- Status Selection -->
                    <flux:field>
                        <flux:label>Client Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="active">Active</flux:select.option>
                            <flux:select.option value="inactive">Inactive</flux:select.option>
                            <flux:select.option value="suspended">Suspended</flux:select.option>
                        </flux:select>
                        <flux:description>Client can be selected for new projects and invoices when active</flux:description>
                        <flux:error name="status" />
                    </flux:field>

                    <flux:separator variant="subtle" />

                    <!-- Billing Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Currency -->
                        <flux:field>
                            <flux:label>Currency</flux:label>
                            <flux:select wire:model="currency_code">
                                <flux:select.option value="USD">$ USD - US Dollar</flux:select.option>
                                <flux:select.option value="EUR">€ EUR - Euro</flux:select.option>
                                <flux:select.option value="GBP">£ GBP - British Pound</flux:select.option>
                                <flux:select.option value="CAD">$ CAD - Canadian Dollar</flux:select.option>
                                <flux:select.option value="AUD">$ AUD - Australian Dollar</flux:select.option>
                            </flux:select>
                            <flux:error name="currency_code" />
                        </flux:field>

                        <!-- Payment Terms -->
                        <flux:field>
                            <flux:label>Payment Terms</flux:label>
                            <flux:select wire:model="net_terms">
                                <flux:select.option value="0">Due on Receipt</flux:select.option>
                                <flux:select.option value="15">Net 15</flux:select.option>
                                <flux:select.option value="30">Net 30</flux:select.option>
                                <flux:select.option value="45">Net 45</flux:select.option>
                                <flux:select.option value="60">Net 60</flux:select.option>
                            </flux:select>
                            <flux:description>Days until payment is due</flux:description>
                            <flux:error name="net_terms" />
                        </flux:field>

                        <!-- Hourly Rate -->
                        <flux:field>
                            <flux:label badge="Optional">Hourly Rate</flux:label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                <flux:input 
                                    type="number" 
                                    wire:model.blur="hourly_rate" 
                                    step="0.01" 
                                    min="0"
                                    placeholder="150.00"
                                    class="pl-8"
                                />
                            </div>
                            <flux:description>Default rate for time-based billing</flux:description>
                            <flux:error name="hourly_rate" />
                        </flux:field>

                    </div>
                    
                    <!-- Navigation -->
                    <flux:separator variant="subtle" />
                    <div class="flex justify-between">
                        <flux:button type="button" variant="ghost" wire:click="previousTab" icon="arrow-left">
                            Previous
                        </flux:button>
                        <flux:button type="button" wire:click="nextTab" icon="arrow-right">
                            Next
                        </flux:button>
                    </div>
                </flux:card>
            </flux:tab.panel>

            <flux:tab.panel name="additional">
                <flux:card class="space-y-6">
                    <!-- Tags -->
                    @if($availableTags->count() > 0)
                        <flux:field>
                            <flux:label>Tags</flux:label>
                            <flux:select 
                                wire:model="tags" 
                                multiple 
                                placeholder="Select tags..."
                            >
                                @foreach($availableTags as $tag)
                                    <flux:select.option value="{{ $tag->id }}">{{ $tag->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:description>Categorize this {{ $isLead ? 'lead' : 'client' }} with tags</flux:description>
                        </flux:field>
                    @endif

                    <!-- Notes -->
                    <flux:field>
                        <flux:label>Internal Notes</flux:label>
                        <flux:textarea 
                            wire:model="notes" 
                            rows="4" 
                            placeholder="Add any relevant notes about this {{ $isLead ? 'lead' : 'client' }}..."
                        />
                        <flux:description>These notes are only visible to your team</flux:description>
                        <flux:error name="notes" />
                    </flux:field>

                    
                    <!-- Navigation -->
                    <flux:separator variant="subtle" />
                    <div class="flex justify-start">
                        <flux:button type="button" variant="ghost" wire:click="previousTab" icon="arrow-left">
                            Previous
                        </flux:button>
                    </div>
                </flux:card>
            </flux:tab.panel>
        </flux:tab.group>

        <!-- Form Actions -->
        <div class="flex justify-between items-center mt-6">
            <flux:text size="sm" class="text-gray-500">
                <span wire:loading.remove>All fields with badges are required</span>
                <span wire:loading>Saving...</span>
            </flux:text>
            
            <div class="flex gap-3">
                <flux:button 
                    variant="ghost" 
                    type="button" 
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                >
                    Cancel
                </flux:button>
                @if($currentTab === 'additional')
                    <flux:button 
                        variant="primary" 
                        type="submit" 
                        wire:loading.attr="disabled"
                        icon="check"
                    >
                        <span wire:loading.remove>Create {{ $isLead ? 'Lead' : 'Client' }}</span>
                        <span wire:loading>Creating...</span>
                    </flux:button>
                @endif
            </div>
        </div>
    </form>
</div>
