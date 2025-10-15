<div class="container-fluid px-4 lg:px-8">
    <!-- Header -->
    <flux:card class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Add New Contact</flux:heading>
                @if($client)
                    <flux:text>Create a new contact for {{ $client->name }}</flux:text>
                @endif
            </div>
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('clients.contacts.index') }}">
                Back to Contacts
            </flux:button>
        </div>
    </flux:card>

    @if(!$client)
        <flux:card class="mb-6">
            <div class="text-center py-8">
                <flux:text class="text-gray-500">Please select a client first to create a contact.</flux:text>
                <div class="mt-4">
                    <flux:button href="{{ route('clients.contacts.index') }}" variant="primary">
                        Go to Contacts
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @else
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button 
                    wire:click="setTab('essential')" 
                    type="button"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'essential' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Essential Information
                </button>
                <button 
                    wire:click="setTab('professional')" 
                    type="button"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'professional' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Professional & Communication
                </button>
                <button 
                    wire:click="setTab('portal')" 
                    type="button"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'portal' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Portal & Security
                </button>
                <button 
                    wire:click="setTab('extended')" 
                    type="button"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'extended' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Extended Information
                </button>
            </nav>
        </div>

        <!-- Form -->
        <form wire:submit="save" autocomplete="off">
            @if($activeTab === 'essential')
                <!-- Essential Information Tab -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Basic Information (2/3 width) -->
                    <div class="lg:col-span-2">
                        <flux:card>
                            <flux:heading size="lg" class="mb-4">Basic Information</flux:heading>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Name *</flux:label>
                                    <flux:input wire:model="name" placeholder="John Doe" />
                                    @error('name')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Title</flux:label>
                                    <flux:input wire:model="title" placeholder="IT Manager" />
                                    @error('title')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Email</flux:label>
                                    <flux:input type="email" wire:model="email" placeholder="john@example.com" />
                                    @error('email')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Phone</flux:label>
                                    <flux:input type="tel" wire:model="phone" placeholder="+1 (555) 123-4567" />
                                    @error('phone')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Extension</flux:label>
                                    <flux:input wire:model="extension" placeholder="123" />
                                    @error('extension')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Mobile</flux:label>
                                    <flux:input type="tel" wire:model="mobile" placeholder="+1 (555) 987-6543" />
                                    @error('mobile')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Department</flux:label>
                                    <flux:input wire:model="department" placeholder="Information Technology" />
                                    @error('department')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Role</flux:label>
                                    <flux:input wire:model="role" placeholder="Decision Maker" />
                                    @error('role')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>
                            </div>
                        </flux:card>
                    </div>

                    <!-- Contact Type & Notes (1/3 width) -->
                    <div class="space-y-6">
                        <flux:card>
                            <flux:heading size="lg" class="mb-4">Contact Type</flux:heading>
                            <div class="space-y-3">
                                <flux:checkbox wire:model="primary" label="Primary Contact" description="Main point of contact" />
                                <flux:checkbox wire:model="billing" label="Billing Contact" description="Handles payments" />
                                <flux:checkbox wire:model="technical" label="Technical Contact" description="Handles support" />
                                <flux:checkbox wire:model="important" label="Important Contact" description="Key decision maker" />
                            </div>
                        </flux:card>

                        <flux:card>
                            <flux:heading size="lg" class="mb-4">Notes</flux:heading>
                            <flux:field>
                                <flux:textarea wire:model="notes" rows="4" placeholder="Additional notes..." />
                                @error('notes')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>
                        </flux:card>
                    </div>
                </div>

            @elseif($activeTab === 'professional')
                <!-- Professional & Communication Tab -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Communication Preferences -->
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Communication Preferences</flux:heading>
                        
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Preferred Contact Method</flux:label>
                                <flux:select wire:model="preferred_contact_method">
                                    @foreach($contactMethods as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('preferred_contact_method')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Best Time to Contact</flux:label>
                                <flux:select wire:model="best_time_to_contact">
                                    @foreach($contactTimes as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('best_time_to_contact')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Timezone</flux:label>
                                <flux:select wire:model="timezone">
                                    <flux:select.option value="">Select Timezone</flux:select.option>
                                    @foreach($timezones as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('timezone')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Language</flux:label>
                                <flux:select wire:model="language">
                                    @foreach($languages as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('language')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <div class="space-y-2">
                                <flux:checkbox wire:model="do_not_disturb" label="Do Not Disturb" description="Limit non-urgent communications" />
                                <flux:checkbox wire:model="marketing_opt_in" label="Marketing Opt-in" description="Allow marketing communications" />
                            </div>
                        </div>
                    </flux:card>

                    <!-- Professional Details -->
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Professional Details</flux:heading>
                        
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>LinkedIn URL</flux:label>
                                <flux:input wire:model="linkedin_url" placeholder="https://linkedin.com/in/username" />
                                @error('linkedin_url')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Reports To</flux:label>
                                <flux:select wire:model="reports_to_id">
                                    <flux:select.option value="">Select Contact</flux:select.option>
                                    @foreach($contacts as $contact)
                                        <flux:select.option value="{{ $contact->id }}">{{ $contact->name }} @if($contact->title)({{ $contact->title }})@endif</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('reports_to_id')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Work Schedule</flux:label>
                                <flux:input wire:model="work_schedule" placeholder="Mon-Fri 9AM-5PM" />
                                @error('work_schedule')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Assistant Name</flux:label>
                                <flux:input wire:model="assistant_name" placeholder="Assistant's name" />
                                @error('assistant_name')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Assistant Email</flux:label>
                                <flux:input type="email" wire:model="assistant_email" placeholder="assistant@company.com" />
                                @error('assistant_email')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Assistant Phone</flux:label>
                                <flux:input type="tel" wire:model="assistant_phone" placeholder="+1 (555) 123-4567" />
                                @error('assistant_phone')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Professional Bio</flux:label>
                                <flux:textarea wire:model="professional_bio" rows="3" placeholder="Brief professional background..." />
                                @error('professional_bio')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>
                        </div>
                    </flux:card>
                </div>

            @elseif($activeTab === 'portal')
                <!-- Portal & Security Tab -->
                <div class="max-w-2xl mx-auto">
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Client Portal Access</flux:heading>
                        
                        <div class="space-y-6">
                            <flux:field>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <flux:label>Enable Portal Access</flux:label>
                                        <flux:description>Allow this contact to access the client portal</flux:description>
                                    </div>
                                    <flux:switch wire:model.live="has_portal_access" />
                                </div>
                            </flux:field>

                            @if($has_portal_access)
                                <flux:field>
                                    <flux:label>Portal Access Method</flux:label>
                                    <flux:radio.group wire:model.live="portal_access_method">
                                        <flux:radio value="manual_password" label="Set password manually" />
                                        <flux:radio value="send_invitation" label="Send invitation email" />
                                    </flux:radio.group>
                                    <flux:description>Choose how to set up portal access for this contact</flux:description>
                                </flux:field>
                                
                                @if($portal_access_method === 'manual_password')
                                    <flux:field>
                                        <flux:label>Authentication Method</flux:label>
                                        <flux:select wire:model="auth_method">
                                            <flux:select.option value="password">Password</flux:select.option>
                                            <flux:select.option value="pin">PIN Code</flux:select.option>
                                            <flux:select.option value="none">No Authentication</flux:select.option>
                                        </flux:select>
                                        @error('auth_method')<flux:error>{{ $message }}</flux:error>@enderror
                                    </flux:field>

                                    @if($auth_method === 'password')
                                        {{-- Honeypot fields to prevent autofill --}}
                                        <input type="text" name="fakeusername" autocomplete="username" tabindex="-1" style="position: absolute; opacity: 0; height: 0; width: 0;" />
                                        <input type="password" name="fakepassword" autocomplete="current-password" tabindex="-1" style="position: absolute; opacity: 0; height: 0; width: 0;" />
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <flux:field>
                                                <flux:label>Password</flux:label>
                                                <flux:input 
                                                    type="password" 
                                                    wire:model="password" 
                                                    placeholder="••••••••"
                                                    autocomplete="new-password"
                                                    data-form-type="other"
                                                    data-lpignore="true"
                                                    data-1p-ignore="true"
                                                />
                                                <flux:description>Minimum 8 characters</flux:description>
                                                @error('password')<flux:error>{{ $message }}</flux:error>@enderror
                                            </flux:field>

                                            <flux:field>
                                                <flux:label>Confirm Password</flux:label>
                                                <flux:input 
                                                    type="password" 
                                                    wire:model="password_confirmation" 
                                                    placeholder="••••••••"
                                                    autocomplete="new-password"
                                                    data-form-type="other"
                                                    data-lpignore="true"
                                                    data-1p-ignore="true"
                                                />
                                                @error('password_confirmation')<flux:error>{{ $message }}</flux:error>@enderror
                                            </flux:field>
                                        </div>
                                    @endif
                                @elseif($portal_access_method === 'send_invitation')
                                    <flux:callout type="info" class="mt-4">
                                        <flux:heading size="sm">Invitation Email</flux:heading>
                                        <flux:text>
                                            An invitation email will be sent to {{ $email ?: 'the contact' }} after creation.
                                            They will have 72 hours to set their own password.
                                        </flux:text>
                                    </flux:callout>
                                @endif
                            @endif
                        </div>
                    </flux:card>
                </div>

            @elseif($activeTab === 'extended')
                <!-- Extended Information Tab -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Location & Availability -->
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Location & Availability</flux:heading>
                        
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Office Location</flux:label>
                                <flux:select wire:model="office_location_id">
                                    <flux:select.option value="">Select Location</flux:select.option>
                                    @foreach($locations as $location)
                                        <flux:select.option value="{{ $location->id }}">{{ $location->name }} - {{ $location->address }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('office_location_id')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Out of Office Start</flux:label>
                                    <flux:input type="date" wire:model="out_of_office_start" />
                                    @error('out_of_office_start')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>

                                <flux:field>
                                    <flux:label>Out of Office End</flux:label>
                                    <flux:input type="date" wire:model="out_of_office_end" />
                                    @error('out_of_office_end')<flux:error>{{ $message }}</flux:error>@enderror
                                </flux:field>
                            </div>

                            <div class="space-y-2">
                                <flux:checkbox wire:model="is_emergency_contact" label="Emergency Contact" description="Available for urgent issues" />
                                <flux:checkbox wire:model="is_after_hours_contact" label="After Hours Contact" description="Available outside business hours" />
                            </div>
                        </div>
                    </flux:card>

                    <!-- Social & Web Presence -->
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Social & Web Presence</flux:heading>
                        
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Website</flux:label>
                                <flux:input wire:model="website" placeholder="https://www.company.com" />
                                @error('website')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Company Blog</flux:label>
                                <flux:input wire:model="company_blog" placeholder="https://blog.company.com" />
                                @error('company_blog')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Twitter Handle</flux:label>
                                <flux:input wire:model="twitter_handle" placeholder="@username" />
                                @error('twitter_handle')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Facebook URL</flux:label>
                                <flux:input wire:model="facebook_url" placeholder="https://facebook.com/username" />
                                @error('facebook_url')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>

                            <flux:field>
                                <flux:label>Instagram Handle</flux:label>
                                <flux:input wire:model="instagram_handle" placeholder="@username" />
                                @error('instagram_handle')<flux:error>{{ $message }}</flux:error>@enderror
                            </flux:field>
                        </div>
                    </flux:card>
                </div>
            @endif

            <!-- Form Actions -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
                <div class="flex gap-2">
                    @if($activeTab !== 'essential')
                        <flux:button 
                            type="button" 
                            variant="ghost" 
                            wire:click="setTab('{{ $activeTab === 'professional' ? 'essential' : ($activeTab === 'portal' ? 'professional' : 'portal') }}')"
                        >
                            Previous
                        </flux:button>
                    @endif
                </div>
                
                <div class="flex gap-2">
                    <flux:button variant="ghost" href="{{ route('clients.contacts.index') }}">
                        Cancel
                    </flux:button>
                    
                    @if($activeTab !== 'extended')
                        <flux:button 
                            type="button" 
                            variant="primary"
                            wire:click="setTab('{{ $activeTab === 'essential' ? 'professional' : ($activeTab === 'professional' ? 'portal' : 'extended') }}')"
                        >
                            Next
                        </flux:button>
                    @else
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Create Contact</span>
                            <span wire:loading>Creating...</span>
                        </flux:button>
                    @endif
                </div>
            </div>
        </form>
    @endif
</div>