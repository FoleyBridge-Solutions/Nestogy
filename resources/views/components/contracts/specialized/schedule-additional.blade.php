<div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-6">
    <h4 class="text-lg font-medium text-purple-900 dark:text-purple-100 mb-6">Additional Terms & Conditions</h4>
    
    <!-- Custom Clauses -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">Custom Contract Clauses</label>
        <textarea rows="4" placeholder="Enter any custom terms, limitations, or special conditions..."
                  class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
    </div>

    <!-- Compliance Requirements -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">Compliance Requirements</label>
        <div class="space-y-2">
            <label class="flex items-center space-x-2">
                <input type="checkbox" value="sox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">SOX Compliance</span>
            </label>
            <label class="flex items-center space-x-2">
                <input type="checkbox" value="hipaa" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">HIPAA Compliance</span>
            </label>
            <label class="flex items-center space-x-2">
                <input type="checkbox" value="pci_dss" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">PCI DSS Compliance</span>
            </label>
            <label class="flex items-center space-x-2">
                <input type="checkbox" value="gdpr" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">GDPR Compliance</span>
            </label>
        </div>
    </div>

    <!-- Termination Terms -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">Termination Notice Period</label>
        <select class="w-full max-w-xs px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
            <option value="30">30 days</option>
            <option value="60">60 days</option>
            <option value="90">90 days</option>
            <option value="120">120 days</option>
        </select>
    </div>
</div>
