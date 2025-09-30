@extends('layouts.app')

@section('title', $metadata['name'] ?? 'Settings')

@section('content')
<flux:container>
    <!-- Breadcrumb -->
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item href="{{ route('settings.index') }}" icon="home">
            Settings
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('settings.domain.index', $domain) }}">
            {{ $domainInfo['name'] }}
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            {{ $metadata['name'] }}
        </flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Header -->
    <flux:card class="mb-6">
        <flux:card.body>
            <flux:heading size="xl">{{ $metadata['name'] }}</flux:heading>
            <flux:text class="mt-2">{{ $metadata['description'] }}</flux:text>
        </flux:card.body>
    </flux:card>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <flux:toast variant="success" dismissible class="mb-6">
            {{ session('success') }}
        </flux:toast>
    @endif

    @if(session('error'))
        <flux:toast variant="danger" dismissible class="mb-6">
            {{ session('error') }}
        </flux:toast>
    @endif

    <!-- Settings Form -->
    <flux:card>
        <form action="{{ route('settings.category.update', [$domain, $category]) }}" method="POST" id="settings-form">
            @csrf
            @method('PUT')
            
            <flux:card.body>
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
                    <flux:field.group>
                        @foreach($settings as $key => $value)
                            <flux:field>
                                <flux:label>{{ ucfirst(str_replace('_', ' ', $key)) }}</flux:label>
                                @if(is_bool($value))
                                    <flux:select name="{{ $key }}">
                                        <flux:option value="1" {{ $value ? 'selected' : '' }}>Yes</flux:option>
                                        <flux:option value="0" {{ !$value ? 'selected' : '' }}>No</flux:option>
                                    </flux:select>
                                @elseif(is_array($value))
                                    <flux:textarea name="{{ $key }}" rows="3">{{ json_encode($value) }}</flux:textarea>
                                @else
                                    <flux:input type="text" name="{{ $key }}" value="{{ $value }}" />
                                @endif
                            </flux:field>
                        @endforeach
                    </flux:field.group>
                @endif
            </flux:card.body>
            
            <flux:card.footer>
                <flux:between>
                    <flux:button.group>
                        @if(in_array($category, ['email', 'physical_mail']) && $domain === 'communication')
                            <flux:button type="button" variant="secondary" icon="beaker" onclick="testConfiguration()">
                                Test Configuration
                            </flux:button>
                        @endif
                        
                        <flux:button type="button" variant="secondary" icon="arrow-uturn-left" onclick="resetToDefaults()">
                            Reset to Defaults
                        </flux:button>
                    </flux:button.group>
                    
                    <flux:button.group>
                        <flux:button variant="ghost" href="{{ route('settings.domain.index', $domain) }}">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" icon="check">
                            Save Settings
                        </flux:button>
                    </flux:button.group>
                </flux:between>
            </flux:card.footer>
        </form>
    </flux:card>

    <div id="test-result"></div>
</flux:container>

@push('scripts')
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
                <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2 text-green-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <strong>${data.message}</strong>
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2 text-red-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <strong>${data.message}</strong>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        const resultDiv = document.getElementById('test-result');
        resultDiv.innerHTML = `
            <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center gap-2 text-red-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <strong>Failed to test configuration: ${error.message}</strong>
                </div>
            </div>
        `;
    });
}

function resetToDefaults() {
    if (confirm('Are you sure you want to reset these settings to defaults? This action cannot be undone.')) {
        fetch('{{ route('settings.category.reset', [$domain, $category]) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(() => window.location.reload())
        .catch(error => alert('Failed to reset: ' + error.message));
    }
}
</script>
@endpush
@endsection