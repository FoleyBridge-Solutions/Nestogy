<div class="p-6">
    <div class="space-y-6">
        <!-- API Configuration -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">API Configuration</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_api" name="enable_api" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="enable_api" class="ml-3 block text-sm font-medium text-gray-700">Enable REST API</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="require_api_auth" name="require_api_auth" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="require_api_auth" class="ml-3 block text-sm font-medium text-gray-700">Require API authentication</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="api_rate_limiting" name="api_rate_limiting" value="1" checked class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="api_rate_limiting" class="ml-3 block text-sm font-medium text-gray-700">Enable rate limiting</label>
                </div>
            </div>
        </div>

        <!-- API Keys -->
        <div class="border-t border-gray-200 pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">API Keys</h3>
                <button type="button" class="px-4 py-2 bg-blue-600-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-600-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Generate New Key
                </button>
            </div>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Production API Key</h4>
                                <p class="text-sm text-gray-500">pk_live_••••••••••••••••••••••••1234</p>
                                <p class="text-sm text-gray-500">Created: Jan 15, 2024 • Last used: 2 hours ago</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <button class="text-primary-600 hover:text-primary-900 text-sm font-medium">Edit</button>
                                <button class="text-red-600 hover:text-red-900 text-sm font-medium">Revoke</button>
                            </div>
                        </div>
                    </li>
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Development API Key</h4>
                                <p class="text-sm text-gray-500">pk_test_••••••••••••••••••••••••5678</p>
                                <p class="text-sm text-gray-500">Created: Dec 1, 2023 • Last used: Never</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                <button class="text-primary-600 hover:text-primary-900 text-sm font-medium">Edit</button>
                                <button class="text-red-600 hover:text-red-900 text-sm font-medium">Revoke</button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Webhooks -->
        <div class="border-t border-gray-200 pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Webhooks</h3>
                <button type="button" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Add Webhook
                </button>
            </div>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Ticket Updates</h4>
                                <p class="text-sm text-gray-500">https://external-system.com/webhooks/tickets</p>
                                <p class="text-sm text-gray-500">Events: ticket.created, ticket.updated, ticket.closed</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <button class="text-primary-600 hover:text-primary-900 text-sm font-medium">Test</button>
                                <button class="text-primary-600 hover:text-primary-900 text-sm font-medium">Edit</button>
                            </div>
                        </div>
                    </li>
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Payment Events</h4>
                                <p class="text-sm text-gray-500">https://accounting-system.com/webhooks/payments</p>
                                <p class="text-sm text-gray-500">Events: payment.successful, payment.failed</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>
                                <button class="text-primary-600 hover:text-primary-900 text-sm font-medium">Test</button>
                                <button class="text-primary-600 hover:text-primary-900 text-sm font-medium">Edit</button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Rate Limiting -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Rate Limiting</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="requests_per_minute" class="block text-sm font-medium text-gray-700 mb-1">Requests per Minute</label>
                    <select id="requests_per_minute" name="requests_per_minute" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="60" selected>60</option>
                        <option value="120">120</option>
                        <option value="300">300</option>
                        <option value="600">600</option>
                        <option value="unlimited">Unlimited</option>
                    </select>
                </div>
                <div>
                    <label for="burst_limit" class="block text-sm font-medium text-gray-700 mb-1">Burst Limit</label>
                    <select id="burst_limit" name="burst_limit" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="button" class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700">
            View API Documentation
        </button>
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save API Settings
        </button>
    </div>
</div>