@extends('layouts.setup')

@section('title', 'Setup Wizard - Initial Setup')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
    <div class="max-w-3xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="bg-blue-600 dark:bg-blue-500 rounded-full p-6 shadow-lg">
                    <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                Welcome to Nestogy ERP
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                Let's set up your MSP business management platform
            </p>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
            <div class="px-6 py-8 sm:p-10">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        🚀 Let's Set Up Your ERP System
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Welcome! It looks like this is your first time using Nestogy. 
                        Let's get you set up with your company information and create your administrator account.
                    </p>
                </div>

                <div class="space-y-6">
                    <!-- Features Overview -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4">What you'll get:</h4>
                        <div class="grid grid-cols-1 gap-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">Complete client management system</p>
                            </div>
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">Advanced ticketing and project management</p>
                            </div>
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">Invoicing, quoting, and financial management</p>
                            </div>
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">Asset tracking and RMM integrations</p>
                            </div>
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="ml-3 text-sm text-gray-700 dark:text-gray-300">Comprehensive reporting and analytics</p>
                            </div>
                        </div>
                    </div>

                    <!-- Setup Time Estimate -->
                    <div class="text-center">
                        <div class="inline-flex items-center px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-600 rounded-md">
                            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm text-blue-700 dark:text-blue-300 font-medium">Setup takes about 2-3 minutes</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div>
                        <a href="{{ route('setup.wizard.company-form') }}" 
                           class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition duration-150 ease-in-out">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </span>
                            Start Setup Process
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-600 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Nestogy ERP - Built for MSPs, by MSPs
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
