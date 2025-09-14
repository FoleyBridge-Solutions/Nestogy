<div class="container-fluid px-4 lg:px-8">
    <!-- Header -->
    <flux:card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Edit {{ $client->lead ? 'Lead' : 'Client' }}</flux:heading>
                <flux:text>Update {{ $client->name }}'s information</flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" icon="eye" href="{{ route('clients.show', $client) }}">
                    View
                </flux:button>
                <flux:button variant="ghost" icon="arrow-left" href="{{ $client->lead ? route('clients.leads') : route('clients.index') }}">
                    Back to {{ $client->lead ? 'Leads' : 'Clients' }}
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <flux:card class="mb-6 border-green-200 bg-green-50">
            <flux:text class="text-green-800">{{ session('message') }}</flux:text>
        </flux:card>
    @endif

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8 overflow-x-auto">
            <button 
                wire:click="setActiveTab('basic')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'basic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Basic Information
            </button>
            <button 
                wire:click="setActiveTab('address')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'address' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Address
            </button>
            <button 
                wire:click="setActiveTab('contacts')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'contacts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Contacts
            </button>
            <button 
                wire:click="setActiveTab('billing')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'billing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Billing
            </button>
            <button 
                wire:click="setActiveTab('rates')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'rates' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Custom Rates
            </button>
            <button 
                wire:click="setActiveTab('contract')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'contract' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Contract & SLA
            </button>
            <button 
                wire:click="setActiveTab('additional')" 
                type="button"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'additional' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                Additional
            </button>
        </nav>
    </div>

    <!-- Form -->
    <form wire:submit="save">
        @if($activeTab === 'basic')
            <!-- Basic Information Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Basic Information</flux:heading>
                
                <div class="space-y-6">
                    <!-- Lead/Client Toggle -->
                    <flux:field>
                        <flux:checkbox wire:model="lead" label="This is a lead (not yet a client)" />
                        <flux:description>Leads are potential clients who haven't signed a contract yet</flux:description>
                    </flux:field>

                    <!-- Client Type -->
                    <flux:field>
                        <flux:label>Client Type</flux:label>
                        <flux:radio.group wire:model="type" variant="segmented">
                            <flux:radio value="individual" label="Individual" />
                            <flux:radio value="business" label="Business" />
                        </flux:radio.group>
                        <flux:description>Select whether this is an individual or business entity</flux:description>
                    </flux:field>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Name -->
                        <flux:field>
                            <flux:label>{{ $type === 'business' ? 'Business Name' : 'Full Name' }} *</flux:label>
                            <flux:input wire:model="name" placeholder="{{ $type === 'business' ? 'Acme Corporation' : 'John Smith' }}" />
                            @error('name')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- Company (for individuals) -->
                        @if($type === 'individual')
                        <flux:field>
                            <flux:label>Company Name</flux:label>
                            <flux:input wire:model="company_name" placeholder="Where they work (optional)" />
                            @error('company_name')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>
                        @endif

                        <!-- Email -->
                        <flux:field>
                            <flux:label>Email Address *</flux:label>
                            <flux:input wire:model="email" type="email" placeholder="contact@example.com" />
                            @error('email')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- Phone -->
                        <flux:field>
                            <flux:label>Phone Number</flux:label>
                            <flux:input wire:model="phone" type="tel" placeholder="+1 (555) 123-4567" />
                            @error('phone')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- Website -->
                        <flux:field>
                            <flux:label>Website</flux:label>
                            <flux:input wire:model="website" type="url" placeholder="https://example.com" />
                            @error('website')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- Tax ID -->
                        <flux:field>
                            <flux:label>Tax ID / EIN</flux:label>
                            <flux:input wire:model="tax_id_number" placeholder="XX-XXXXXXX" />
                            <flux:description>For tax reporting purposes</flux:description>
                            @error('tax_id_number')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- Referral Source -->
                        <flux:field class="md:col-span-2">
                            <flux:label>Referral Source</flux:label>
                            <flux:input wire:model="referral" placeholder="How did they hear about us? (Google, referral from John, etc.)" />
                            <flux:description>Track where your best clients come from</flux:description>
                            @error('referral')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- RMM ID -->
                        <flux:field>
                            <flux:label>RMM Client ID</flux:label>
                            <flux:input wire:model="rmm_id" placeholder="External RMM system ID" />
                            <flux:description>Link to remote monitoring system</flux:description>
                            @error('rmm_id')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>
                    </div>
                </div>
            </flux:card>
        @endif

        @if($activeTab === 'address')
            <!-- Address Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Physical Address</flux:heading>
                <flux:text class="mb-6">Primary business or billing address</flux:text>
                
                <div class="space-y-4">
                    <!-- Street Address -->
                    <flux:field>
                        <flux:label>Street Address</flux:label>
                        <flux:input wire:model="address" placeholder="123 Main Street, Suite 100" />
                        @error('address')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                        <!-- City -->
                        <flux:field>
                            <flux:label>City</flux:label>
                            <flux:input wire:model="city" placeholder="New York" />
                            @error('city')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- State -->
                        <flux:field>
                            <flux:label>State / Province</flux:label>
                            <flux:input wire:model="state" placeholder="NY" />
                            @error('state')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- ZIP Code -->
                        <flux:field>
                            <flux:label>ZIP / Postal Code</flux:label>
                            <flux:input wire:model="zip_code" placeholder="10001" />
                            @error('zip_code')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>

                        <!-- Country -->
                        <flux:field class="md:col-span-3">
                            <flux:label>Country</flux:label>
                            <flux:select wire:model="country" placeholder="Select Country">
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="JP">Japan</option>
                                <option value="IN">India</option>
                            </flux:select>
                            @error('country')<flux:error>{{ $message }}</flux:error>@enderror
                        </flux:field>
                    </div>
                </div>
            </flux:card>
        @endif

        @if($activeTab === 'contacts')
            <!-- Contacts Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Key Contacts</flux:heading>
                <flux:text class="mb-6">Specify primary contacts for different purposes</flux:text>
                
                <div class="space-y-4">
                    <!-- Billing Contact -->
                    <flux:field>
                        <flux:label>Billing Contact</flux:label>
                        <flux:input wire:model="billing_contact" placeholder="Jane Doe - jane@example.com - (555) 123-4567" />
                        <flux:description>Person responsible for invoices and payments</flux:description>
                        @error('billing_contact')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Technical Contact -->
                    <flux:field>
                        <flux:label>Technical Contact</flux:label>
                        <flux:input wire:model="technical_contact" placeholder="John Smith - john@example.com - (555) 987-6543" />
                        <flux:description>Primary technical point of contact</flux:description>
                        @error('technical_contact')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>
                </div>
            </flux:card>
        @endif

        @if($activeTab === 'billing')
            <!-- Billing Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Billing Settings</flux:heading>
                <flux:text class="mb-6">Default billing configuration for this client</flux:text>
                
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Status -->
                    <flux:field>
                        <flux:label>Account Status *</flux:label>
                        <flux:select wire:model="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </flux:select>
                        @error('status')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Currency -->
                    <flux:field>
                        <flux:label>Currency</flux:label>
                        <flux:select wire:model="currency_code">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="CAD">CAD - Canadian Dollar</option>
                            <option value="AUD">AUD - Australian Dollar</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                        </flux:select>
                        @error('currency_code')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Payment Terms -->
                    <flux:field>
                        <flux:label>Payment Terms</flux:label>
                        <flux:select wire:model="net_terms">
                            <option value="0">Due on Receipt</option>
                            <option value="15">Net 15</option>
                            <option value="30">Net 30</option>
                            <option value="45">Net 45</option>
                            <option value="60">Net 60</option>
                            <option value="90">Net 90</option>
                        </flux:select>
                        @error('net_terms')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Default Hourly Rate -->
                    <flux:field>
                        <flux:label>Default Hourly Rate</flux:label>
                        <flux:input wire:model="hourly_rate" type="number" step="0.01" min="0" prefix="$" placeholder="150.00" />
                        <flux:description>Standard billing rate per hour</flux:description>
                        @error('hourly_rate')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Project Rate -->
                    <flux:field>
                        <flux:label>Project Rate</flux:label>
                        <flux:input wire:model="rate" type="number" step="0.01" min="0" prefix="$" placeholder="175.00" />
                        <flux:description>Rate for project-based work</flux:description>
                        @error('rate')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>
                </div>
            </flux:card>
        @endif

        @if($activeTab === 'rates')
            <!-- Custom Rates Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Custom Billing Rates</flux:heading>
                
                <div class="space-y-6">
                    <!-- Enable Custom Rates -->
                    <flux:field>
                        <flux:checkbox wire:model="use_custom_rates" label="Use custom billing rates for this client" />
                        <flux:description>Override company-wide billing rates with client-specific rates</flux:description>
                    </flux:field>

                    @if($use_custom_rates)
                    <div class="space-y-6">
                        <flux:field>
                            <flux:label>Rate Calculation Method</flux:label>
                            <flux:radio.group wire:model="custom_rate_calculation_method" variant="cards">
                                <flux:radio value="fixed_rates" label="Fixed Rates">
                                    <flux:description>Set specific dollar amounts for each rate type</flux:description>
                                </flux:radio>
                                <flux:radio value="multipliers" label="Multipliers">
                                    <flux:description>Apply multipliers to the standard rate</flux:description>
                                </flux:radio>
                            </flux:radio.group>
                        </flux:field>

                        @if($custom_rate_calculation_method === 'fixed_rates')
                        <div>
                            <flux:heading size="sm" class="mb-3">Fixed Rate Schedule</flux:heading>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <flux:field>
                                    <flux:label>Standard Rate</flux:label>
                                    <flux:input wire:model="custom_standard_rate" type="number" step="0.01" min="0" prefix="$" placeholder="150.00" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>After Hours Rate</flux:label>
                                    <flux:input wire:model="custom_after_hours_rate" type="number" step="0.01" min="0" prefix="$" placeholder="225.00" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Emergency Rate</flux:label>
                                    <flux:input wire:model="custom_emergency_rate" type="number" step="0.01" min="0" prefix="$" placeholder="300.00" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Weekend Rate</flux:label>
                                    <flux:input wire:model="custom_weekend_rate" type="number" step="0.01" min="0" prefix="$" placeholder="200.00" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Holiday Rate</flux:label>
                                    <flux:input wire:model="custom_holiday_rate" type="number" step="0.01" min="0" prefix="$" placeholder="350.00" />
                                </flux:field>
                            </div>
                        </div>
                        @else
                        <div>
                            <flux:heading size="sm" class="mb-3">Rate Multipliers</flux:heading>
                            <flux:text class="mb-4">Multipliers applied to the standard rate (e.g., 1.5 = 150% of standard)</flux:text>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <flux:field>
                                    <flux:label>After Hours Multiplier</flux:label>
                                    <flux:input wire:model="custom_after_hours_multiplier" type="number" step="0.1" min="1" placeholder="1.5" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Emergency Multiplier</flux:label>
                                    <flux:input wire:model="custom_emergency_multiplier" type="number" step="0.1" min="1" placeholder="2.0" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Weekend Multiplier</flux:label>
                                    <flux:input wire:model="custom_weekend_multiplier" type="number" step="0.1" min="1" placeholder="1.25" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Holiday Multiplier</flux:label>
                                    <flux:input wire:model="custom_holiday_multiplier" type="number" step="0.1" min="1" placeholder="2.5" />
                                </flux:field>
                            </div>
                        </div>
                        @endif

                        <div>
                            <flux:heading size="sm" class="mb-3">Time Tracking Settings</flux:heading>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <flux:field>
                                    <flux:label>Minimum Billing Increment (hours)</flux:label>
                                    <flux:input wire:model="custom_minimum_billing_increment" type="number" step="0.01" min="0.01" max="2" placeholder="0.25" />
                                    <flux:description>Minimum time increment for billing (e.g., 0.25 = 15 minutes)</flux:description>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Time Rounding Method</flux:label>
                                    <flux:select wire:model="custom_time_rounding_method">
                                        <option value="nearest">Round to Nearest</option>
                                        <option value="up">Always Round Up</option>
                                        <option value="down">Always Round Down</option>
                                        <option value="none">No Rounding</option>
                                    </flux:select>
                                </flux:field>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </flux:card>
        @endif

        @if($activeTab === 'contract')
            <!-- Contract & SLA Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Contract Information</flux:heading>
                
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Contract Start Date -->
                    <flux:field>
                        <flux:label>Contract Start Date</flux:label>
                        <flux:input wire:model="contract_start_date" type="date" />
                        @error('contract_start_date')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Contract End Date -->
                    <flux:field>
                        <flux:label>Contract End Date</flux:label>
                        <flux:input wire:model="contract_end_date" type="date" />
                        <flux:description>Leave blank for ongoing contracts</flux:description>
                        @error('contract_end_date')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- SLA -->
                    <flux:field class="md:col-span-2">
                        <flux:label>Service Level Agreement (SLA)</flux:label>
                        <flux:select wire:model="sla_id" placeholder="Select SLA">
                            <option value="">Use Company Default</option>
                            @foreach($slas as $sla)
                                <option value="{{ $sla->id }}">
                                    {{ $sla->name }} 
                                    @if($sla->is_default)
                                        (Default)
                                    @endif
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:description>Defines response and resolution time commitments</flux:description>
                        @error('sla_id')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>
                </div>
            </flux:card>
        @endif

        @if($activeTab === 'additional')
            <!-- Additional Tab -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Additional Information</flux:heading>
                
                <div class="space-y-6">
                    <!-- Notes -->
                    <flux:field>
                        <flux:label>Internal Notes</flux:label>
                        <flux:textarea 
                            wire:model="notes" 
                            rows="6" 
                            placeholder="Add any internal notes about this {{ $client->lead ? 'lead' : 'client' }}...&#10;&#10;- Special requirements&#10;- Important contacts&#10;- Key dates&#10;- Account history" 
                        />
                        <flux:description>These notes are only visible to your team</flux:description>
                        @error('notes')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>

                    <!-- Avatar Upload -->
                    <flux:field>
                        <flux:label>Client Logo/Avatar</flux:label>
                        <div class="flex items-center space-x-5">
                            <div class="flex-shrink-0">
                                @if($client->avatar)
                                    <img class="h-20 w-20 rounded-full object-cover border-2 border-gray-300" 
                                         src="{{ asset('storage/' . $client->avatar) }}" alt="Current avatar">
                                @else
                                    <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center border-2 border-gray-300">
                                        <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-2">
                                <flux:input type="file" wire:model="avatar" accept="image/*" />
                                @if($client->avatar)
                                    <flux:checkbox wire:model="remove_avatar" label="Remove current logo" />
                                @endif
                                <flux:text size="sm" class="text-gray-500">PNG, JPG, GIF up to 2MB</flux:text>
                            </div>
                        </div>
                        @error('avatar')<flux:error>{{ $message }}</flux:error>@enderror
                    </flux:field>
                </div>
            </flux:card>
        @endif

        <!-- Form Actions -->
        <div class="flex gap-4 justify-end mt-6">
            <flux:button variant="ghost" href="{{ route('clients.show', $client) }}">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Update {{ $client->lead ? 'Lead' : 'Client' }}</span>
                <span wire:loading>Updating...</span>
            </flux:button>
        </div>
    </form>
</div>