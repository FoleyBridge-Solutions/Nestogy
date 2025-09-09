<div class="p-6">
    <div class="space-y-6">
        <!-- Payment Gateways -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Gateways</h3>
            <div class="space-y-4">
                <!-- Stripe -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img src="/images/stripe-logo.png" alt="Stripe" class="h-8 w-8 mr-3" onerror="this.style.display='none'">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Stripe</h4>
                                <p class="text-sm text-gray-500">Credit cards, ACH, and more</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            <button class="text-blue-600-600 hover:text-blue-600-900 text-sm font-medium">Configure</button>
                        </div>
                    </div>
                </div>

                <!-- PayPal -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img src="/images/paypal-logo.png" alt="PayPal" class="h-8 w-8 mr-3" onerror="this.style.display='none'">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">PayPal</h4>
                                <p class="text-sm text-gray-500">PayPal and credit card processing</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                            <button class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">Configure</button>
                        </div>
                    </div>
                </div>

                <!-- Square -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img src="/images/square-logo.png" alt="Square" class="h-8 w-8 mr-3" onerror="this.style.display='none'">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Square</h4>
                                <p class="text-sm text-gray-500">In-person and online payments</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                            <button class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">Configure</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Processing Settings -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Processing</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="auto_capture" name="auto_capture" value="1" checked class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_capture" class="ml-3 block text-sm font-medium text-gray-700">Automatically capture authorized payments</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="save_payment_methods" name="save_payment_methods" value="1" checked class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="save_payment_methods" class="ml-3 block text-sm font-medium text-gray-700">Allow clients to save payment methods</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="require_cvv" name="require_cvv" value="1" checked class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="require_cvv" class="ml-3 block text-sm font-medium text-gray-700">Require CVV for all transactions</label>
                </div>
            </div>
        </div>

        <!-- Transaction Fees -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Fees</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="pass_fees_to_client" name="pass_fees_to_client" value="1" class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="pass_fees_to_client" class="ml-3 block text-sm font-medium text-gray-700">Pass processing fees to clients</label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="credit_card_fee" class="block text-sm font-medium text-gray-700 mb-1">Credit Card Fee (%)</label>
                        <input type="number" id="credit_card_fee" name="credit_card_fee" value="2.9" step="0.1" min="0" max="10"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="ach_fee" class="block text-sm font-medium text-gray-700 mb-1">ACH Fee ($)</label>
                        <input type="number" id="ach_fee" name="ach_fee" value="0.50" step="0.01" min="0" max="50"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Payment Settings
        </button>
    </div>
</div>
