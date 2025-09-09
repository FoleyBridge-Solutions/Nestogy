<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Company Name -->
        <div class="col-span-12-span-2 md:col-span-12-span-1">
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                Company Name
            </label>
            <input type="text" 
                   id="company_name"
                   name="company_name"
                   value="{{ old('company_name', $company->name ?? '') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                   placeholder="Enter company name">
        </div>

        <!-- Business Phone -->
        <div class="col-span-12-span-2 md:col-span-12-span-1">
            <label for="business_phone" class="block text-sm font-medium text-gray-700 mb-1">
                Business Phone
            </label>
            <input type="tel" 
                   id="business_phone"
                   name="business_phone"
                   value="{{ old('business_phone', $settings['business_phone'] ?? '') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                   placeholder="(555) 123-4567">
        </div>

        <!-- Business Email -->
        <div class="col-span-12-span-2 md:col-span-12-span-1">
            <label for="business_email" class="block text-sm font-medium text-gray-700 mb-1">
                Business Email
            </label>
            <input type="email" 
                   id="business_email"
                   name="business_email"
                   value="{{ old('business_email', $settings['business_email'] ?? '') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                   placeholder="info@company.com">
        </div>

        <!-- Tax ID -->
        <div class="col-span-12-span-2 md:col-span-12-span-1">
            <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">
                Tax ID / EIN
            </label>
            <input type="text" 
                   id="tax_id"
                   name="tax_id"
                   value="{{ old('tax_id', $settings['tax_id'] ?? '') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                   placeholder="12-3456789">
        </div>

        <!-- Website -->
        <div class="col-span-12-span-2">
            <label for="website" class="block text-sm font-medium text-gray-700 mb-1">
                Website
            </label>
            <input type="url" 
                   id="website"
                   name="website"
                   value="{{ old('website', $settings['website'] ?? '') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                   placeholder="https://www.example.com">
        </div>

        <!-- Business Address -->
        <div class="col-span-12-span-2">
            <label for="business_address" class="block text-sm font-medium text-gray-700 mb-1">
                Business Address
            </label>
            <textarea id="business_address"
                      name="business_address"
                      rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      placeholder="123 Main St, Suite 100">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" 
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Company Information
        </button>
    </div>
</div>
