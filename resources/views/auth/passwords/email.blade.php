@extends('layouts.guest')

@section('title', 'Reset Password - Nestogy ERP')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4 min-vh-100">
        <!-- Left side - Branding -->
        <div class="col-lg-6 hidden d-lg-flex items-center justify-center bg-blue-600">
            <div class="text-center text-white">
                <div class="mb-4">
                    <img src="{{ asset('static-assets/img/branding/nestogy-logo-white.png') }}" alt="Nestogy" class="img-fluid" style="max-width: 200px;">
                </div>
                <h2 class="fw-bold mb-3">Password Recovery</h2>
                <p class="lead mb-4">We'll help you get back into your account securely</p>
                <div class="text-center">
                    <i class="fas fa-shield-alt fa-4x mb-3 opacity-75"></i>
                    <p class="small">Your security is our priority</p>
                </div>
            </div>
        </div>

        <!-- Right side - Reset Form -->
        <div class="col-lg-6 flex items-center justify-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="bg-white rounded-lg shadow-md overflow-hidden shadow-lg border-0">
                    <div class="p-6 p-5">
                        <!-- Mobile Logo -->
                        <div class="text-center mb-4 d-lg-none">
                            <img src="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" alt="Nestogy" class="img-fluid" style="max-width: 150px;">
                        </div>

                        <h3 class="bg-white rounded-lg shadow-md overflow-hidden-title text-center mb-4 fw-bold">Reset Password</h3>
                        
                        <p class="text-gray-600 text-center mb-4">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>

                        @if (session('status'))
                            <div class="px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                                <i class="fas fa-check-circle mr-2"></i>{{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('auth.password.email') }}">
                            @csrf

                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-envelope mr-1"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       autocomplete="email" 
                                       autofocus
                                       placeholder="Enter your email address">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-4">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-lg">
                                    <i class="fas fa-paper-plane me-1"></i>Send Reset Link
                                </button>
                            </div>

                            <!-- Back to Login -->
                            <div class="text-center">
                                <a href="{{ route('login') }}" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <p class="text-gray-600 small">
                        &copy; {{ date('Y') }} Nestogy ERP. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.bg-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.card {
    border-radius: 15px;
}

.form-control {
    border-radius: 8px;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.min-vh-100 {
    min-height: 100vh;
}

@media (max-width: 991.98px) {
    .card-body {
        padding: 2rem !important;
    }
}
</style>
@endpush
@endsection