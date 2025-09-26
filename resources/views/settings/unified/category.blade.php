@extends('layouts.app')

@section('title', $metadata['name'] ?? 'Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('settings.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    Settings
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <a href="{{ route('settings.domain.index', $domain) }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        {{ $domainInfo['name'] }}
                    </a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $metadata['name'] }}
                    </span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $metadata['name'] }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $metadata['description'] }}</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Settings Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <form action="{{ route('settings.category.update', [$domain, $category]) }}" method="POST" id="settings-form">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                @if($category === 'email' && $domain === 'communication')
                    @include('settings.unified.forms.communication-email', ['settings' => $settings])
                @elseif($category === 'physical_mail' && $domain === 'communication')
                    @include('settings.unified.forms.communication-physical-mail', ['settings' => $settings])
                @elseif($category === 'general' && $domain === 'company')
                    @include('settings.unified.forms.company-general', ['settings' => $settings])
                @elseif($category === 'billing' && $domain === 'financial')
                    @include('settings.unified.forms.financial-billing', ['settings' => $settings])
                @else
                    <!-- Generic form for other categories -->
                    <div class="space-y-6">
                        @foreach($settings as $key => $value)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                                </label>
                                @if(is_bool($value))
                                    <select name="{{ $key }}" class="form-select rounded-md border-gray-300 w-full">
                                        <option value="1" {{ $value ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !$value ? 'selected' : '' }}>No</option>
                                    </select>
                                @elseif(is_array($value))
                                    <textarea name="{{ $key }}" rows="3" class="form-textarea rounded-md border-gray-300 w-full">{{ json_encode($value) }}</textarea>
                                @else
                                    <input type="text" name="{{ $key }}" value="{{ $value }}" class="form-input rounded-md border-gray-300 w-full">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                <div>
                    @if(in_array($category, ['email', 'physical_mail']) && $domain === 'communication')
                        <button type="button" onclick="testConfiguration()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            <i class="fas fa-vial mr-2"></i>Test Configuration
                        </button>
                    @endif
                    
                    <button type="button" onclick="resetToDefaults()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 ml-2">
                        <i class="fas fa-undo mr-2"></i>Reset to Defaults
                    </button>
                </div>
                
                <div>
                    <a href="{{ route('settings.domain.index', $domain) }}" class="px-4 py-2 text-gray-600 hover:text-gray-800 mr-2">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="test-result" class="mt-4"></div>
</div>

<script>
function testConfiguration() {
    const form = document.getElementById('settings-form');
    const formData = new FormData(form);
    
    fetch('{{ route('settings.category.test', [$domain, $category]) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('test-result');
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    <strong>Success!</strong> ${data.message}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                    <strong>Error!</strong> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        const resultDiv = document.getElementById('test-result');
        resultDiv.innerHTML = `
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                <strong>Error!</strong> Failed to test configuration: ${error.message}
            </div>
        `;
    });
}

function resetToDefaults() {
    if (confirm('Are you sure you want to reset these settings to defaults? This action cannot be undone.')) {
        window.location.href = '{{ route('settings.category.reset', [$domain, $category]) }}';
    }
}
</script>
@endsection