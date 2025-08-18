@extends('layouts.app')

@section('title', 'Approve Login Attempt')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 text-green-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Approve Login Attempt
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                We detected a login attempt from an unusual location
            </p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Security Verification Required
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Please verify this login attempt was made by you.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Login Attempt Details</h4>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Location</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getLocationString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Device</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getDeviceString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->ip_address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Time</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Risk Level</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $attempt->getRiskLevelColor() }}-100 text-{{ $attempt->getRiskLevelColor() }}-800">
                                    {{ $attempt->getRiskLevelString() }}
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Detection Reasons</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getDetectionReasonsString() }}</dd>
                        </div>
                    </dl>
                </div>

                <form method="POST" action="{{ route('security.suspicious-login.approve', $attempt->verification_token) }}">
                    @csrf
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input id="trust_location" name="trust_location" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="trust_location" class="ml-2 block text-sm text-gray-900">
                                Trust this location for future logins
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                ✅ Approve Login
                            </button>
                            
                            <a href="{{ route('security.suspicious-login.deny', $attempt->verification_token) }}" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                🚫 Deny
                            </a>
                        </div>
                    </div>
                </form>

                <div class="text-xs text-gray-500 text-center">
                    This verification expires at {{ $attempt->expires_at->format('M j, Y g:i A T') }}
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                Having trouble? Contact 
                <a href="mailto:support@nestogy.com" class="font-medium text-indigo-600 hover:text-indigo-500">support</a>
            </p>
        </div>
    </div>
</div>
@endsection