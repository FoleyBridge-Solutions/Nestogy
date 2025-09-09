@props(['formData' => null])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Client ID
        </label>
        <input type="text" 
               x-model="formData.paypal_client_id"
               placeholder="AYSq3RDGsmBLJE-otTkBtM..."
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Client Secret
        </label>
        <input type="password" 
               x-model="formData.paypal_client_secret"
               placeholder="EGnHDxD_qRPdaLdZz8iCr8..."
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Webhook ID
        </label>
        <input type="text" 
               x-model="formData.paypal_webhook_id"
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Environment
        </label>
        <select x-model="formData.paypal_environment"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="sandbox">Sandbox</option>
            <option value="live">Live/Production</option>
        </select>
    </div>
</div>
<div class="mt-4 flex items-center justify-between">
    <label class="flex items-center">
        <input type="checkbox" 
               x-model="formData.paypal_enabled"
               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <span class="ml-2 text-sm text-gray-700">Enable PayPal Payments</span>
    </label>
    <button type="button" 
            @click="testGateway('paypal')"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
        Test Connection
    </button>
</div>