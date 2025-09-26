@extends('layouts.app')

@section('title', 'Login Blocked')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-6 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 text-red-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Login Attempt Blocked
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                The suspicious login attempt has been successfully blocked
            </p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div class="border-l-4 border-red-400 bg-red-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Access Denied
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>The suspicious login attempt has been blocked and reported.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900 mb-6">Blocked Attempt Details</h4>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Location</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->getLocationString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->ip_address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Blocked At</dt>
                            <dd class="text-sm text-gray-900">{{ $attempt->denied_at->format('M j, Y g:i A T') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ⛔ Blocked
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-6">
                    <h4 class="text-sm font-medium text-yellow-800 mb-2">🔐 What We've Done</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• Blocked the suspicious IP address</li>
                        <li>• Logged the security incident</li>
                        <li>• Notified our security team</li>
                        <li>• Updated threat intelligence</li>
                    </ul>
                </div>

                <div class="bg-red-50 border border-red-200 rounded-md p-6">
                    <h4 class="text-sm font-medium text-red-800 mb-6">🛡️ Immediate Security Steps</h4>
                    <div class="space-y-2">
                        @foreach($securityRecommendations as $index => $recommendation)
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-red-100 text-red-800 text-xs font-medium mr-2 mt-0.5">{{ $index + 1 }}</span>
                            <span class="text-sm text-red-700">{{ $recommendation }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('password.request') }}" class="w-full flex justify-center py-6 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        🔑 Change Password Now
                    </a>
                    
                    <a href="{{ route('settings.domain.index', 'security') }}" class="w-full flex justify-center py-2 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        ⚙️ Security Settings
                    </a>
                    
                    <a href="{{ route('login') }}" class="w-full flex justify-center py-2 px-6 border border-indigo-300 rounded-md shadow-sm text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        ← Back to Login
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center bg-white border border-gray-200 rounded-lg p-6">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Need Immediate Help?</h4>
            <p class="text-sm text-gray-600 mb-6">
                If you're locked out or need assistance securing your account:
            </p>
            <div class="space-y-2">
                <a href="mailto:security@nestogy.com" class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-500">
                    📧 security@nestogy.com
                </a>
                <br>
                <a href="tel:+1-555-SECURITY" class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-500">
                    📞 Emergency Security Hotline
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
