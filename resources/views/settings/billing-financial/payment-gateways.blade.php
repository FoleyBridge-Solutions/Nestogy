<x-settings.form-section title="Payment Gateway Configuration">
    <x-slot name="icon">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
    </x-slot>

    <div class="flex justify-end mb-4">
        <button type="button" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            + Add Gateway
        </button>
    </div>
    
    <div class="space-y-3">
        <!-- Stripe Gateway -->
        <x-settings.accordion-item 
            id="stripe"
            title="Stripe"
            :subtitle="($settings['stripe_enabled'] ?? false) ? 'Active' : 'Inactive'"
            icon-color="purple">
            <x-slot name="icon">
                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
                </svg>
            </x-slot>
            
            <x-settings.gateways.stripe-config :form-data="null" />
        </x-settings.accordion-item>

        <!-- PayPal Gateway -->
        <x-settings.accordion-item 
            id="paypal"
            title="PayPal"
            :subtitle="($settings['paypal_enabled'] ?? false) ? 'Active' : 'Inactive'"
            icon-color="blue">
            <x-slot name="icon">
                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .76-.655h7.535c2.49 0 4.264.816 5.273 2.424.992 1.58 1.16 3.542.412 5.67-.36 1.03-.893 1.955-1.583 2.75-.687.8-1.503 1.437-2.424 1.893-.895.45-1.905.682-3 .682H9.89L7.076 21.337zm1.27-14.97h1.723c1.24 0 2.16.276 2.735.826.566.543.75 1.388.466 2.587-.285 1.195-.853 2.022-1.695 2.46-.836.43-1.853.645-3.017.645H7.756l1.103-6.518h-.513z"/>
                </svg>
            </x-slot>
            
            <x-settings.gateways.paypal-config :form-data="null" />
        </x-settings.accordion-item>

        <!-- Square Gateway -->
        <x-settings.accordion-item 
            id="square"
            title="Square"
            :subtitle="($settings['square_enabled'] ?? false) ? 'Active' : 'Inactive'"
            icon-color="gray">
            <x-slot name="icon">
                <svg class="w-6 h-6 text-gray-700" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-4 14H8V8h8v8z"/>
                </svg>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Application ID
                    </label>
                    <input type="text" 
                           x-model="formData.square_application_id"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Access Token
                    </label>
                    <input type="password" 
                           x-model="formData.square_access_token"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Location ID
                    </label>
                    <input type="text" 
                           x-model="formData.square_location_id"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Environment
                    </label>
                    <select x-model="formData.square_environment"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="sandbox">Sandbox</option>
                        <option value="production">Production</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" 
                           x-model="formData.square_enabled"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Enable Square Payments</span>
                </label>
                <button type="button" 
                        @click="testGateway('square')"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Test Connection
                </button>
            </div>
        </x-settings.accordion-item>
    </div>
</x-settings.form-section>