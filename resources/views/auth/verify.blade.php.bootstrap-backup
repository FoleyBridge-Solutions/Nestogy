@extends('layouts.app')

@section('title', 'Verify Email - Nestogy ERP')

@section('content')
<div class="container-fluid">
    <div class="row min-vh-100">
        <!-- Left side - Branding -->
        <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary">
            <div class="text-center text-white">
                <div class="mb-4">
                    <img src="{{ asset('assets/img/branding/nestogy-logo-white.png') }}" alt="Nestogy" class="img-fluid" style="max-width: 200px;">
                </div>
                <h2 class="fw-bold mb-3">Email Verification</h2>
                <p class="lead mb-4">We need to verify your email address to secure your account</p>
                <div class="text-center">
                    <i class="fas fa-envelope-open fa-4x mb-3 opacity-75"></i>
                    <p class="small">Check your inbox for the verification link</p>
                </div>
            </div>
        </div>

        <!-- Right side - Verification Form -->
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 500px;">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Mobile Logo -->
                        <div class="text-center mb-4 d-lg-none">
                            <img src="{{ asset('assets/img/branding/nestogy-logo.png') }}" alt="Nestogy" class="img-fluid" style="max-width: 150px;">
                        </div>

                        <h3 class="card-title text-center mb-4 fw-bold">Verify Your Email Address</h3>

                        @if (session('resent'))
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                A fresh verification link has been sent to your email address.
                            </div>
                        @endif

                        @if (session('verified'))
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Your email has been verified successfully!
                            </div>
                        @endif

                        <div class="text-center mb-4">
                            <div class="verification-icon mb-3">
                                <i class="fas fa-envelope fa-3x text-primary"></i>
                            </div>
                            <p class="text-muted">
                                Before proceeding, please check your email for a verification link.
                                If you didn't receive the email, we can send you another one.
                            </p>
                        </div>

                        <div class="verification-info bg-light p-4 rounded mb-4">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle me-2 text-info"></i>What to do next:
                            </h6>
                            <ol class="mb-0 small">
                                <li>Check your email inbox for a message from Nestogy ERP</li>
                                <li>Click the verification link in the email</li>
                                <li>Return to this page to continue</li>
                            </ol>
                        </div>

                        <div class="d-grid gap-2">
                            <!-- Resend Verification Email -->
                            <form method="POST" action="{{ route('verification.resend') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-paper-plane me-1"></i>Resend Verification Email
                                </button>
                            </form>

                            <!-- Logout -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </button>
                            </form>
                        </div>

                        <!-- Help Section -->
                        <div class="text-center mt-4">
                            <p class="text-muted small mb-2">Still having trouble?</p>
                            <div class="help-links">
                                <a href="#" class="text-decoration-none me-3" data-bs-toggle="modal" data-bs-target="#helpModal">
                                    <i class="fas fa-question-circle me-1"></i>Get Help
                                </a>
                                <a href="mailto:support@nestogy.com" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i>Contact Support
                                </a>
                            </div>
                        </div>
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

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Email Verification Help
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold">Can't find the verification email?</h6>
                <ul class="mb-3">
                    <li>Check your spam or junk folder</li>
                    <li>Make sure the email address is correct</li>
                    <li>Wait a few minutes for the email to arrive</li>
                    <li>Try clicking "Resend Verification Email"</li>
                </ul>

                <h6 class="fw-bold">Email link not working?</h6>
                <ul class="mb-3">
                    <li>Make sure you're using the latest email</li>
                    <li>Copy and paste the link directly into your browser</li>
                    <li>Clear your browser cache and try again</li>
                </ul>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> Verification links expire after 60 minutes for security reasons.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="mailto:support@nestogy.com" class="btn btn-primary">
                    <i class="fas fa-envelope me-1"></i>Contact Support
                </a>
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
    border-radius: 8px;
    border: 2px solid #6c757d;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    transform: translateY(-1px);
}

.verification-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.verification-info {
    border-left: 4px solid #17a2b8;
}

.text-primary {
    color: #667eea !important;
}

.help-links a {
    font-size: 0.9rem;
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