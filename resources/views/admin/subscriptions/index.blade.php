<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Subscription Management') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.subscriptions.analytics') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Analytics
                </a>
                <a href="{{ route('admin.subscriptions.export', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Export
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500">Total Customers</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500">Active</p>
                                <p class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</p>
                            </div>
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500">Trialing</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ $stats['trialing'] }}</p>
                            </div>
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500">Past Due</p>
                                <p class="text-2xl font-bold text-red-600">{{ $stats['past_due'] }}</p>
                            </div>
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500">Canceled</p>
                                <p class="text-2xl font-bold text-gray-600">{{ $stats['canceled'] }}</p>
                            </div>
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                   placeholder="Company name or email..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Statuses</option>
                                <option value="trialing" {{ request('status') === 'trialing' ? 'selected' : '' }}>Trialing</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="past_due" {{ request('status') === 'past_due' ? 'selected' : '' }}>Past Due</option>
                                <option value="canceled" {{ request('status') === 'canceled' ? 'selected' : '' }}>Canceled</option>
                                <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            </select>
                        </div>

                        <div>
                            <label for="plan" class="block text-sm font-medium text-gray-700">Plan</label>
                            <select name="plan" id="plan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Plans</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ request('plan') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscriptions Table -->
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Company
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Plan
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Trial/Billing
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Users
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($subscriptions as $subscription)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $subscription->company_name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ $subscription->email }}
                                                    </div>
                                                    @if($subscription->linkedCompany && $subscription->linkedCompany->suspended_at)
                                                        <div class="text-xs text-red-600 font-medium">
                                                            SUSPENDED
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ $subscription->subscriptionPlan->name ?? 'No Plan' }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $subscription->subscriptionPlan ? '$' . number_format($subscription->subscriptionPlan->price_monthly, 2) . '/mo' : 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'trialing' => 'bg-yellow-100 text-yellow-800',
                                                    'active' => 'bg-green-100 text-green-800',
                                                    'past_due' => 'bg-red-100 text-red-800',
                                                    'canceled' => 'bg-gray-100 text-gray-800',
                                                    'unpaid' => 'bg-red-100 text-red-800',
                                                ];
                                            @endphp
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$subscription->subscription_status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($subscription->subscription_status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($subscription->trial_ends_at && $subscription->subscription_status === 'trialing')
                                                <div>Trial ends: {{ $subscription->trial_ends_at->format('M j, Y') }}</div>
                                            @elseif($subscription->next_billing_date)
                                                <div>Next bill: {{ $subscription->next_billing_date->format('M j, Y') }}</div>
                                            @else
                                                <div class="text-gray-500">N/A</div>
                                            @endif
                                            <div class="text-xs text-gray-500">
                                                Created: {{ $subscription->created_at->format('M j, Y') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $subscription->current_user_count ?? 0 }}
                                            @if($subscription->subscriptionPlan && $subscription->subscriptionPlan->max_users)
                                                / {{ $subscription->subscriptionPlan->max_users }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('admin.subscriptions.show', $subscription) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    View
                                                </a>
                                                @if(!$subscription->company_link_id)
                                                    <form method="POST" action="{{ route('admin.subscriptions.create-company()', $subscription) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 hover:text-green-900"
                                                                onclick="return confirm('Create company() company for this client?')">
                                                            Create Tenant
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No subscriptions found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $subscriptions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>