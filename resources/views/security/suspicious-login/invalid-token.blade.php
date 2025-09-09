@extends('layouts.app')

@section('title', 'Invalid Verification Link')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-6 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                {{ $title }}
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ $message }}
            </p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div class="border-l-4 border-gray-400 bg-gray-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-gray-800">
                                Verification Link Expired
                            </h3>
                            <div class="mt-2 text-sm text-gray-700">
                                <p>This verification link has expired or has already been used. Verification links are only valid for a limited time for security purposes.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-6">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">ℹ️ What to do next</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Try logging in again to generate a new verification</li>
                        <li>• Check your email for more recent security notifications</li>
                        <li>• Contact support if you're experiencing issues</li>
                        <li>• Change your password if you suspect unauthorized access</li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <a href="{{ route('login') }}" class="w-full flex justify-center py-6 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        🔐 Try Login Again
                    </a>
                    
                    <a href="{{ route('password.request') }}" class="w-full flex justify-center py-2 px-6 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        🔑 Reset Password
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                Need help? Contact 
                <a href="mailto:support@nestogy.com" class="font-medium text-indigo-600 hover:text-indigo-500">support</a>
                or our 
                <a href="mailto:security@nestogy.com" class="font-medium text-red-600 hover:text-red-500">security team</a>
            </p>
        </div>
    </div>
</div>
@endsection
