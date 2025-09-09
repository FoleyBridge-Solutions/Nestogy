<x-settings.form-section title="Financial Reporting Configuration">
    <x-slot name="icon">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
    </x-slot>

    <div class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Fiscal Year Start
            </label>
            <input type="date" 
                   x-model="formData.fiscal_year_start"
                   class="w-full md:w-64 border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Reporting Currency
            </label>
            <select x-model="formData.reporting_currency"
                    class="w-full md:w-64 border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
                <option value="CAD">CAD - Canadian Dollar</option>
                <option value="AUD">AUD - Australian Dollar</option>
            </select>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Automated Reports</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Monthly Revenue Report</p>
                        <p class="text-sm text-gray-500">Sent on the 1st of each month</p>
                    </div>
                    <x-settings.toggle-switch 
                        model="formData.monthly_revenue_report"
                        class="" />
                </div>
                
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Quarterly Financial Summary</p>
                        <p class="text-sm text-gray-500">Comprehensive financial overview</p>
                    </div>
                    <x-settings.toggle-switch 
                        model="formData.quarterly_summary"
                        class="" />
                </div>
                
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Tax Report</p>
                        <p class="text-sm text-gray-500">Annual tax summary report</p>
                    </div>
                    <x-settings.toggle-switch 
                        model="formData.tax_report"
                        class="" />
                </div>
                
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Aging Report</p>
                        <p class="text-sm text-gray-500">Weekly accounts receivable aging</p>
                    </div>
                    <x-settings.toggle-switch 
                        model="formData.aging_report"
                        class="" />
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Report Recipients</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                    <input type="email" 
                           x-model="formData.report_email_1"
                           placeholder="finance@company.com"
                           class="flex-1 mr-3 border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <button type="button" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    + Add Email Recipient
                </button>
            </div>
        </div>

        <div>
            <h3 class="text-md font-medium text-gray-900 mb-3">Export Formats</h3>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.export_pdf"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">PDF Format</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.export_excel"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Excel Format</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.export_csv"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">CSV Format</span>
                </label>
            </div>
        </div>
    </div>
</x-settings.form-section>
