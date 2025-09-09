@extends('layouts.app')

@section('title', 'Edit Location')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-8 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Location: {{ $location->name }}</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Update location details for {{ $client->name }}.</p>
                    </div>
                    <div>
                        <a href="{{ route('clients.locations.show', [$client, $location]) }}" 
                           class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Location
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('clients.locations.update', [$client, $location]) }}">
                @csrf
                @method('PUT')
                
                <div class="px-6 py-8 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <!-- Location Name -->
                        <div class="sm:flex-1 px-6-span-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Location Name</label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       value="{{ old('name', $location->name) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('name') border-red-300 @enderror" 
                                       placeholder="e.g. Main Office, Warehouse, etc.">
                            </div>
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Primary Location -->
                        <div class="sm:flex-1 px-6-span-2">
                            <div class="flex items-center h-5 mt-6">
                                <input id="primary" 
                                       name="primary" 
                                       type="checkbox" 
                                       value="1"
                                       {{ old('primary', $location->primary) ? 'checked' : '' }}
                                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label for="primary" class="ml-2 text-sm text-gray-700">Primary Location</label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="sm:flex-1 px-6-span-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <div class="mt-1">
                                <textarea name="description" 
                                          id="description" 
                                          rows="3" 
                                          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('description') border-red-300 @enderror" 
                                          placeholder="Optional description of this location">{{ old('description', $location->description) }}</textarea>
                            </div>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address Line 1 with Google Places Autocomplete -->
                        <div class="sm:flex-1 px-6-span-6">
                            <label for="address_line_1" class="block text-sm font-medium text-gray-700">Address Line 1</label>
                            <div class="mt-1">
                                @php
                                    // Split the address back into address lines for editing
                                    $addressParts = explode(', ', $location->address ?: '', 2);
                                    $addressLine1 = $addressParts[0] ?? '';
                                    $addressLine2 = $addressParts[1] ?? '';
                                @endphp
                                <input type="text" 
                                       name="address_line_1" 
                                       id="address_line_1" 
                                       value="{{ old('address_line_1', $addressLine1) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('address_line_1') border-red-300 @enderror" 
                                       placeholder="Start typing an address..."
                                       autocomplete="street-address">
                            </div>
                            @error('address_line_1')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address Line 2 -->
                        <div class="sm:flex-1 px-6-span-6">
                            <label for="address_line_2" class="block text-sm font-medium text-gray-700">Address Line 2</label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="address_line_2" 
                                       id="address_line_2" 
                                       value="{{ old('address_line_2', $addressLine2) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('address_line_2') border-red-300 @enderror" 
                                       placeholder="Apartment, suite, unit, building, floor, etc.">
                            </div>
                            @error('address_line_2')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City -->
                        <div class="sm:flex-1 px-6-span-2">
                            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="city" 
                                       id="city" 
                                       value="{{ old('city', $location->city) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('city') border-red-300 @enderror">
                            </div>
                            @error('city')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- State -->
                        <div class="sm:flex-1 px-6-span-2">
                            <label for="state" class="block text-sm font-medium text-gray-700">State</label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="state" 
                                       id="state" 
                                       value="{{ old('state', $location->state) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('state') border-red-300 @enderror">
                            </div>
                            @error('state')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ZIP Code -->
                        <div class="sm:flex-1 px-6-span-2">
                            <label for="zip_code" class="block text-sm font-medium text-gray-700">ZIP Code</label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="zip_code" 
                                       id="zip_code" 
                                       value="{{ old('zip_code', $location->zip) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('zip_code') border-red-300 @enderror">
                            </div>
                            @error('zip_code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div class="sm:flex-1 px-6-span-3">
                            <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                            <div class="mt-1">
                                <select name="country" 
                                        id="country" 
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('country') border-red-300 @enderror">
                                    <option value="US" {{ old('country', $location->country) === 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="CA" {{ old('country', $location->country) === 'CA' ? 'selected' : '' }}>Canada</option>
                                    <option value="MX" {{ old('country', $location->country) === 'MX' ? 'selected' : '' }}>Mexico</option>
                                    <option value="GB" {{ old('country', $location->country) === 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="AU" {{ old('country', $location->country) === 'AU' ? 'selected' : '' }}>Australia</option>
                                </select>
                            </div>
                            @error('country')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="sm:flex-1 px-6-span-3">
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="phone" 
                                       id="phone" 
                                       value="{{ old('phone', $location->phone) }}"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('phone') border-red-300 @enderror" 
                                       placeholder="(555) 123-4567">
                            </div>
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contact -->
                        <div class="sm:flex-1 px-6-span-6">
                            <label for="contact_id" class="block text-sm font-medium text-gray-700">Contact</label>
                            <div class="mt-1">
                                <select name="contact_id" 
                                        id="contact_id" 
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('contact_id') border-red-300 @enderror">
                                    <option value="">Select a contact (optional)</option>
                                    @foreach($contacts as $contact)
                                        <option value="{{ $contact->id }}" {{ old('contact_id', $location->contact_id) == $contact->id ? 'selected' : '' }}>
                                            {{ $contact->name }}{{ $contact->title ? ' - ' . $contact->title : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('contact_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="px-6 py-6 bg-gray-50 text-right sm:px-6">
                    <button type="button" 
                            onclick="window.history.back()" 
                            class="bg-white py-2 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="ml-3 inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<!-- Google Maps JavaScript API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places&callback=initAutocomplete" async defer></script>

<script>
let autocomplete;

function initAutocomplete() {
    const addressInput = document.getElementById('address_line_1');
    
    // Initialize the autocomplete object
    autocomplete = new google.maps.places.Autocomplete(addressInput, {
        types: ['address'],
        componentRestrictions: { country: ['us', 'ca'] }, // Restrict to US and Canada
        fields: ['address_components', 'formatted_address', 'geometry', 'name']
    });

    // Add listener for when a place is selected
    autocomplete.addListener('place_changed', fillInAddress);
}

function fillInAddress() {
    const place = autocomplete.getPlace();
    
    if (!place.address_components) {
        console.log('No address components found');
        return;
    }

    // Clear existing values
    document.getElementById('address_line_2').value = '';
    document.getElementById('city').value = '';
    document.getElementById('state').value = '';
    document.getElementById('zip_code').value = '';
    document.getElementById('country').value = '';

    // Parse address components
    const addressComponents = {};
    place.address_components.forEach(component => {
        const types = component.types;
        
        if (types.includes('street_number')) {
            addressComponents.street_number = component.long_name;
        }
        if (types.includes('route')) {
            addressComponents.route = component.long_name;
        }
        if (types.includes('subpremise')) {
            addressComponents.subpremise = component.long_name;
        }
        if (types.includes('locality')) {
            addressComponents.city = component.long_name;
        }
        if (types.includes('administrative_area_level_1')) {
            addressComponents.state = component.short_name;
        }
        if (types.includes('postal_code')) {
            addressComponents.zip_code = component.long_name;
        }
        if (types.includes('country')) {
            addressComponents.country = component.short_name;
        }
    });

    // Fill in the form fields
    let streetAddress = '';
    if (addressComponents.street_number) {
        streetAddress += addressComponents.street_number;
    }
    if (addressComponents.route) {
        streetAddress += (streetAddress ? ' ' : '') + addressComponents.route;
    }
    
    if (streetAddress) {
        document.getElementById('address_line_1').value = streetAddress;
    }
    
    if (addressComponents.subpremise) {
        document.getElementById('address_line_2').value = addressComponents.subpremise;
    }
    
    if (addressComponents.city) {
        document.getElementById('city').value = addressComponents.city;
    }
    
    if (addressComponents.state) {
        document.getElementById('state').value = addressComponents.state;
    }
    
    if (addressComponents.zip_code) {
        document.getElementById('zip_code').value = addressComponents.zip_code;
    }
    
    if (addressComponents.country) {
        // Map country codes to the dropdown values
        const countryMapping = {
            'US': 'US',
            'CA': 'CA',
            'MX': 'MX',
            'GB': 'GB',
            'AU': 'AU'
        };
        
        const countryCode = countryMapping[addressComponents.country];
        if (countryCode) {
            document.getElementById('country').value = countryCode;
        }
    }
}

// Bias the autocomplete object to the user's geographical location
function geolocate() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const geolocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            const circle = new google.maps.Circle({
                center: geolocation,
                radius: position.coords.accuracy
            });
            autocomplete.setBounds(circle.getBounds());
        });
    }
}

// Optional: Add geolocation bias when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add focus listener to trigger geolocation
    const addressInput = document.getElementById('address_line_1');
    if (addressInput) {
        addressInput.addEventListener('focus', geolocate);
    }
});
</script>
@endpush

@endsection
