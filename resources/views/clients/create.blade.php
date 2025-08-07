@extends('layouts.app')

@section('title', 'Create Client')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create {{ request('lead') ? 'Lead' : 'Client' }}</h1>
                    <p class="mt-1 text-sm text-gray-500">Add a new {{ request('lead') ? 'lead' : 'client' }} to your system</p>
                </div>
                <div>
                    <a href="{{ request('lead') ? route('clients.leads') : route('clients.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to {{ request('lead') ? 'Leads' : 'Clients' }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Form -->
    <div class="bg-white shadow rounded-lg">
        <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <!-- Hidden field for lead status -->
            <input type="hidden" name="lead" value="{{ request('lead', 0) }}">
            
            <div class="px-4 py-5 sm:p-6">
                <!-- Basic Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Client Type -->
                        <div class="sm:col-span-2">
                            <label class="text-base font-medium text-gray-900">Client Type</label>
                            <p class="text-sm leading-5 text-gray-500">Select the type of {{ request('lead') ? 'lead' : 'client' }} you're adding</p>
                            <fieldset class="mt-4">
                                <div class="space-y-4 sm:flex sm:items-center sm:space-y-0 sm:space-x-10">
                                    <div class="flex items-center">
                                        <input id="individual" name="type" type="radio" value="individual" {{ old('type', 'individual') == 'individual' ? 'checked' : '' }} class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                        <label for="individual" class="ml-3 block text-sm font-medium text-gray-700">Individual</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="business" name="type" type="radio" value="business" {{ old('type') == 'business' ? 'checked' : '' }} class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
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
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company (for individuals) -->
                        <div id="company-field" class="{{ old('type', 'individual') == 'business' ? 'hidden' : '' }}">
                            <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                            <input type="text" name="company" id="company" value="{{ old('company') }}"
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
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @enderror">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-300 @enderror">
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                            <input type="url" name="website" id="website" value="{{ old('website') }}" placeholder="https://"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('website') border-red-300 @enderror">
                            @error('website')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tax ID -->
                        <div>
                            <label for="tax_id_number" class="block text-sm font-medium text-gray-700">Tax ID / EIN</label>
                            <input type="text" name="tax_id_number" id="tax_id_number" value="{{ old('tax_id_number') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('tax_id_number') border-red-300 @enderror">
                            @error('tax_id_number')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Referral Source -->
                        <div>
                            <label for="referral" class="block text-sm font-medium text-gray-700">Referral Source</label>
                            <input type="text" name="referral" id="referral" value="{{ old('referral') }}"
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
                            <input type="text" name="address" id="address" value="{{ old('address') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-300 @enderror">
                            @error('address')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" name="city" id="city" value="{{ old('city') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('city') border-red-300 @enderror">
                            @error('city')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- State -->
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700">State / Province</label>
                            <input type="text" name="state" id="state" value="{{ old('state') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('state') border-red-300 @enderror">
                            @error('state')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ZIP Code -->
                        <div>
                            <label for="zip_code" class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                            <input type="text" name="zip_code" id="zip_code" value="{{ old('zip_code') }}"
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
                                <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="CA" {{ old('country') == 'CA' ? 'selected' : '' }}>Canada</option>
                                <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                <option value="AU" {{ old('country') == 'AU' ? 'selected' : '' }}>Australia</option>
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
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <!-- Billing Rate -->
                        <div>
                            <label for="rate" class="block text-sm font-medium text-gray-700">Default Billing Rate ($/hour)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="rate" id="rate" value="{{ old('rate') }}" step="0.01" min="0"
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
                                <option value="USD" {{ old('currency_code', 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                <option value="GBP" {{ old('currency_code') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                <option value="CAD" {{ old('currency_code') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                            </select>
                        </div>

                        <!-- Payment Terms -->
                        <div>
                            <label for="net_terms" class="block text-sm font-medium text-gray-700">Payment Terms (days)</label>
                            <select name="net_terms" id="net_terms" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="15" {{ old('net_terms') == '15' ? 'selected' : '' }}>Net 15</option>
                                <option value="30" {{ old('net_terms', '30') == '30' ? 'selected' : '' }}>Net 30</option>
                                <option value="45" {{ old('net_terms') == '45' ? 'selected' : '' }}>Net 45</option>
                                <option value="60" {{ old('net_terms') == '60' ? 'selected' : '' }}>Net 60</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div class="mb-8">
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <p class="text-sm text-gray-500 mb-3">Add tags to categorize this {{ request('lead') ? 'lead' : 'client' }}</p>
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
                    <input type="hidden" name="tags" id="tags-hidden" value="{{ old('tags', '[]') }}">
                </div>

                <!-- Notes -->
                <div class="mb-8">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Add any additional notes about this {{ request('lead') ? 'lead' : 'client' }}..."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Avatar Upload -->
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700">Client Avatar</label>
                    <div class="mt-1 flex items-center space-x-5">
                        <div class="flex-shrink-0">
                            <img id="avatar-preview" class="h-20 w-20 rounded-full object-cover border-2 border-gray-300" 
                                 src="https://via.placeholder.com/80x80/e5e7eb/6b7280?text=Avatar" alt="Avatar preview">
                        </div>
                        <div>
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="sr-only" onchange="previewAvatar(this)">
                            <label for="avatar" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Choose file
                            </label>
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
                <a href="{{ request('lead') ? route('clients.leads') : route('clients.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create {{ request('lead') ? 'Lead' : 'Client' }}
                </button>
            </div>
        </form>
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
        };
        reader.readAsDataURL(input.files[0]);
    }
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
</script>
@endpush