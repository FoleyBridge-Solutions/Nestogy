<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Billing & Subscription') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Current Subscription Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Current Subscription</h3>
                        @if($client->subscription_status === 'trialing')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                Free Trial
                            </span>
                        @elseif($client->subscription_status === 'active')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        @elseif($client->subscription_status === 'past_due')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                Past Due
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                {{ ucfirst($client->subscription_status) }}
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Plan</h4>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $client->subscriptionPlan->name ?? 'No Plan' }}
                            </p>
                            @if($client->subscriptionPlan)
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    ${{ number_format($client->subscriptionPlan->price_monthly, 2) }}/month
                                </p>
                            @endif
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-500">
                                @if($client->subscription_status === 'trialing')
                                    Trial Ends
                                @else
                                    Next Billing Date
                                @endif
                            </h4>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($client->subscription_status === 'trialing' && $client->trial_ends_at)
                                    {{ $client->trial_ends_at->format('M j, Y') }}
                                @elseif($client->next_billing_date)
                                    {{ $client->next_billing_date->format('M j, Y') }}
                                @else
                                    N/A
                                @endif
                            </p>
                            @if($client->subscription_status === 'trialing' && $client->trial_ends_at)
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $client->trial_ends_at->diffForHumans() }}
                                </p>
                            @endif
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Users</h4>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $client->current_user_count ?? 0 }}
                                @if($client->subscriptionPlan && $client->subscriptionPlan->max_users)
                                    / {{ $client->subscriptionPlan->max_users }}
                                @else
                                    / Unlimited
                                @endif
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Active users</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($client->subscriptionPlan && $availablePlans->count() > 0)
                            <a href="{{ route('billing.change-plan') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Change Plan
                            </a>
                        @endif
                        
                        <a href="{{ route('billing.payment-methods') }}" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium">
                            Manage Payment Methods
                        </a>
                        
                        <a href="{{ route('billing.invoices') }}" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium">
                            View Invoices
                        </a>

                        @if($client->stripe_customer_id)
                            <a href="{{ route('billing.portal') }}" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium">
                                Billing Portal
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Trial Warning -->
            @if($client->subscription_status === 'trialing' && $client->trial_ends_at)
                @php
                    $daysLeft = $client->trial_ends_at->diffInDays(now());
                @endphp
                @if($daysLeft <= 3)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Your trial ends in {{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }}
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>
                                        To continue using Nestogy after your trial, please ensure you have a valid payment method on file.
                                        @if(!$client->paymentMethods->count())
                                            <a href="{{ route('billing.payment-methods') }}" class="font-medium underline">
                                                Add payment method →
                                            </a>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Past Due Warning -->
            @if($client->subscription_status === 'past_due')
                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Payment Required
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>
                                    Your account is past due. Please update your payment method or contact support to avoid service interruption.
                                </p>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('billing.payment-methods') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Update Payment Method
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Payment Methods Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Methods</h3>
                        <a href="{{ route('billing.payment-methods') }}" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                            Manage →
                        </a>
                    </div>

                    @if($client->paymentMethods->count() > 0)
                        <div class="space-y-3">
                            @foreach($client->paymentMethods->take(2) as $paymentMethod)
                                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-md">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            @if($paymentMethod->isCard())
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $paymentMethod->getDisplayName() }}
                                            </p>
                                            @if($paymentMethod->is_default)
                                                <p class="text-xs text-green-600">Default</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        @if($paymentMethod->isCard() && $paymentMethod->card_exp_month && $paymentMethod->card_exp_year)
                                            {{ sprintf('%02d/%s', $paymentMethod->card_exp_month, substr($paymentMethod->card_exp_year, -2)) }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No payment methods on file</p>
                            <a href="{{ route('billing.payment-methods') }}" class="mt-3 inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Add Payment Method
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Usage Stats -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Usage Stats</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">View detailed usage metrics</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('billing.usage') }}" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                View Details →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Subscription History -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Subscription Details</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">View subscription history</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('billing.subscription') }}" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                View Details →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Support -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Need Help?</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Contact our support team</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="mailto:support@nestogy.com" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                Contact Support →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>