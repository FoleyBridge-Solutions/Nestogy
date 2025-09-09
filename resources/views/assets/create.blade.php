@extends('layouts.app')

@section('title', 'Create Asset')

@section('content')
<div class="container mx-auto mx-auto mx-auto px-6">
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Create New Asset</flux:heading>
        </div>
        
        <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Basic Information</h5>
                        
                        <x-forms.client-search-field 
                            name="client_id" 
                            :required="true"
                            :selected="old('client_id', $selectedClientId ?? null) ? \App\Models\Client::where('company_id', auth()->user()->company_id)->find(old('client_id', $selectedClientId ?? null)) : null"
                            label="Client"
                            placeholder="Search for client..."
                            class="mb-6" />

                        <flux:field class="mb-6">
                            <flux:label for="type" required>Type</flux:label>
                            <flux:select name="type" id="type" required :error="$errors->has('type')">
                                <option value="">Select Type</option>
                                @foreach(App\Models\Asset::TYPES as $type)
                                    <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="type" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="name" required>Name</flux:label>
                            <flux:input type="text" name="name" id="name" 
                                value="{{ old('name') }}" 
                                placeholder="Asset name or tag" 
                                required 
                                :error="$errors->has('name')" />
                            <flux:error for="name" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="description">Description</flux:label>
                            <flux:textarea name="description" id="description" 
                                rows="3" 
                                placeholder="Asset description"
                                :error="$errors->has('description')">{{ old('description') }}</flux:textarea>
                            <flux:error for="description" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="status">Status</flux:label>
                            <flux:select name="status" id="status" :error="$errors->has('status')">
                                @foreach(App\Models\Asset::STATUSES as $status)
                                    <option value="{{ $status }}" {{ old('status', 'Ready To Deploy') == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="status" />
                        </flux:field>
                    </div>

                    <!-- Hardware Information -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Hardware Information</h5>
                        
                        <flux:field class="mb-6">
                            <flux:label for="serial">Serial Number</flux:label>
                            <flux:input type="text" name="serial" id="serial" 
                                value="{{ old('serial') }}" 
                                placeholder="Serial number"
                                :error="$errors->has('serial')" />
                            <flux:error for="serial" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="os">Operating System</flux:label>
                            <flux:input type="text" name="os" id="os" 
                                value="{{ old('os') }}" 
                                placeholder="e.g., Windows 11 Pro"
                                :error="$errors->has('os')" />
                            <flux:error for="os" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="vendor_id">Vendor</flux:label>
                            <flux:select name="vendor_id" id="vendor_id" :error="$errors->has('vendor_id')">
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="vendor_id" />
                        </flux:field>
                    </div>
                </div>

                <flux:separator class="my-6" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Network Information -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Network Information</h5>

                        <flux:field class="mb-6">
                            <flux:label for="ip">IP Address</flux:label>
                            <div class="flex">
                                <flux:input type="text" name="ip" id="ip" 
                                    value="{{ old('ip') }}" 
                                    placeholder="192.168.1.100"
                                    class="flex-1"
                                    :error="$errors->has('ip')" />
                                <div class="ml-2 flex items-center">
                                    <flux:checkbox name="dhcp" id="dhcp" value="1" checked="{{ old('dhcp') }}" />
                                    <flux:label for="dhcp" class="ml-2">DHCP</flux:label>
                                </div>
                            </div>
                            <flux:error for="ip" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="nat_ip">NAT IP</flux:label>
                            <flux:input type="text" name="nat_ip" id="nat_ip" 
                                value="{{ old('nat_ip') }}" 
                                placeholder="External IP"
                                :error="$errors->has('nat_ip')" />
                            <flux:error for="nat_ip" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="mac">MAC Address</flux:label>
                            <flux:input type="text" name="mac" id="mac" 
                                value="{{ old('mac') }}" 
                                placeholder="00:00:00:00:00:00" 
                                maxlength="17"
                                :error="$errors->has('mac')" />
                            <flux:error for="mac" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="network_id">Network</flux:label>
                            <flux:select name="network_id" id="network_id" :error="$errors->has('network_id')">
                                <option value="">Select Network</option>
                                @foreach($networks as $network)
                                    <option value="{{ $network->id }}" {{ old('network_id') == $network->id ? 'selected' : '' }}>
                                        {{ $network->name }} ({{ $network->network }})
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="network_id" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="uri">URI/URL</flux:label>
                            <flux:input type="text" name="uri" id="uri" 
                                value="{{ old('uri') }}" 
                                placeholder="https://device.local"
                                :error="$errors->has('uri')" />
                            <flux:error for="uri" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="port">Ports</flux:label>
                            <flux:input type="text" name="port" id="port" 
                                value="{{ old('port') }}" 
                                placeholder="22, 80, 443"
                                :error="$errors->has('port')" />
                            <flux:error for="port" />
                        </flux:field>
                    </div>

                    <!-- Location & Assignment -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Location & Assignment</h5>

                        <flux:field class="mb-6">
                            <flux:label for="location_id">Location</flux:label>
                            <flux:select name="location_id" id="location_id" :error="$errors->has('location_id')">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="location_id" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="room">Room/Area</flux:label>
                            <flux:input type="text" name="room" id="room" 
                                value="{{ old('room') }}" 
                                placeholder="Server Room, Office 201"
                                :error="$errors->has('room')" />
                            <flux:error for="room" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="contact_id">Assigned Contact</flux:label>
                            <flux:select name="contact_id" id="contact_id" :error="$errors->has('contact_id')">
                                <option value="">Select Contact</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->name }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="contact_id" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="user_id">Assigned Technician</flux:label>
                            <flux:select name="user_id" id="user_id" :error="$errors->has('user_id')">
                                <option value="">Select Technician</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error for="user_id" />
                        </flux:field>
                    </div>
                </div>

                <flux:separator class="my-6" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Purchase Information -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Purchase Information</h5>

                        <flux:field class="mb-6">
                            <flux:label for="price">Purchase Price</flux:label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                <flux:input type="number" name="price" id="price" 
                                    value="{{ old('price') }}" 
                                    placeholder="0.00" 
                                    step="0.01"
                                    class="pl-8"
                                    :error="$errors->has('price')" />
                            </div>
                            <flux:error for="price" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="date">Purchase Date</flux:label>
                            <flux:input type="date" name="date" id="date" 
                                value="{{ old('date') }}"
                                :error="$errors->has('date')" />
                            <flux:error for="date" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="depreciation_months">Depreciation Period (months)</flux:label>
                            <flux:input type="number" name="depreciation_months" id="depreciation_months" 
                                value="{{ old('depreciation_months', 36) }}" 
                                min="0"
                                :error="$errors->has('depreciation_months')" />
                            <flux:error for="depreciation_months" />
                        </flux:field>
                    </div>

                    <!-- Warranty Information -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Warranty Information</h5>

                        <flux:field class="mb-6">
                            <flux:label for="warranty_months">Warranty Period (months)</flux:label>
                            <flux:input type="number" name="warranty_months" id="warranty_months" 
                                value="{{ old('warranty_months') }}" 
                                min="0"
                                :error="$errors->has('warranty_months')" />
                            <flux:error for="warranty_months" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="install_date">Installation Date</flux:label>
                            <flux:input type="date" name="install_date" id="install_date" 
                                value="{{ old('install_date') }}"
                                :error="$errors->has('install_date')" />
                            <flux:error for="install_date" />
                        </flux:field>

                        <flux:field class="mb-6">
                            <flux:label for="notes">Additional Notes</flux:label>
                            <flux:textarea name="notes" id="notes" 
                                rows="4" 
                                placeholder="Any additional information..."
                                :error="$errors->has('notes')">{{ old('notes') }}</flux:textarea>
                            <flux:error for="notes" />
                        </flux:field>
                    </div>
                </div>

                <!-- Attachments -->
                <flux:separator class="my-6" />
                
                <div>
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Attachments</h5>
                    
                    <flux:field>
                        <flux:label for="attachments">Upload Files</flux:label>
                        <flux:input type="file" name="attachments[]" id="attachments" multiple />
                        <flux:text size="sm" class="mt-1 text-gray-500">
                            You can upload images, PDFs, or documents related to this asset.
                        </flux:text>
                    </flux:field>
                </div>
            </div>

            <flux:separator class="my-6" />
            
            <div class="flex justify-between">
                <flux:button type="button" variant="ghost" href="{{ route('assets.index') }}">
                    Cancel
                </flux:button>
                <div class="flex gap-2">
                    <flux:button type="submit" name="action" value="save_and_new" variant="secondary">
                        Save & Create Another
                    </flux:button>
                    <flux:button type="submit" name="action" value="save" variant="primary">
                        Create Asset
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</div>
@endsection
