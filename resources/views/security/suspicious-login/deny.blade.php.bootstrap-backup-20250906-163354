@extends('layouts.app')

@section('title', 'Deny Login Attempt')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 text-red-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Block Login Attempt
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Confirm that this login attempt should be blocked
            </p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div class="border-l-4 border-red-400 bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Suspicious Activity Detected
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>If you didn't attempt to log in, this could be an unauthorized access attempt.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Suspicious Login Details</h4>
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
                            <dt class="text-sm font-medium text-gray-500">Why was this flagged?</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getDetectionReasonsString() }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-yellow-800 mb-2">⚠️ Important</h4>
                    <p class="text-sm text-yellow-700">
                        By denying this login attempt, you confirm that this was not authorized by you. 
                        We will block this IP address and recommend additional security measures.
                    </p>
                </div>

                <form method="POST" action="{{ route('security.suspicious-login.deny', $attempt->verification_token) }}">
                    @csrf
                    
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('security.suspicious-login.approve', $attempt->verification_token) }}" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            ← Back to Approve
                        </a>
                        
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            🚫 Block Login
                        </button>
                    </div>
                </form>

                <div class="text-xs text-gray-500 text-center">
                    This verification expires at {{ $attempt->expires_at->format('M j, Y g:i A T') }}
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                Need immediate help? Contact 
                <a href="mailto:security@nestogy.com" class="font-medium text-red-600 hover:text-red-500">security team</a>
            </p>
        </div>
    </div>
</div>
@endsection