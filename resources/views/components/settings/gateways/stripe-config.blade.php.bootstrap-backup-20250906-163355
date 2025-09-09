@props(['formData' => null])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Publishable Key
        </label>
        <input type="text" 
               x-model="formData.stripe_publishable_key"
               placeholder="pk_test_..."
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Secret Key
        </label>
        <input type="password" 
               x-model="formData.stripe_secret_key"
               placeholder="sk_test_..."
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Webhook Secret
        </label>
        <input type="password" 
               x-model="formData.stripe_webhook_secret"
               placeholder="whsec_..."
               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Environment
        </label>
        <select x-model="formData.stripe_environment"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="test">Test/Sandbox</option>
            <option value="live">Live/Production</option>
        </select>
    </div>
</div>
<div class="mt-4 flex items-center justify-between">
    <label class="flex items-center">
        <input type="checkbox" 
               x-model="formData.stripe_enabled"
               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
        <span class="ml-2 text-sm text-gray-700">Enable Stripe Payments</span>
    </label>
    <button type="button" 
            @click="testGateway('stripe')"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
        Test Connection
    </button>
</div>