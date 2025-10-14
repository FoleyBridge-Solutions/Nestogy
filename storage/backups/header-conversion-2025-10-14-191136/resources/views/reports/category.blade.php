@extends('layouts.app')

@section('title', $categoryInfo['name'])

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center h-12 w-12 rounded-lg bg-{{ $categoryInfo['color'] }}-100 mr-4">
                        <svg class="h-6 w-6 text-{{ $categoryInfo['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @switch($categoryInfo['icon'])
                                @case('currency-dollar')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    @break
                                @case('cog')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    @break
                                @case('users')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    @break
                                @case('user-group')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    @break
                                @case('folder')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    @break
                                @case('computer-desktop')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    @break
                                @case('chart-pie')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                    @break
                                @case('shield-check')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    @break
                                @default
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            @endswitch
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $categoryInfo['name'] }}</h1>
                        <p class="mt-1 text-sm text-gray-500">{{ $categoryInfo['description'] }}</p>
                    </div>
                </div>
                <a href="{{ route('reports.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    ← Back to Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($reports as $report)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-md bg-{{ $categoryInfo['color'] }}-100">
                            <svg class="h-6 w-6 text-{{ $categoryInfo['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $report['name'] }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ $report['description'] }}</p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <div class="flex space-x-3">
                        <a href="{{ route('reports.builder', $report['id']) }}" 
                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Configure
                        </a>
                        <button onclick="quickGenerate('{{ $report['id'] }}')" 
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Quick Generate
                        </button>
                    </div>
                    <button onclick="toggleFavorite('{{ $report['id'] }}')" class="text-gray-400 hover:text-yellow-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Empty State -->
    @if(count($reports) === 0)
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No reports available</h3>
        <p class="mt-1 text-sm text-gray-500">No reports have been configured for this category yet.</p>
    </div>
    @endif
</div>

@push('scripts')
<script>
// Quick generate report with default parameters
function quickGenerate(reportId) {
    // Create a form with default parameters
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/reports/generate/${reportId}`;
    
    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    // Add default date range (last 30 days)
    const startDate = document.createElement('input');
    startDate.type = 'hidden';
    startDate.name = 'start_date';
    startDate.value = '{{ now()->subDays(30)->format("Y-m-d") }}';
    form.appendChild(startDate);
    
    const endDate = document.createElement('input');
    endDate.type = 'hidden';
    endDate.name = 'end_date';
    endDate.value = '{{ now()->format("Y-m-d") }}';
    form.appendChild(endDate);
    
    // Add format (HTML for viewing)
    const format = document.createElement('input');
    format.type = 'hidden';
    format.name = 'format';
    format.value = 'html';
    form.appendChild(format);
    
    // Submit the form
    document.body.appendChild(form);
    form.submit();
}

// Toggle favorite status
function toggleFavorite(reportId) {
    // This would normally make an AJAX call to save the favorite
    const button = event.currentTarget;
    const svg = button.querySelector('svg');
    
    if (svg.getAttribute('fill') === 'currentColor') {
        svg.setAttribute('fill', 'none');
        button.classList.remove('text-yellow-500');
        button.classList.add('text-gray-400');
    } else {
        svg.setAttribute('fill', 'currentColor');
        button.classList.remove('text-gray-400');
        button.classList.add('text-yellow-500');
    }
    
    // In a real implementation, save this preference
    console.log('Toggle favorite for report:', reportId);
}
</script>
@endpush
@endsection
