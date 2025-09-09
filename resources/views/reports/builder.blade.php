@extends('layouts.app')

@section('title', 'Report Builder - ' . $reportInfo['name'])

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reportInfo['name'] }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $reportInfo['description'] }}</p>
                </div>
                <a href="{{ route('reports.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    ← Back to Reports
                </a>
            </div>
        </div>

        <!-- Report Configuration Form -->
        <form id="reportForm" action="{{ route('reports.generate', $reportId) }}" method="POST" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <!-- Date Range Selection -->
                @if($filters['date_range'] ?? false)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input type="date" name="start_date" id="start_date" 
                               value="{{ now()->subMonth()->format('Y-m-d') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input type="date" name="end_date" id="end_date" 
                               value="{{ now()->format('Y-m-d') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>
                @endif
                
                <!-- As Of Date (for snapshot reports) -->
                @if($filters['as_of_date'] ?? false)
                <div>
                    <label for="as_of_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">As of Date</label>
                    <input type="date" name="as_of_date" id="as_of_date" 
                           value="{{ now()->format('Y-m-d') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                @endif
                
                <!-- Client Selection -->
                @if($filters['client'] ?? false)
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Client (Optional)</label>
                    <select name="client_id" id="client_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Clients</option>
                        @if(isset($clients))
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @endif
                
                <!-- User/Technician Selection -->
                @if($filters['user'] ?? false || $filters['technician'] ?? false)
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">User/Technician (Optional)</label>
                    <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Users</option>
                        @if(isset($users))
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @endif
                
                <!-- Status Filter -->
                @if($filters['status'] ?? false)
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in-progress">In Progress</option>
                        <option value="closed">Closed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                @endif
                
                <!-- Priority Filter -->
                @if($filters['priority'] ?? false)
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                    <select name="priority" id="priority" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                @endif
                
                <!-- Category Filter -->
                @if($filters['category'] ?? false)
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Categories</option>
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="network">Network</option>
                        <option value="security">Security</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                @endif
                
                <!-- Group By Option -->
                @if($filters['group_by'] ?? false)
                <div>
                    <label for="group_by" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Group By</label>
                    <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">No Grouping</option>
                        <option value="client">Client</option>
                        <option value="user">User</option>
                        <option value="category">Category</option>
                        <option value="status">Status</option>
                        <option value="month">Month</option>
                    </select>
                </div>
                @endif
                
                <!-- Output Format -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Output Format</label>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-5 gap-3">
                        <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-gray-800 p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="format" value="html" class="sr-only" checked>
                            <div class="flex flex-1">
                                <div class="flex flex-col-span-12">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">View Online</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">HTML</span>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </label>
                        
                        <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-gray-800 p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="format" value="pdf" class="sr-only">
                            <div class="flex flex-1">
                                <div class="flex flex-col-span-12">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">PDF</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">Download</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-gray-800 p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="format" value="excel" class="sr-only">
                            <div class="flex flex-1">
                                <div class="flex flex-col-span-12">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Excel</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">XLSX</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-gray-800 p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="format" value="csv" class="sr-only">
                            <div class="flex flex-1">
                                <div class="flex flex-col-span-12">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">CSV</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">Export</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative flex cursor-pointer rounded-lg border bg-white dark:bg-gray-800 p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="format" value="json" class="sr-only">
                            <div class="flex flex-1">
                                <div class="flex flex-col-span-12">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">JSON</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">API</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-6 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Generate Report
                    </button>
                    
                    <button type="button" onclick="saveReport()" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="mr-2 -ml-1 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V2"></path>
                        </svg>
                        Save Configuration
                    </button>
                    
                    <button type="button" onclick="scheduleReport()" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="mr-2 -ml-1 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Schedule Report
                    </button>
                </div>
                
                <button type="button" onclick="resetForm()" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white dark:text-white">
                    Reset Form
                </button>
            </div>
        </form>
    </div>
    
    <!-- Report Preview Section (shown after generation) -->
    <div id="reportPreview" class="hidden mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Report Preview</h2>
        </div>
        <div id="reportContent" class="p-6">
            <!-- Report content will be loaded here -->
        </div>
    </div>
</div>

@push('scripts')
<script>
// Handle form submission
document.getElementById('reportForm').addEventListener('submit', function(e) {
    const format = document.querySelector('input[name="format"]:checked').value;
    
    if (format === 'html') {
        e.preventDefault();
        generateReport();
    }
});

// Generate report via AJAX for HTML preview
function generateReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayReport(data);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating report');
    });
}

// Display report preview
function displayReport(data) {
    const previewSection = document.getElementById('reportPreview');
    const contentDiv = document.getElementById('reportContent');
    
    // Build HTML content from data
    let html = '<div class="space-y-6">';
    
    // Add metrics if available
    if (data.metrics) {
        html += '<div class="grid grid-cols-1 md:grid-cols-4 gap-4">';
        for (const [key, value] of Object.entries(data.metrics)) {
            html += `
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-500">${key.replace(/_/g, ' ').toUpperCase()}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">${formatValue(value)}</div>
                </div>
            `;
        }
        html += '</div>';
    }
    
    // Add charts or tables based on data structure
    // This is a simplified version - you'd want more sophisticated rendering
    html += '</div>';
    
    contentDiv.innerHTML = html;
    previewSection.classList.remove('hidden');
    
    // Scroll to preview
    previewSection.scrollIntoView({ behavior: 'smooth' });
}

// Format values for display
function formatValue(value) {
    if (typeof value === 'number') {
        if (value >= 1000000) {
            return '$' + (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return '$' + (value / 1000).toFixed(1) + 'K';
        } else {
            return '$' + value.toFixed(2);
        }
    }
    return value;
}

// Save report configuration
function saveReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Add report metadata
    formData.append('report_id', '{{ $reportId }}');
    formData.append('name', prompt('Enter a name for this saved report:'));
    
    fetch('{{ route("reports.save") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving report');
    });
}

// Schedule report
function scheduleReport() {
    // Open schedule modal
    alert('Report scheduling interface coming soon!');
}

// Reset form
function resetForm() {
    document.getElementById('reportForm').reset();
    document.getElementById('reportPreview').classList.add('hidden');
}

// Handle radio button styling
document.querySelectorAll('input[type="radio"][name="format"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Update styling
        document.querySelectorAll('label:has(input[name="format"])').forEach(label => {
            const input = label.querySelector('input');
            const svg = label.querySelector('svg');
            
            if (input.checked) {
                label.classList.add('border-indigo-600', 'ring-2', 'ring-indigo-600');
                svg?.classList.remove('hidden');
            } else {
                label.classList.remove('border-indigo-600', 'ring-2', 'ring-indigo-600');
                svg?.classList.add('hidden');
            }
        });
    });
});
</script>
@endpush
@endsection
