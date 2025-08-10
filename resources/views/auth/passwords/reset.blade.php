@extends('layouts.guest')

@section('title', 'Reset Password - Nestogy ERP')

@section('content')
<div class="container-fluid">
    <div class="row min-vh-100">
        <!-- Left side - Branding -->
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary">
            <div class="text-center text-white">
                <div class="mb-4">
                    <img src="{{ asset('assets/img/branding/nestogy-logo-white.png') }}" alt="Nestogy" class="img-fluid" style="max-width: 200px;">
                </div>
                <h2 class="fw-bold mb-3">Set New Password</h2>
                <p class="lead mb-4">Create a strong password to secure your account</p>
                <div class="text-center">
                    <i class="fas fa-key fa-4x mb-3 opacity-75"></i>
                    <p class="small">Choose a password that's easy to remember but hard to guess</p>
                </div>
            </div>
        </div>

        <!-- Right side - Reset Form -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Mobile Logo -->
                        <div class="text-center mb-4 d-lg-none">
                            <img src="{{ asset('assets/img/branding/nestogy-logo.png') }}" alt="Nestogy" class="img-fluid" style="max-width: 150px;">
                        </div>

                        <h3 class="card-title text-center mb-4 fw-bold">Reset Password</h3>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('auth.password.update') }}">
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ $email ?? old('email') }}" 
                                       required 
                                       autocomplete="email" 
                                       readonly
                                       style="background-color: #f8f9fa;">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           autocomplete="new-password"
                                           placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <small>Minimum 8 characters required</small>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Confirm New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           required 
                                           autocomplete="new-password"
                                           placeholder="Confirm new password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Password Strength Indicator -->
                            <div class="mb-4">
                                <div class="password-strength">
                                    <div class="strength-meter">
                                        <div class="strength-meter-fill" id="strengthMeter"></div>
                                    </div>
                                    <small class="strength-text" id="strengthText">Enter a password</small>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-1"></i>Reset Password
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
                    <p class="text-muted small">
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
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }
    
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('togglePasswordConfirm', 'password_confirmation');
    
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthMeter = document.getElementById('strengthMeter');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput && strengthMeter && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            strengthMeter.style.width = strength.percentage + '%';
            strengthMeter.className = 'strength-meter-fill ' + strength.class;
            strengthText.textContent = strength.text;
            strengthText.className = 'strength-text ' + strength.class;
        });
    }
    
    function calculatePasswordStrength(password) {
        let score = 0;
        let feedback = [];
        
        if (password.length >= 8) score += 1;
        else feedback.push('at least 8 characters');
        
        if (/[a-z]/.test(password)) score += 1;
        else feedback.push('lowercase letters');
        
        if (/[A-Z]/.test(password)) score += 1;
        else feedback.push('uppercase letters');
        
        if (/[0-9]/.test(password)) score += 1;
        else feedback.push('numbers');
        
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        else feedback.push('special characters');
        
        const strengths = [
            { class: 'weak', text: 'Very Weak', percentage: 20 },
            { class: 'weak', text: 'Weak', percentage: 40 },
            { class: 'fair', text: 'Fair', percentage: 60 },
            { class: 'good', text: 'Good', percentage: 80 },
            { class: 'strong', text: 'Strong', percentage: 100 }
        ];
        
        if (password.length === 0) {
            return { class: '', text: 'Enter a password', percentage: 0 };
        }
        
        return strengths[score] || strengths[0];
    }
});
</script>
@endpush

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

.btn-outline-secondary {
    border-left: none;
    border-radius: 0 8px 8px 0;
}

.input-group .form-control {
    border-radius: 8px 0 0 8px;
}

.password-strength {
    margin-bottom: 1rem;
}

.strength-meter {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.strength-meter-fill {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-meter-fill.weak { background-color: #dc3545; }
.strength-meter-fill.fair { background-color: #ffc107; }
.strength-meter-fill.good { background-color: #17a2b8; }
.strength-meter-fill.strong { background-color: #28a745; }

.strength-text.weak { color: #dc3545; }
.strength-text.fair { color: #ffc107; }
.strength-text.good { color: #17a2b8; }
.strength-text.strong { color: #28a745; }

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