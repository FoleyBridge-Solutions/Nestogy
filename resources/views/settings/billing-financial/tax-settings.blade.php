<x-settings.form-section title="Tax Configuration">
    <x-slot name="icon">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
        </svg>
    </x-slot>

    <div class="space-y-6">
        <x-settings.toggle-switch 
            model="formData.tax_enabled"
            label="Enable Tax Calculations"
            description="Automatically calculate taxes on invoices and orders" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Default Tax Rate (%)
                </label>
                <input type="number" 
                       x-model="formData.default_tax_rate"
                       step="0.01"
                       min="0"
                       max="100"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tax ID Number
                </label>
                <input type="text" 
                       x-model="formData.tax_id"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Tax Calculation Method
            </label>
            <select x-model="formData.tax_calculation_method"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="exclusive">Tax Exclusive (added to price)</option>
                <option value="inclusive">Tax Inclusive (included in price)</option>
            </select>
        </div>

        <!-- Regional Tax Rates -->
        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Regional Tax Rates</h3>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Region
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tax Rate (%)
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="region in (formData.tax_regions || [])" :key="region.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="region.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="region.rate + '%'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="region.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                          x-text="region.active ? 'Active' : 'Inactive'">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button type="button" class="text-blue-600 hover:text-blue-900">Edit</button>
                                </td>
                            </tr>
                        </template>
                        <!-- Default row if no regions -->
                        <tr x-show="!formData.tax_regions || formData.tax_regions.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No regional tax rates configured
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">
                + Add Regional Tax Rate
            </button>
        </div>
    </div>
</x-settings.form-section>