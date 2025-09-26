@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Set Your Password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Welcome {{ $contact->name }}! Please create a secure password to access your client portal.
            </p>
            <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            This invitation expires {{ $expiresAt->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <form class="mt-8 space-y-6" action="{{ route('client.invitation.accept', $token) }}" method="POST">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-700">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ $contact->email }}" readonly
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm @error('password') border-red-300 @enderror"
                           placeholder="Enter your password">
                    <p class="mt-1 text-xs text-gray-600">Minimum 8 characters with uppercase, lowercase, and numbers</p>
                </div>
                
                <div class="mb-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm @error('password_confirmation') border-red-300 @enderror"
                           placeholder="Confirm your password">
                </div>
            </div>
            
            <!-- Password strength indicator -->
            <div id="password-strength" class="hidden">
                <p class="text-sm font-medium text-gray-700 mb-1">Password Strength</p>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="strength-bar" class="h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="strength-text" class="text-xs mt-1"></p>
            </div>
            
            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    Set Password & Access Portal
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Need help? Contact support at 
                <a href="mailto:support@{{ request()->getHost() }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                    support@{{ request()->getHost() }}
                </a>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const strengthDiv = document.getElementById('password-strength');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
            strengthDiv.classList.add('hidden');
            return;
        }
        
        strengthDiv.classList.remove('hidden');
        
        let strength = 0;
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            numbers: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password),
        };
        
        strength = Object.values(checks).filter(v => v).length;
        
        const percentage = (strength / 5) * 100;
        strengthBar.style.width = percentage + '%';
        
        if (strength <= 2) {
            strengthBar.className = 'h-2.5 rounded-full transition-all duration-300 bg-red-500';
            strengthText.textContent = 'Weak password';
            strengthText.className = 'text-xs mt-1 text-red-600';
        } else if (strength === 3) {
            strengthBar.className = 'h-2.5 rounded-full transition-all duration-300 bg-yellow-500';
            strengthText.textContent = 'Fair password';
            strengthText.className = 'text-xs mt-1 text-yellow-600';
        } else if (strength === 4) {
            strengthBar.className = 'h-2.5 rounded-full transition-all duration-300 bg-blue-500';
            strengthText.textContent = 'Good password';
            strengthText.className = 'text-xs mt-1 text-blue-600';
        } else {
            strengthBar.className = 'h-2.5 rounded-full transition-all duration-300 bg-green-500';
            strengthText.textContent = 'Strong password';
            strengthText.className = 'text-xs mt-1 text-green-600';
        }
    });
});
</script>
@endsection