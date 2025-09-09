@extends('layouts.app')

@section('title', 'Edit Asset')

@section('content')
<div class="w-full px-6">
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900">
                    <h3 class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title">Edit Asset: {{ $asset->name }}</h3>
                </div>
                
                <form action="{{ route('assets.update', $asset) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="p-6">
                        <div class="flex flex-wrap -mx-4">
                            <!-- Basic Information -->
                            <div class="md:w-1/2 px-6">
                                <h5 class="mb-6">Basic Information</h5>
                                
                                <div class="mb-6">
                                    <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1">Client <span class="text-red-600">*</span></label>
                                    <select name="client_id" id="client_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') border-red-500 @enderror" required>
                                        <option value="">Select Client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id', $asset->client_id) == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1">Type <span class="text-red-600">*</span></label>
                                    <select name="type" id="type" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('type') border-red-500 @enderror" required>
                                        <option value="">Select Type</option>
                                        @foreach(App\Models\Asset::TYPES as $type)
                                            <option value="{{ $type }}" {{ old('type', $asset->type) == $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <input type="text" name="name" id="name" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror" 
                                           value="{{ old('name', $asset->name) }}" placeholder="Asset name or tag" required>
                                    @error('name')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea name="description" id="description" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror" 
                                              rows="3" placeholder="Asset description">{{ old('description', $asset->description) }}</textarea>
                                    @error('description')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select name="status" id="status" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status') border-red-500 @enderror">
                                        @foreach(App\Models\Asset::STATUSES as $status)
                                            <option value="{{ $status }}" {{ old('status', $asset->status) == $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Hardware Information -->
                            <div class="md:w-1/2 px-6">
                                <h5 class="mb-6">Hardware Information</h5>

                                <div class="mb-6">
                                    <label for="make" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Make <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <input type="text" name="make" id="make" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('make') border-red-500 @enderror" 
                                           value="{{ old('make', $asset->make) }}" placeholder="e.g., Dell, HP, Cisco" required>
                                    @error('make')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                                    <input type="text" name="model" id="model" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('model') border-red-500 @enderror" 
                                           value="{{ old('model', $asset->model) }}" placeholder="e.g., OptiPlex 7080">
                                    @error('model')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="serial" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serial Number</label>
                                    <input type="text" name="serial" id="serial" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('serial') border-red-500 @enderror" 
                                           value="{{ old('serial', $asset->serial) }}" placeholder="Serial number">
                                    @error('serial')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="os" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Operating System</label>
                                    <input type="text" name="os" id="os" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('os') border-red-500 @enderror" 
                                           value="{{ old('os', $asset->os) }}" placeholder="e.g., Windows 11 Pro">
                                    @error('os')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor</label>
                                    <select name="vendor_id" id="vendor_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('vendor_id') border-red-500 @enderror">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $asset->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="flex flex-wrap -mx-4">
                            <!-- Network Information -->
                            <div class="flex-1 px-6-md-6">
                                <h5 class="mb-6">Network Information</h5>

                                <div class="mb-6">
                                    <label for="ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IP Address</label>
                                    <div class="flex">
                                        <input type="text" name="ip" id="ip" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('ip') border-red-500 @enderror" 
                                               value="{{ old('ip', $asset->ip) }}" placeholder="192.168.1.100"
                                               {{ $asset->ip === 'DHCP' ? 'disabled' : '' }}>
                                        <div class="flex-text">
                                            <input type="checkbox" name="dhcp" id="dhcp" value="1" 
                                                   {{ old('dhcp', $asset->ip === 'DHCP') ? 'checked' : '' }}>
                                            <label for="dhcp" class="ml-2 mb-0">DHCP</label>
                                        </div>
                                    </div>
                                    @error('ip')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="nat_ip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NAT IP</label>
                                    <input type="text" name="nat_ip" id="nat_ip" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('nat_ip') border-red-500 @enderror" 
                                           value="{{ old('nat_ip', $asset->nat_ip) }}" placeholder="External IP">
                                    @error('nat_ip')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="mac" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">MAC Address</label>
                                    <input type="text" name="mac" id="mac" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('mac') border-red-500 @enderror" 
                                           value="{{ old('mac', $asset->mac) }}" placeholder="00:00:00:00:00:00" maxlength="17">
                                    @error('mac')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="network_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Network</label>
                                    <select name="network_id" id="network_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('network_id') border-red-500 @enderror">
                                        <option value="">Select Network</option>
                                        @foreach($networks as $network)
                                            <option value="{{ $network->id }}" {{ old('network_id', $asset->network_id) == $network->id ? 'selected' : '' }}>
                                                {{ $network->name }} ({{ $network->network }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('network_id')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="uri" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URI/URL</label>
                                    <input type="text" name="uri" id="uri" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('uri') border-red-500 @enderror" 
                                           value="{{ old('uri', $asset->uri) }}" placeholder="https://device.local">
                                    @error('uri')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="uri_2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secondary URI/URL</label>
                                    <input type="text" name="uri_2" id="uri_2" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('uri_2') border-red-500 @enderror" 
                                           value="{{ old('uri_2', $asset->uri_2) }}" placeholder="https://device-mgmt.local">
                                    @error('uri_2')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Assignment & Location -->
                            <div class="flex-1 px-6-md-6">
                                <h5 class="mb-6">Assignment & Location</h5>

                                <div class="mb-6">
                                    <label for="location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                                    <select name="location_id" id="location_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('location_id') border-red-500 @enderror">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ old('location_id', $asset->location_id) == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location_id')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="contact_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assigned To</label>
                                    <select name="contact_id" id="contact_id" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('contact_id') border-red-500 @enderror">
                                        <option value="">Select Contact</option>
                                        @foreach($contacts as $contact)
                                            <option value="{{ $contact->id }}" {{ old('contact_id', $asset->contact_id) == $contact->id ? 'selected' : '' }}>
                                                {{ $contact->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('contact_id')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <h5 class="mb-6 mt-6">Important Dates</h5>

                                <div class="mb-6">
                                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purchase Date</label>
                                    <input type="date" name="purchase_date" id="purchase_date" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('purchase_date') border-red-500 @enderror" 
                                           value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                                    @error('purchase_date')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="warranty_expire" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Warranty Expiration</label>
                                    <input type="date" name="warranty_expire" id="warranty_expire" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('warranty_expire') border-red-500 @enderror" 
                                           value="{{ old('warranty_expire', $asset->warranty_expire?->format('Y-m-d')) }}">
                                    @error('warranty_expire')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                    @if($asset->is_warranty_expired)
                                        <small class="text-red-600 dark:text-red-400">Warranty has expired</small>
                                    @elseif($asset->is_warranty_expiring_soon)
                                        <small class="text-yellow-600 dark:text-yellow-400">Warranty expiring soon</small>
                                    @endif
                                </div>

                                <div class="mb-6">
                                    <label for="install_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Install Date</label>
                                    <input type="date" name="install_date" id="install_date" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('install_date') border-red-500 @enderror" 
                                           value="{{ old('install_date', $asset->install_date?->format('Y-m-d')) }}">
                                    @error('install_date')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-12">
                                <h5 class="mb-6">Additional Information</h5>

                                <div class="mb-6">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea name="notes" id="notes" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('notes') border-red-500 @enderror" 
                                              rows="4" placeholder="Additional notes about this asset">{{ old('notes', $asset->notes) }}</textarea>
                                    @error('notes')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-6">
                                    <label for="files" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attachments</label>
                                    <input type="file" name="files[]" id="files" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('files.*') border-red-500 @enderror" 
                                           multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                    <small class="text-gray-600 dark:text-gray-400 dark:text-gray-400">You can attach multiple files (PDF, Word, Excel, Images)</small>
                                    @error('files.*')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($asset->files->count() > 0)
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Attachments</label>
                                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($asset->files as $file)
                                                <div class="divide-y divide-gray-200 dark:divide-gray-700-item flex justify-between items-center">
                                                    <div>
                                                        <i class="fas fa-file mr-2"></i>
                                                        {{ $file->name }}
                                                        <small class="text-gray-600 dark:text-gray-400 dark:text-gray-400">({{ number_format($file->size / 1024, 2) }} KB)</small>
                                                    </div>
                                                    <div>
                                                        <a href="{{ route('files.download', $file) }}" class="px-4 py-2 font-medium rounded-md transition-colors px-6 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-primary">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button type="button" class="px-4 py-2 font-medium rounded-md transition-colors px-6 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-danger delete-file" 
                                                                data-file-id="{{ $file->id }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Metadata -->
                        <hr class="my-4">
                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-12">
                                <h5 class="mb-6">Metadata</h5>
                                <div class="flex flex-wrap -mx-4 text-gray-600 dark:text-gray-400 small">
                                    <div class="flex-1 px-6-md-6">
                                        <p><strong>Created:</strong> {{ $asset->created_at->format('M d, Y g:i A') }}</p>
                                        <p><strong>Last Updated:</strong> {{ $asset->updated_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                    <div class="flex-1 px-6-md-6">
                                        @if($asset->accessed_at)
                                            <p><strong>Last Accessed:</strong> {{ $asset->accessed_at->format('M d, Y g:i A') }}</p>
                                        @endif
                                        @if($asset->age_in_years !== null)
                                            <p><strong>Asset Age:</strong> {{ $asset->age_in_years }} years</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-footer">
                        <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save"></i> Update Asset
                        </button>
                        <a href="{{ route('assets.show', $asset) }}" class="inline-flex items-center px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-disable IP field when DHCP is checked
    const dhcpCheckbox = document.getElementById('dhcp');
    const ipField = document.getElementById('ip');
    
    dhcpCheckbox.addEventListener('change', function() {
        if (this.checked) {
            ipField.value = 'DHCP';
            ipField.disabled = true;
        } else {
            ipField.value = '';
            ipField.disabled = false;
        }
    });

    // Format MAC address as user types
    const macField = document.getElementById('mac');
    macField.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9a-fA-F]/g, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length && i < 12; i++) {
            if (i > 0 && i % 2 === 0) {
                formattedValue += ':';
            }
            formattedValue += value[i];
        }
        
        e.target.value = formattedValue.toUpperCase();
    });

    // Delete file functionality
    document.querySelectorAll('.delete-file').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this file?')) {
                const fileId = this.dataset.fileId;
                fetch(`/files/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.divide-y divide-gray-200 dark:divide-gray-700-item').remove();
                    }
                });
            }
        });
    });
});
</script>
@endpush
