@extends('layouts.auth-standalone')

@section('title', 'Verify Email')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-6 py-8">
        <h1 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6">
            Verify Your Email Address
        </h1>

        <!-- Success Messages -->
        @if (session('resent'))
            <div class="mb-6 px-6 py-6 rounded-lg bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span>A fresh verification link has been sent to your email address.</span>
                </div>
            </div>
        @endif

        @if (session('verified'))
            <div class="mb-6 px-6 py-6 rounded-lg bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span>Your email has been verified successfully!</span>
                </div>
            </div>
        @endif

        <!-- Email Icon and Description -->
        <div class="text-center mb-6">
            <div class="mb-6">
                <i class="fas fa-envelope text-5xl text-blue-600 dark:text-blue-400 opacity-80"></i>
            </div>
            <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                Before proceeding, please check your email for a verification link.
                If you didn't receive the email, we can send you another one.
            </p>
        </div>

        <!-- Instructions -->
        <div class="mb-6 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-6 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                What to do next:
            </h3>
            <ol class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-decimal list-inside">
                <li>Check your email inbox for a message from Nestogy ERP</li>
                <li>Click the verification link in the email</li>
                <li>Return to this page to continue</li>
            </ol>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
            <!-- Resend Verification Email -->
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-6 bg-blue-600 dark:bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Resend Verification Email
                </button>
            </form>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-6 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </button>
            </form>
        </div>

        <!-- Help Section -->
        <div class="text-center mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">Still having trouble?</p>
            <div class="flex flex-flex-1 px-6 sm:flex-flex flex-wrap -mx-4 items-center justify-center space-y-2 sm:space-y-0 sm:space-x-6">
                <button type="button" 
                        onclick="document.getElementById('helpModal').classList.remove('hidden')"
                        aria-describedby="helpModal"
                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                    <i class="fas fa-question-circle mr-1"></i>Get Help
                </button>
                <a href="mailto:support@nestogy.com" 
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                    <i class="fas fa-envelope mr-1"></i>Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div id="helpModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-labelledby="helpModalTitle" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-6 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('helpModal').classList.add('hidden')" aria-hidden="true"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="helpModalTitle" class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-question-circle mr-2 text-blue-600 dark:text-blue-400"></i>
                        Email Verification Help
                    </h3>
                    <button type="button" 
                            onclick="document.getElementById('helpModal').classList.add('hidden')"
                            aria-label="Close modal"
                            class="text-gray-400 dark:text-gray-300 hover:text-gray-600 dark:hover:text-gray-100 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Can't find the verification email?</h4>
                        <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                            <li>Check your spam or junk folder</li>
                            <li>Make sure the email address is correct</li>
                            <li>Wait a few minutes for the email to arrive</li>
                            <li>Try clicking "Resend Verification Email"</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Email link not working?</h4>
                        <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                            <li>Make sure you're using the latest email</li>
                            <li>Copy and paste the link directly into your browser</li>
                            <li>Clear your browser cache and try again</li>
                        </ul>
                    </div>

                    <div class="px-6 py-6 rounded-lg bg-cyan-100 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-cyan-600 dark:text-cyan-400 mr-2 mt-0.5"></i>
                            <div class="text-sm text-cyan-800 dark:text-cyan-200">
                                <strong>Note:</strong> Verification links expire after 60 minutes for security reasons.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-6 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                <a href="mailto:support@nestogy.com" 
                   class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2 bg-blue-600 dark:bg-blue-500 text-base font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    <i class="fas fa-envelope mr-2"></i>
                    Contact Support
                </a>
                <button type="button" 
                        onclick="document.getElementById('helpModal').classList.add('hidden')"
                        aria-label="Close help modal"
                        class="mt-6 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-6 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
