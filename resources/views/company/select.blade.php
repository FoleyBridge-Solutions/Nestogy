@extends('layouts.app')

@section('title', 'Select Company')

@section('content')
<div class="container mx-auto px-4 mx-auto px-4">
    <div class="flex flex-wrap -mx-4 justify-center">
        <div class="md:w-2/3 px-4">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="mb-0">{{ __('Select Company') }}</h4>
                </div>

                <div class="p-6">
                    @if (session('error'))
                        <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <p class="text-gray-600 mb-4">
                        {{ __('Please select a company to continue. You can switch between companies at any time.') }}
                    </p>

                    @if($companies->count() > 0)
                        <form method="POST" action="{{ route('company.set') }}">
                            @csrf
                            
                            <div class="flex flex-wrap -mx-4">
                                @foreach($companies as $company)
                                    <div class="md:w-1/2 px-4 mb-3">
                                        <div class="card company-bg-white rounded-lg shadow-md overflow-hidden h-100" style="cursor: pointer;" onclick="selectCompany({{ $company->id }})">
                                            <div class="p-6 text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-building fa-3x text-blue-600"></i>
                                                </div>
                                                <h5 class="card-title">{{ $company->name }}</h5>
                                                @if($company->description)
                                                    <p class="card-text text-gray-600">{{ $company->description }}</p>
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
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-lg" id="continueBtn" disabled>
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    {{ __('Continue') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            {{ __('No companies are available for your account. Please contact your administrator.') }}
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="{{ route('logout') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                {{ __('Logout') }}
                            </a>
                        </div>
                        
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
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