<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:heading size="xl">Create New Lead</flux:heading>
                                <flux:subheading>Add a new lead to your pipeline and start tracking their journey.</flux:subheading>
                            </div>
                            <flux:button 
                                href="{{ route('leads.index') }}" 
                                variant="ghost" 
                                icon="arrow-left">
                                Back
                            </flux:button>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div>
                            <flux:heading size="lg">Personal Information</flux:heading>
                            <flux:subheading>Basic contact details for the lead</flux:subheading>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="first_name"
                                    label="First Name"
                                    placeholder="John"
                                    required />

                                <flux:input
                                    wire:model="last_name"
                                    label="Last Name"
                                    placeholder="Doe"
                                    required />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="email"
                                    label="Email"
                                    type="email"
                                    placeholder="john.doe@example.com"
                                    required />

                                <flux:input
                                    wire:model="phone"
                                    label="Phone"
                                    type="tel"
                                    placeholder="(555) 555-5555" />
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div>
                            <flux:heading size="lg">Company Information</flux:heading>
                            <flux:subheading>Details about the lead's organization</flux:subheading>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="company_name"
                                    label="Company Name"
                                    placeholder="Acme Corporation" />

                                <flux:input
                                    wire:model="title"
                                    label="Job Title"
                                    placeholder="CEO" />
                            </div>

                            <flux:input
                                wire:model="website"
                                label="Website"
                                type="url"
                                placeholder="https://example.com" />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Industry</flux:label>
                                    <flux:select wire:model="industry" placeholder="Select an industry">
                                        @foreach($industries as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>

                                <flux:input
                                    wire:model="company_size"
                                    label="Company Size"
                                    type="number"
                                    min="1"
                                    placeholder="50" />
                            </div>

                            <flux:callout icon="light-bulb" color="amber" variant="secondary">
                                <flux:callout.text>
                                    Company details help us better understand the lead's context and scoring potential
                                </flux:callout.text>
                            </flux:callout>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div>
                            <flux:heading size="lg">Address</flux:heading>
                            <flux:subheading>Physical location information</flux:subheading>
                        </div>

                        <div class="space-y-6">
                            <flux:input
                                wire:model="address"
                                label="Street Address"
                                placeholder="123 Main Street" />

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:input
                                    wire:model="city"
                                    label="City"
                                    placeholder="San Francisco" />

                                <flux:input
                                    wire:model="state"
                                    label="State / Province"
                                    placeholder="CA" />

                                <flux:input
                                    wire:model="zip_code"
                                    label="ZIP / Postal Code"
                                    placeholder="94103" />
                            </div>

                            <flux:input
                                wire:model="country"
                                label="Country"
                                placeholder="United States" />
                        </div>
                    </flux:card>

                    <flux:card>
                        <div>
                            <flux:heading size="lg">Lead Management</flux:heading>
                            <flux:subheading>Assignment and tracking details</flux:subheading>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Lead Source</flux:label>
                                    <flux:select wire:model="lead_source_id" placeholder="Select a source">
                                        @foreach($leadSources as $source)
                                            <flux:select.option value="{{ $source->id }}">{{ $source->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Assigned User</flux:label>
                                    <flux:select wire:model="assigned_user_id" placeholder="Assign to someone">
                                        @foreach($users as $user)
                                            <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label badge="Required">Priority</flux:label>
                                    <flux:select wire:model="priority" required>
                                        @foreach($priorities as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>

                                <flux:input
                                    wire:model="estimated_value"
                                    label="Estimated Value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="10000.00" />
                            </div>

                            <flux:textarea
                                wire:model="notes"
                                label="Notes"
                                placeholder="Additional information about this lead..."
                                rows="4" />

                            <flux:callout icon="information-circle" variant="secondary">
                                <flux:callout.text>
                                    Lead scoring will be calculated automatically based on the information provided
                                </flux:callout.text>
                            </flux:callout>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div>
                            <flux:heading size="lg">UTM Parameters</flux:heading>
                            <flux:subheading>Campaign tracking information (if applicable)</flux:subheading>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="utm_source"
                                    label="UTM Source"
                                    placeholder="google" />

                                <flux:input
                                    wire:model="utm_medium"
                                    label="UTM Medium"
                                    placeholder="cpc" />
                            </div>

                            <flux:input
                                wire:model="utm_campaign"
                                label="UTM Campaign"
                                placeholder="summer_sale_2024" />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="utm_content"
                                    label="UTM Content"
                                    placeholder="banner_ad" />

                                <flux:input
                                    wire:model="utm_term"
                                    label="UTM Term"
                                    placeholder="managed_services" />
                            </div>

                            <flux:callout icon="chart-bar" color="blue" variant="secondary">
                                <flux:callout.text>
                                    UTM parameters help track which marketing campaigns are generating the best leads
                                </flux:callout.text>
                            </flux:callout>
                        </div>
                    </flux:card>
                </div>

                <div class="space-y-6">
                    <flux:card>
                        <div class="space-y-4">
                            <flux:button
                                type="submit"
                                variant="primary"
                                class="w-full justify-center"
                                icon="check"
                                wire:loading.attr="disabled"
                                wire:target="save">
                                <span wire:loading.remove wire:target="save">Create Lead</span>
                                <span wire:loading wire:target="save">Creating...</span>
                            </flux:button>

                            <flux:button
                                href="{{ route('leads.index') }}"
                                variant="ghost"
                                class="w-full justify-center"
                                icon="x-mark">
                                Cancel
                            </flux:button>
                        </div>
                    </flux:card>

                    <flux:callout icon="information-circle" color="blue">
                        <flux:callout.heading>Next Steps</flux:callout.heading>
                        <flux:callout.text>
                            After creating this lead, you'll be able to:
                            <ul class="mt-2 ml-4 list-disc space-y-1">
                                <li>Track interactions and activities</li>
                                <li>Monitor lead score changes</li>
                                <li>Add to marketing campaigns</li>
                                <li>Convert to a client</li>
                            </ul>
                        </flux:callout.text>
                    </flux:callout>

                    <flux:callout icon="star" color="purple" variant="secondary">
                        <flux:callout.heading>Lead Scoring</flux:callout.heading>
                        <flux:callout.text>
                            Leads are automatically scored based on:
                            <ul class="mt-2 ml-4 list-disc space-y-1">
                                <li>Demographic fit</li>
                                <li>Company information</li>
                                <li>Behavioral engagement</li>
                                <li>Estimated value</li>
                            </ul>
                        </flux:callout.text>
                    </flux:callout>

                    <flux:callout icon="users" color="green" variant="secondary">
                        <flux:callout.heading>Pro Tip</flux:callout.heading>
                        <flux:callout.text>
                            Assigning leads to the right team member ensures faster follow-up and better conversion rates.
                        </flux:callout.text>
                    </flux:callout>
                </div>
            </div>
        </form>
    </div>
</div>
