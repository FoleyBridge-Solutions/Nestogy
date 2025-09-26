@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Invitation Expired
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    This invitation link has expired or is no longer valid.
                </p>
            </div>
            
            <div class="mt-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                What to do next?
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <li>Contact your account manager for a new invitation</li>
                                    <li>Check your email for a more recent invitation</li>
                                    <li>Ensure you're using the latest invitation link sent to you</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="{{ route('client.login') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Go to Login Page
                </a>
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Need assistance? Contact support at
                    <a href="mailto:support@{{ request()->getHost() }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        support@{{ request()->getHost() }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection