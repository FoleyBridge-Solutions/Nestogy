@extends('layouts.app')

@section('title', 'Edit Client - ' . $client->name)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit {{ $client->lead ? 'Lead' : 'Client' }}</h1>
                    <p class="mt-1 text-sm text-gray-500">Update {{ $client->name }}'s information</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('clients.show', $client) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View {{ $client->lead ? 'Lead' : 'Client' }}
                    </a>
                    <a href="{{ $client->lead ? route('clients.leads') : route('clients.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to {{ $client->lead ? 'Leads' : 'Clients' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Form -->
    <div class="bg-white shadow rounded-lg">
        <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="px-4 py-5 sm:p-6">
                <!-- Basic Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Client Type -->
                        <div class="sm:col-span-2">
                            <label class="text-base font-medium text-gray-900">Client Type</label>
                            <p class="text-sm leading-5 text-gray-500">Select the type of {{ $client->lead ? 'lead' : 'client' }}</p>
                            <fieldset class="mt-4">
                                <div class="space-y-4 sm:flex sm:items-center sm:space-y-0 sm:space-x-10">
                                    <div class="flex items-center">
                                        <input id="individual" name="type" type="radio" value="individual" {{ old('type', $client->type) == 'individual' ? 'checked' : '' }} class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <label for="individual" class="ml-3 block text-sm font-medium text-gray-700">Individual</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="business" name="type" type="radio" value="business" {{ old('type', $client->type) == 'business' ? 'checked' : '' }} class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <label for="business" class="ml-3 block text-sm font-medium text-gray-700">Business</label>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Full Name / Company Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company (for individuals) -->
                        <div id="company-field" class="{{ old('type', $client->type) == 'business' ? 'hidden' : '' }}">
                            <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                            <input type="text" name="company" id="company" value="{{ old('company', $client->company) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('company') border-red-300 @enderror">
                            @error('company')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @enderror">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $client->phone) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-300 @enderror">
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                            <input type="url" name="website" id="website" value="{{ old('website', $client->website) }}" placeholder="https://"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('website') border-red-300 @enderror">
                            @error('website')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tax ID -->
                        <div>
                            <label for="tax_id_number" class="block text-sm font-medium text-gray-700">Tax ID / EIN</label>
                            <input type="text" name="tax_id_number" id="tax_id_number" value="{{ old('tax_id_number', $client->tax_id_number) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('tax_id_number') border-red-300 @enderror">
                            @error('tax_id_number')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Referral Source -->
                        <div>
                            <label for="referral" class="block text-sm font-medium text-gray-700">Referral Source</label>
                            <input type="text" name="referral" id="referral" value="{{ old('referral', $client->referral) }}"
                                   placeholder="How did they hear about us?"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('referral') border-red-300 @enderror">
                            @error('referral')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Address Information</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Street Address -->
                        <div class="sm:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                            <input type="text" name="address" id="address" value="{{ old('address', $client->address) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-300 @enderror">
                            @error('address')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $client->city) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('city') border-red-300 @enderror">
                            @error('city')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- State -->
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700">State / Province</label>
                            <input type="text" name="state" id="state" value="{{ old('state', $client->state) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('state') border-red-300 @enderror">
                            @error('state')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ZIP Code -->
                        <div>
                            <label for="zip_code" class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                            <input type="text" name="zip_code" id="zip_code" value="{{ old('zip_code', $client->zip_code) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('zip_code') border-red-300 @enderror">
                            @error('zip_code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                            <select name="country" id="country" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('country') border-red-300 @enderror">
                                <option value="">Select Country</option>
                                <option value="US" {{ old('country', $client->country) == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="CA" {{ old('country', $client->country) == 'CA' ? 'selected' : '' }}>Canada</option>
                                <option value="GB" {{ old('country', $client->country) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                <option value="AU" {{ old('country', $client->country) == 'AU' ? 'selected' : '' }}>Australia</option>
                                <!-- Add more countries as needed -->
                            </select>
                            @error('country')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Billing Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Billing Information</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Status -->
                        <div>
                            <label for="is_active" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="is_active" id="is_active" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="1" {{ old('is_active', $client->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $client->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <!-- Billing Rate -->
                        <div>
                            <label for="rate" class="block text-sm font-medium text-gray-700">Default Billing Rate ($/hour)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="rate" id="rate" value="{{ old('rate', $client->rate) }}" step="0.01" min="0"
                                       class="pl-7 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('rate') border-red-300 @enderror">
                            </div>
                            @error('rate')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency_code" class="block text-sm font-medium text-gray-700">Currency</label>
                            <select name="currency_code" id="currency_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="USD" {{ old('currency_code', $client->currency_code) == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="EUR" {{ old('currency_code', $client->currency_code) == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                <option value="GBP" {{ old('currency_code', $client->currency_code) == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                <option value="CAD" {{ old('currency_code', $client->currency_code) == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                            </select>
                        </div>

                        <!-- Payment Terms -->
                        <div>
                            <label for="net_terms" class="block text-sm font-medium text-gray-700">Payment Terms (days)</label>
                            <select name="net_terms" id="net_terms" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="15" {{ old('net_terms', $client->net_terms) == '15' ? 'selected' : '' }}>Net 15</option>
                                <option value="30" {{ old('net_terms', $client->net_terms) == '30' ? 'selected' : '' }}>Net 30</option>
                                <option value="45" {{ old('net_terms', $client->net_terms) == '45' ? 'selected' : '' }}>Net 45</option>
                                <option value="60" {{ old('net_terms', $client->net_terms) == '60' ? 'selected' : '' }}>Net 60</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div class="mb-8">
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <p class="text-sm text-gray-500 mb-3">Add tags to categorize this {{ $client->lead ? 'lead' : 'client' }}</p>
                    <div id="tag-container" class="flex flex-wrap gap-2 mb-3">
                        <!-- Selected tags will appear here -->
                    </div>
                    <div class="flex">
                        <input type="text" id="tag-input" placeholder="Type a tag and press Enter"
                               class="flex-1 border-gray-300 rounded-l-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" id="add-tag-btn" class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700">
                            Add Tag
                        </button>
                    </div>
                    <input type="hidden" name="tags" id="tags-hidden" value="{{ old('tags', json_encode($client->tags->pluck('name')->toArray())) }}">
                </div>

                <!-- Notes -->
                <div class="mb-8">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Add any additional notes about this {{ $client->lead ? 'lead' : 'client' }}..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-300 @enderror">{{ old('notes', $client->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Avatar Upload -->
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700">Client Avatar</label>
                    <div class="mt-1 flex items-center space-x-5">
                        <div class="flex-shrink-0">
                            @if($client->avatar)
                                <img id="avatar-preview" class="h-20 w-20 rounded-full object-cover border-2 border-gray-300" 
                                     src="{{ Storage::url($client->avatar) }}" alt="Current avatar">
                            @else
                                <img id="avatar-preview" class="h-20 w-20 rounded-full object-cover border-2 border-gray-300" 
                                     src="https://via.placeholder.com/80x80/e5e7eb/6b7280?text=Avatar" alt="Avatar preview">
                            @endif
                        </div>
                        <div>
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="sr-only" onchange="previewAvatar(this)">
                            <label for="avatar" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Change avatar
                            </label>
                            @if($client->avatar)
                            <button type="button" onclick="removeAvatar()" class="ml-3 py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50">
                                Remove
                            </button>
                            <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                            @endif
                            <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                        </div>
                    </div>
                    @error('avatar')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 space-x-3">
                <a href="{{ route('clients.show', $client) }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update {{ $client->lead ? 'Lead' : 'Client' }}
                </button>
            </div>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white shadow rounded-lg border border-red-200">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Danger Zone</h3>
            <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                <div>
                    <h4 class="text-sm font-medium text-red-800">Delete {{ $client->lead ? 'Lead' : 'Client' }}</h4>
                    <p class="text-sm text-red-600">Permanently delete this {{ $client->lead ? 'lead' : 'client' }} and all associated data. This action cannot be undone.</p>
                </div>
                <button onclick="confirmDelete()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    Delete {{ $client->lead ? 'Lead' : 'Client' }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Delete {{ $client->lead ? 'Lead' : 'Client' }}</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to delete <strong>{{ $client->name }}</strong>? This will also delete all associated tickets, invoices, and assets. This action cannot be undone.</p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600">Delete</button>
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Toggle company field based on client type
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const companyField = document.getElementById('company-field');
        if (this.value === 'business') {
            companyField.classList.add('hidden');
        } else {
            companyField.classList.remove('hidden');
        }
    });
});

// Avatar preview
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
            document.getElementById('remove_avatar').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Remove avatar
function removeAvatar() {
    document.getElementById('avatar-preview').src = 'https://via.placeholder.com/80x80/e5e7eb/6b7280?text=Avatar';
    document.getElementById('avatar').value = '';
    document.getElementById('remove_avatar').value = '1';
}

// Tags functionality
let tags = [];
try {
    tags = JSON.parse(document.getElementById('tags-hidden').value || '[]');
} catch (e) {
    tags = [];
}

function renderTags() {
    const container = document.getElementById('tag-container');
    container.innerHTML = '';
    
    tags.forEach((tag, index) => {
        const tagEl = document.createElement('span');
        tagEl.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800';
        tagEl.innerHTML = `
            ${tag}
            <button type="button" onclick="removeTag(${index})" class="ml-2 inline-flex items-center justify-center w-4 h-4 text-blue-400 hover:text-blue-600">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        `;
        container.appendChild(tagEl);
    });
    
    document.getElementById('tags-hidden').value = JSON.stringify(tags);
}

function addTag() {
    const input = document.getElementById('tag-input');
    const tag = input.value.trim();
    
    if (tag && !tags.includes(tag)) {
        tags.push(tag);
        renderTags();
        input.value = '';
    }
}

function removeTag(index) {
    tags.splice(index, 1);
    renderTags();
}

document.getElementById('add-tag-btn').addEventListener('click', addTag);
document.getElementById('tag-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addTag();
    }
});

// Initial render
renderTags();

// Delete confirmation
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("clients.destroy", $client) }}';
    
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    
    const tokenField = document.createElement('input');
    tokenField.type = 'hidden';
    tokenField.name = '_token';
    tokenField.value = '{{ csrf_token() }}';
    
    form.appendChild(methodField);
    form.appendChild(tokenField);
    document.body.appendChild(form);
    form.submit();
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = ['name', 'email'];
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-300');
        } else {
            field.classList.remove('border-red-300');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});

// Auto-format phone number
document.getElementById('phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    } else if (value.length >= 3) {
        value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
    }
    e.target.value = value;
});

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endpush