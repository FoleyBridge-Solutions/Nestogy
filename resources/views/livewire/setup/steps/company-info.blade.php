<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg" class="flex items-center">
            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Company Information
        </flux:heading>
        <flux:text class="mt-2">
            Tell us about your MSP business. This information will be used throughout the system.
        </flux:text>
    </div>

    <div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Name (Full Width) -->
            <div class="col-span-2">
                <flux:input 
                    wire:model.defer="company_name" 
                    label="Company Name" 
                    required
                    placeholder="Your MSP Company Name"
                    :invalid="$errors->has('company_name')" />
            </div>

            <!-- Company Email -->
            <flux:input 
                wire:model.defer="company_email" 
                type="email" 
                label="Company Email" 
                required
                placeholder="info@yourcompany.com"
                :invalid="$errors->has('company_email')" />

            <!-- Phone Number -->
            <flux:input 
                wire:model.defer="company_phone" 
                type="tel"
                label="Phone Number"
                placeholder="(555) 123-4567" />

            <!-- Street Address (Full Width) -->
            <div class="col-span-2">
                <flux:input 
                    wire:model.defer="company_address" 
                    label="Street Address"
                    placeholder="123 Main Street" />
            </div>

            <!-- City -->
            <flux:input 
                wire:model.defer="company_city" 
                label="City"
                placeholder="Your City" />

            <!-- State/Province -->
            <flux:input 
                wire:model.defer="company_state" 
                label="State/Province"
                placeholder="State/Province" />

            <!-- ZIP/Postal Code -->
            <flux:input 
                wire:model.defer="company_zip" 
                label="ZIP/Postal Code"
                placeholder="12345" />

            <!-- Country -->
            <flux:input 
                wire:model.defer="company_country" 
                label="Country"
                placeholder="United States" />

            <!-- Website -->
            <flux:input 
                wire:model.defer="company_website" 
                type="url"
                label="Website"
                placeholder="https://yourcompany.com" />

            <!-- Default Currency -->
            <flux:select 
                wire:model.defer="currency" 
                label="Default Currency" 
                required
                :invalid="$errors->has('currency')">
                @foreach(\App\Models\Company::SUPPORTED_CURRENCIES as $code => $name)
                    <flux:select.option value="{{ $code }}">{{ $code }} - {{ $name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>
</flux:card>