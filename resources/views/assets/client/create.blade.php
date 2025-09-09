@extends('layouts.app')

@section('title', 'Create Asset for ' . $client->name)

@section('content')
<div class="container mx-auto px-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="flex items-center mb-4">
            <a href="{{ route('clients.assets.index', $client) }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to {{ $client->name }} Assets
            </a>
        </nav>
        
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Create New Asset</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Add a new asset for {{ $client->name }}</p>
    </div>

    <flux:card class="space-y-6">
        <form action="{{ route('clients.assets.store', $client) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div>
                        <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Basic Information</h5>
                        
                        <!-- Client is pre-selected -->
                        <flux:field class="mb-6">
                            <flux:label>Client</flux:label>
                            <flux:input type="text" value="{{ $client->name }}" disabled />
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                        </flux:field>

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
                    </div>

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
                            <flux:label for="warranty_months">Warranty Period (months)</flux:label>
                            <flux:input type="number" name="warranty_months" id="warranty_months" 
                                value="{{ old('warranty_months') }}" 
                                min="0"
                                :error="$errors->has('warranty_months')" />
                            <flux:error for="warranty_months" />
                        </flux:field>
                    </div>
                </div>

                <!-- Additional Notes -->
                <flux:separator class="my-6" />
                
                <div>
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">Additional Information</h5>
                    
                    <flux:field class="mb-6">
                        <flux:label for="notes">Notes</flux:label>
                        <flux:textarea name="notes" id="notes" 
                            rows="4" 
                            placeholder="Any additional information..."
                            :error="$errors->has('notes')">{{ old('notes') }}</flux:textarea>
                        <flux:error for="notes" />
                    </flux:field>
                    
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
                <flux:button type="button" variant="ghost" href="{{ route('clients.assets.index', $client) }}">
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