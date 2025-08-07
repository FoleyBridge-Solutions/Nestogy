@extends('layouts.app')

@section('title', 'Select Company')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Select Company') }}</h4>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <p class="text-muted mb-4">
                        {{ __('Please select a company to continue. You can switch between companies at any time.') }}
                    </p>

                    @if($companies->count() > 0)
                        <form method="POST" action="{{ route('company.set') }}">
                            @csrf
                            
                            <div class="row">
                                @foreach($companies as $company)
                                    <div class="col-md-6 mb-3">
                                        <div class="card company-card h-100" style="cursor: pointer;" onclick="selectCompany({{ $company->id }})">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-building fa-3x text-primary"></i>
                                                </div>
                                                <h5 class="card-title">{{ $company->name }}</h5>
                                                @if($company->description)
                                                    <p class="card-text text-muted">{{ $company->description }}</p>
                                                @endif
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="company_id" value="{{ $company->id }}" id="company_{{ $company->id }}">
                                                    <label class="form-check-label" for="company_{{ $company->id }}">
                                                        {{ __('Select this company') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                    <i class="fas fa-arrow-right me-2"></i>
                                    {{ __('Continue') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ __('No companies are available for your account. Please contact your administrator.') }}
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="{{ route('logout') }}" class="btn btn-secondary"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                {{ __('Logout') }}
                            </a>
                        </div>
                        
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.company-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.company-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.company-card.selected {
    border-color: #007bff;
    background-color: #f8f9ff;
}
</style>

<script>
function selectCompany(companyId) {
    // Remove selected class from all cards
    document.querySelectorAll('.company-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    event.currentTarget.classList.add('selected');
    
    // Select the radio button
    document.getElementById('company_' + companyId).checked = true;
    
    // Enable continue button
    document.getElementById('continueBtn').disabled = false;
}

// Handle radio button changes
document.querySelectorAll('input[name="company_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('continueBtn').disabled = false;
    });
});
</script>
@endsection