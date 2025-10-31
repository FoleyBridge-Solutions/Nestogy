<form wire:submit.prevent="save" autocomplete="off">
    <flux:card class="mb-6">
        <div class="p-6 space-y-6">
            <flux:heading size="lg">Add Location for {{ $client->name }}</flux:heading>
            <flux:subheading>Create a new location for {{ $client->name }}.</flux:subheading>

            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <!-- Location Name -->
                <div class="sm:col-span-4">
                    <flux:field>
                        <flux:label>Location Name</flux:label>
                        <flux:input wire:model="name" placeholder="e.g. Main Office, Warehouse, etc." autofocus />
                        <flux:error name="name" />
                    </flux:field>
                </div>
                <!-- Primary -->
                <div class="sm:col-span-2 flex items-center mt-7">
                    <flux:checkbox wire:model="primary">Primary Location</flux:checkbox>
                </div>
                <!-- Description -->
                <div class="sm:col-span-6">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" placeholder="Optional description of this location" rows="2" />
                        <flux:error name="description" />
                    </flux:field>
                </div>
                <!-- Address Line 1 + Google Autocomplete -->
                <div class="sm:col-span-6">
                    <flux:field>
                        <flux:label>Address Line 1</flux:label>
                        <flux:input wire:model="address_line_1" id="address_line_1" placeholder="Start typing an address..." autocomplete="street-address" />
                        <flux:error name="address_line_1" />
                    </flux:field>
                </div>
                <!-- Address Line 2 -->
                <div class="sm:col-span-6">
                    <flux:field>
                        <flux:label>Address Line 2</flux:label>
                        <flux:input wire:model="address_line_2" id="address_line_2" placeholder="Suite, Apt, etc." />
                        <flux:error name="address_line_2" />
                    </flux:field>
                </div>
                <!-- City -->
                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>City</flux:label>
                        <flux:input wire:model="city" id="city" />
                        <flux:error name="city" />
                    </flux:field>
                </div>
                <!-- State -->
                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>State</flux:label>
                        <flux:input wire:model="state" id="state" />
                        <flux:error name="state" />
                    </flux:field>
                </div>
                <!-- Zip -->
                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>ZIP Code</flux:label>
                        <flux:input wire:model="zip_code" id="zip_code" />
                        <flux:error name="zip_code" />
                    </flux:field>
                </div>
                <!-- Country -->
                <div class="sm:col-span-3">
                    <flux:field>
                        <flux:label>Country</flux:label>
                        <flux:select wire:model="country" id="country">
                            <option value="US">United States</option>
                            <option value="CA">Canada</option>
                            <option value="MX">Mexico</option>
                            <option value="GB">United Kingdom</option>
                            <option value="AU">Australia</option>
                        </flux:select>
                        <flux:error name="country" />
                    </flux:field>
                </div>
                <!-- Phone -->
                <div class="sm:col-span-3">
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input wire:model="phone" placeholder="(555) 123-4567" />
                        <flux:error name="phone" />
                    </flux:field>
                </div>
                <!-- Contact Dropdown -->
                <div class="sm:col-span-6">
                    <flux:field>
                        <flux:label>Contact</flux:label>
                        <flux:select wire:model="contact_id">
                            <option value="">Select a contact (optional)</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }}{{ $contact->title ? ' - ' . $contact->title : '' }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="contact_id" />
                    </flux:field>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-8">
                <flux:button type="button" variant="subtle" @click="window.history.back()">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create Location</flux:button>
            </div>
        </div>
    </flux:card>
</form>
@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places&callback=initAutocomplete" async defer></script>
<script>
let autocomplete;
function dispatchInput(el) {
    if (!el) return;
    const event = new Event('input', { bubbles: true });
    el.dispatchEvent(event);
}
function initAutocomplete() {
    const addressInput = document.getElementById('address_line_1');
    if (!addressInput) return;
    autocomplete = new google.maps.places.Autocomplete(addressInput, {
        types: ['address'],
        componentRestrictions: { country: ['us', 'ca'] },
        fields: ['address_components', 'formatted_address', 'geometry', 'name']
    });
    autocomplete.addListener('place_changed', fillInAddress);
}
function fillInAddress() {
    const place = autocomplete.getPlace();
    if (!place?.address_components) return;

    const map = {};
    for (const component of place.address_components) {
        const types = component.types || [];
        if (types.includes('street_number')) map.street_number = component.long_name;
        if (types.includes('route')) map.route = component.long_name;
        if (types.includes('subpremise')) map.subpremise = component.long_name;
        if (types.includes('locality')) map.city = component.long_name;
        if (types.includes('administrative_area_level_1')) map.state = component.short_name;
        if (types.includes('postal_code')) map.postal_code = component.long_name;
        if (types.includes('country')) map.country = component.short_name;
    }

    const address1 = document.getElementById('address_line_1');
    const address2 = document.getElementById('address_line_2');
    const city = document.getElementById('city');
    const state = document.getElementById('state');
    const zip = document.getElementById('zip_code');
    const country = document.getElementById('country');

    let street = '';
    if (map.street_number) street += map.street_number;
    if (map.route) street += (street ? ' ' : '') + map.route;

    if (street && address1) { address1.value = street; dispatchInput(address1); }
    if (map.subpremise && address2) { address2.value = map.subpremise; dispatchInput(address2); }
    if (map.city && city) { city.value = map.city; dispatchInput(city); }
    if (map.state && state) { state.value = map.state; dispatchInput(state); }
    if (map.postal_code && zip) { zip.value = map.postal_code; dispatchInput(zip); }
    if (map.country && country) { country.value = map.country; dispatchInput(country); }
}
</script>
@endpush
