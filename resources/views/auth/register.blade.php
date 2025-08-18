@extends('layouts.guest')

@section('title', 'Register User - Nestogy ERP')

@section('content')
<div class="w-full px-4 py-4">
    <div class="flex flex-wrap -mx-4 justify-center">
        <div class="col-lg-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden shadow-lg border-0">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 bg-blue-600 text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus mr-2"></i>Register New User
                    </h4>
                </div>
                <div class="p-6 p-4">
                    @if ($errors->any())
                        <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                            <h6 class="alert-heading">Please correct the following errors:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="flex flex-wrap -mx-4">
                            <!-- Personal Information -->
                            <div class="md:w-1/2 px-4">
                                <h5 class="text-blue-600 mb-3">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h5>

                                <!-- Name -->
                                <div class="mb-3">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input type="text" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           required 
                                           autocomplete="name" 
                                           autofocus
                                           placeholder="Enter full name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email') }}" 
                                           required 
                                           autocomplete="email"
                                           placeholder="Enter email address">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Extension Key -->
                                <div class="mb-3">
                                    <label for="extension_key" class="form-label">Extension Key</label>
                                    <input type="text" 
                                           class="form-control @error('extension_key') is-invalid @enderror" 
                                           id="extension_key" 
                                           name="extension_key" 
                                           value="{{ old('extension_key') }}" 
                                           maxlength="18"
                                           placeholder="Optional phone extension">
                                    @error('extension_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Account Settings -->
                            <div class="md:w-1/2 px-4">
                                <h5 class="text-blue-600 mb-3">
                                    <i class="fas fa-cog me-2"></i>Account Settings
                                </h5>

                                <!-- Company -->
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Company *</label>
                                    <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('company_id') is-invalid @enderror" 
                                            id="company_id" name="company_id" required>
                                        <option value="">Select Company</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('company_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Role -->
                                <div class="mb-3">
                                    <label for="role" class="form-label">User Role *</label>
                                    <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('role') is-invalid @enderror" 
                                            id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        @foreach($roles as $roleId => $roleLabel)
                                            <option value="{{ $roleId }}" {{ old('role') == $roleId ? 'selected' : '' }}>
                                                {{ $roleLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <small>
                                            <strong>Administrator:</strong> Full system access<br>
                                            <strong>Technician:</strong> Technical operations and tickets<br>
                                            <strong>Accountant:</strong> Financial operations and reports
                                        </small>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="status">
                                            Active User Account
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <small>Inactive users cannot log in to the system</small>
                                    </div>
                                </div>

                                <!-- Force MFA -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="force_mfa" name="force_mfa" value="1" {{ old('force_mfa') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="force_mfa">
                                            Require Multi-Factor Authentication
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <small>User will be required to set up 2FA on first login</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <hr class="my-4">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-lock me-2"></i>Password
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control @error('password') is-invalid @enderror" 
                                               id="password" 
                                               name="password" 
                                               required 
                                               autocomplete="new-password"
                                               placeholder="Enter password">
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
                            </div>

                            <div class="col-md-6">
                                <!-- Confirm Password -->
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password_confirmation" 
                                               name="password_confirmation" 
                                               required 
                                               autocomplete="new-password"
                                               placeholder="Confirm password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-between mt-4">
                            <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-user-plus me-1"></i>Create User
                            </button>
                        </div>
                    </form>
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
    
    // Role-based dashboard settings
    const roleSelect = document.getElementById('role');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const selectedRole = parseInt(this.value);
            // You can add logic here to show/hide certain options based on role
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.card {
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
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

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.text-primary {
    color: #667eea !important;
}

.alert {
    border-radius: 8px;
}
</style>
@endpush
@endsection