@extends('layouts.app')

@section('title', 'Login Approved')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-6 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 text-green-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Login Approved Successfully
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Your login attempt has been verified and approved
            </p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div class="border-l-4 border-green-400 bg-green-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                Access Granted
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>You can now complete your login to Nestogy.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900 mb-6">Verification Details</h4>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Approved Location</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getLocationString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Device</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getDeviceString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Approved At</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->approved_at->format('M j, Y g:i A T') }}</dd>
                        </div>
                        @if($trustLocation)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Trust Setting</dt>
                            <dd class="text-sm text-green-700">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Location marked as trusted
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>

                @if($trustLocation)
                <div class="bg-blue-50 border border-blue-200 rounded-md p-6">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">📍 Trusted Location</h4>
                    <p class="text-sm text-blue-700">
                        This location has been added to your trusted devices. Future logins from this location and device will not require additional verification.
                    </p>
                </div>
                @endif

                <div class="space-y-3">
                    <a href="{{ route('dashboard') }}" class="w-full flex justify-center py-6 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Continue to Dashboard
                    </a>
                    
                    <a href="{{ route('settings.security') }}" class="w-full flex justify-center py-2 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Review Security Settings
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white border border-yellow-200 rounded-lg p-6">
            <h4 class="text-sm font-medium text-yellow-800 mb-2">🔒 Security Recommendations</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
                <li>• Enable two-factor authentication for enhanced security</li>
                <li>• Use strong, unique passwords for your accounts</li>
                <li>• Regularly review your account activity</li>
                <li>• Keep your devices and browsers updated</li>
            </ul>
        </div>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                Questions about account security? Contact 
                <a href="mailto:security@nestogy.com" class="font-medium text-indigo-600 hover:text-indigo-500">our security team</a>
            </p>
        </div>
    </div>
</div>
@endsection
