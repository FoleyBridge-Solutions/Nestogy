<div>
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Create New Ticket</flux:heading>
                <flux:text>Submit a support request for your client</flux:text>
            </div>
            <flux:button href="{{ route('tickets.index') }}" 
                        variant="ghost"
                        icon="arrow-left">
                Back to Tickets
            </flux:button>
        </div>
    </flux:card>

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Basic Ticket Information (takes 2 columns on large screens) -->
            <div class="lg:col-span-2">
                <flux:card>
                    <flux:heading size="lg">Ticket Information</flux:heading>
                    <flux:subheading>Basic details about the support ticket</flux:subheading>
                    
                    <div class="space-y-6 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Client Selection -->
                            <flux:field>
                                <flux:label for="client_id" required>Client</flux:label>
                                <flux:select wire:model.live="client_id" id="client_id" name="client_id" required>
                                    <option value="">Select a client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </flux:select>
                                @error('client_id')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                            
                            <!-- Contact Selection with Pillbox -->
                            @if($this->client_id && $contacts->count() > 0)
                                <flux:field>
                                    <flux:label for="contact_ids">Client Contacts</flux:label>
                                    <flux:pillbox 
                                        wire:model="contact_ids" 
                                        multiple 
                                        searchable
                                        placeholder="Select contacts...">
                                        @foreach($contacts as $contact)
                                            <flux:pillbox.option value="{{ $contact->id }}">
                                                {{ $contact->name }}
                                                @if($contact->email)
                                                    <span class="text-xs text-zinc-400"> ({{ $contact->email }})</span>
                                                @endif
                                            </flux:pillbox.option>
                                        @endforeach
                                    </flux:pillbox>
                                    <flux:description>
                                        Select one or more contacts to notify about this ticket
                                    </flux:description>
                                    @error('contact_ids')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                            @endif
                        </div>
                        
                        <!-- Subject and Priority -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:field>
                                <flux:label for="subject" required>Subject</flux:label>
                                <flux:input 
                                    wire:model="subject"
                                    type="text"
                                    id="subject" 
                                    name="subject" 
                                    placeholder="Brief description of the issue"
                                    required />
                                @error('subject')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                                
                            <flux:field>
                                <flux:label for="priority" required>Priority</flux:label>
                                <flux:select wire:model="priority" id="priority" name="priority" required>
                                    <option value="Low">🔵 Low Priority</option>
                                    <option value="Medium">🟡 Medium Priority</option>
                                    <option value="High">🟠 High Priority</option>
                                    <option value="Critical">🔴 Critical Priority</option>
                                </flux:select>
                                @error('priority')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                        </div>
                        
                        <!-- Assignment and Asset -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:field>
                                <flux:label for="assigned_to">Assign To</flux:label>
                                <flux:pillbox 
                                    wire:model="assigned_to" 
                                    searchable
                                    placeholder="Select user or leave unassigned...">
                                    @foreach($assignees as $user)
                                        <flux:pillbox.option value="{{ $user->id }}">
                                            {{ $user->name }}
                                            @if($user->email)
                                                <span class="text-xs text-zinc-400"> ({{ $user->email }})</span>
                                            @endif
                                        </flux:pillbox.option>
                                    @endforeach
                                </flux:pillbox>
                                @error('assigned_to')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                            
                            @if($this->client_id && $assets->count() > 0)
                                <flux:field>
                                    <flux:label for="asset_id">Related Asset</flux:label>
                                    <flux:pillbox 
                                        wire:model="asset_id" 
                                        searchable
                                        placeholder="Select asset...">
                                        @foreach($assets as $asset)
                                            <flux:pillbox.option value="{{ $asset->id }}">
                                                {{ $asset->name }}
                                                @if($asset->type)
                                                    <span class="text-xs text-zinc-400"> ({{ $asset->type }})</span>
                                                @endif
                                            </flux:pillbox.option>
                                        @endforeach
                                    </flux:pillbox>
                                    @error('asset_id')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                            @endif
                        </div>
                        
                        <!-- Details -->
                        <flux:field>
                            <flux:label for="details" required>Details</flux:label>
                            <flux:textarea 
                                wire:model="details"
                                id="details" 
                                name="details" 
                                rows="6"
                                placeholder="Describe the issue in detail. Include steps to reproduce, error messages, and any relevant information."
                                required />
                            @error('details')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                        </flux:field>
                    </div>
                </flux:card>
            </div>
            
            <!-- Additional Information (takes 1 column on large screens) -->
            <div class="lg:col-span-1">
                <flux:card>
                    <flux:heading size="lg">Additional Information</flux:heading>
                    <flux:subheading>Vendor and billing details</flux:subheading>
                    
                    <div class="space-y-4 mt-6">
                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                            Additional ticket metadata and billing information can be configured here.
                        </flux:text>
                    </div>
                </flux:card>
            </div>
        </div>
        
        <!-- Form Actions -->
        <flux:card class="mt-4">
            <div class="flex items-center justify-between">
                <flux:text size="sm" class="flex items-center gap-1">
                    <flux:icon name="information-circle" variant="mini" />
                    All ticket information will be saved automatically
                </flux:text>
                <div class="flex gap-2">
                    <flux:button href="{{ route('tickets.index') }}" 
                                variant="ghost"
                                icon="x-mark">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" 
                                variant="primary"
                                icon="plus">
                        Create Ticket
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </form>
</div>
