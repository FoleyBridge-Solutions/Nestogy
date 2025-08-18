@extends('layouts.guest')

@section('title', 'Login - Nestogy ERP')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4 min-h-screen">
        <!-- Left side - Branding -->
        <div class="w-full lg:w-1/2 hidden lg:flex items-center justify-center bg-blue-600">
            <div class="text-center text-white">
                <div class="mb-4">
                    <img src="{{ asset('static-assets/img/branding/nestogy-logo-white.png') }}" alt="Nestogy" class="max-w-xs w-full h-auto" style="max-width: 200px;">
                </div>
                <h2 class="font-bold mb-3 text-2xl">Welcome to Nestogy ERP</h2>
                <p class="text-lg mb-4">Streamline your business operations with our comprehensive ERP solution</p>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <i class="fas fa-users text-2xl mb-2 block"></i>
                        <p class="text-sm">Client Management</p>
                    </div>
                    <div>
                        <i class="fas fa-ticket-alt text-2xl mb-2 block"></i>
                        <p class="text-sm">Ticket System</p>
                    </div>
                    <div>
                        <i class="fas fa-chart-line text-2xl mb-2 block"></i>
                        <p class="text-sm">Financial Reports</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="bg-white rounded-lg shadow-md overflow-hidden shadow-lg border-0">
                    <div class="p-6 p-5">
                        <!-- Mobile Logo -->
                        <div class="text-center mb-4 block lg:hidden">
                            <img src="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" alt="Nestogy" class="max-w-xs w-full h-auto" style="max-width: 150px;">
                        </div>

                        <h3 class="text-center mb-4 font-bold text-xl">Sign In</h3>

                        @if ($errors->any())
                            <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Company Selection -->
                            <div class="mb-4">
                                <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-building mr-1"></i>Company
                                </label>
                                <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('company_id') border-red-500 @enderror" 
                                        id="company_id" name="company_id" required>
                                    <option value="">Select Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-envelope mr-1"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-500 @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       autocomplete="email" 
                                       autofocus
                                       placeholder="Enter your email">
                                @error('email')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-lock mr-1"></i>Password
                                </label>
                                <div class="flex">
                                    <input type="password" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-500 @enderror" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           autocomplete="current-password"
                                           placeholder="Enter your password">
                                    <button class="px-3 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Remember Me -->
                            @if(!isset($settings) || $settings->remember_me_enabled)
                            <div class="mb-4 flex items-center">
                                <input type="hidden" name="remember" value="0">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                <label class="ml-2 block text-sm text-gray-700" for="remember">
                                    Remember me for 30 days
                                </label>
                            </div>
                            @endif

                            <!-- Submit Button -->
                            <div class="mb-4">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-lg py-3">
                                    <i class="fas fa-sign-in-alt mr-1"></i>Sign In
                                </button>
                            </div>

                            <!-- Forgot Password Link -->
                            <div class="text-center">
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-key mr-1"></i>Forgot your password?
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <p class="text-gray-600 text-sm">
                        &copy; {{ date('Y') }} Nestogy ERP. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Auto-focus first empty field
    const companySelect = document.getElementById('company_id');
    const emailInput = document.getElementById('email');
    
    if (companySelect && !companySelect.value) {
        companySelect.focus();
    } else if (emailInput && !emailInput.value) {
        emailInput.focus();
    }
});
</script>
@endpush

@push('styles')
<style>
/* Custom gradient background */
.bg-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Enhanced input styling */
input[type="email"]:focus,
input[type="password"]:focus,
select:focus {
    --tw-ring-color: rgb(59 130 246 / 0.3);
    border-color: #667eea;
}

/* Enhanced button styling */
button[type="submit"] {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: all 0.3s ease;
}

button[type="submit"]:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Checkbox styling */
input[type="checkbox"]:checked {
    background-color: #667eea;
    border-color: #667eea;
}

/* Password toggle button hover effect */
#togglePassword:hover {
    background-color: #f3f4f6;
}

@media (max-width: 991.98px) {
    .lg\\:w-1\\/2 {
        padding: 2rem;
    }
}
</style>
@endpush
@endsection