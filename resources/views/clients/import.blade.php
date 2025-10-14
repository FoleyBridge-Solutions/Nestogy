@extends('layouts.app')

@section('title', 'Import Clients')

@php
$pageTitle = 'Import Clients';
$pageSubtitle = 'Upload a CSV file to import multiple clients at once';
@endphp

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-8 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    
                </div>
                <div>
                    <a href="{{ route('clients.index') }}" class="inline-flex items-center px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Clients
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Instructions -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Import Instructions</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>The CSV file must include headers in the first flex flex-wrap</li>
                        <li>Required fields: name, email</li>
                        <li>Optional fields: company, phone, website, address, city, state, zip_code, country, type, lead, rate, currency_code, net_terms, tax_id_number, referral, notes</li>
                        <li>For type field, use: individual or business</li>
                        <li>For lead field, use: 1 for leads, 0 for customers</li>
                        <li>Tags can be included as comma-separated values in a 'tags' column</li>
                        <li>Maximum file size: 10MB</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <form method="POST" action="{{ route('clients.import') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="px-6 py-8 sm:p-6">
                <!-- File Upload -->
                <div class="mb-6">
                    <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CSV File</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                <label for="file" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload a file</span>
                                    <input id="file" name="file" type="file" accept=".csv" class="sr-only" required onchange="updateFileName(this)">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">CSV up to 10MB</p>
                            <p id="file-name" class="text-sm text-gray-900 dark:text-white mt-2"></p>
                        </div>
                    </div>
                    @error('file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Import Options -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Import Options</h3>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="skip_duplicates" name="skip_duplicates" type="checkbox" value="1" checked
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="skip_duplicates" class="font-medium text-gray-700 dark:text-gray-300">Skip duplicate emails</label>
                            <p class="text-gray-500">If checked, clients with email addresses that already exist will be skipped</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="update_existing" name="update_existing" type="checkbox" value="1"
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="update_existing" class="font-medium text-gray-700 dark:text-gray-300">Update existing clients</label>
                            <p class="text-gray-500">If checked, existing clients (matched by email) will be updated with new data</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="import_as_leads" name="import_as_leads" type="checkbox" value="1"
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="import_as_leads" class="font-medium text-gray-700 dark:text-gray-300">Import all as leads</label>
                            <p class="text-gray-500">If checked, all imported clients will be marked as leads regardless of CSV data</p>
                        </div>
                    </div>
                </div>

                <!-- Sample CSV Download -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Need a template?</h3>
                    <p class="text-sm text-gray-500 mb-6">Download our sample CSV file to see the correct format</p>
                    <a href="{{ route('clients.import.template') }}" class="inline-flex items-center px-6 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download Template
                    </a>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-6 bg-gray-50 dark:bg-gray-900 text-right sm:px-6 space-x-3">
                <a href="{{ route('clients.index') }}" class="inline-flex justify-center py-2 px-6 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Import Clients
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Imports -->
    @if(session('import_results'))
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-8 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Import Results</h3>
            <div class="bg-green-50 border-l-4 border-green-400 p-6 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            Successfully imported {{ session('import_results.imported') }} clients
                        </p>
                    </div>
                </div>
            </div>
            
            @if(session('import_results.skipped') > 0)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Skipped {{ session('import_results.skipped') }} duplicate clients
                        </p>
                    </div>
                </div>
            </div>
            @endif
            
            @if(session('import_results.errors') && count(session('import_results.errors')) > 0)
            <div class="bg-red-50 border-l-4 border-red-400 p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Errors encountered:</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach(session('import_results.errors') as $error)
                                    <li>Row {{ $error['flex flex-wrap'] }}: {{ $error['message'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function updateFileName(input) {
    const fileName = input.files[0]?.name;
    const fileNameElement = document.getElementById('file-name');
    if (fileName) {
        fileNameElement.textContent = 'Selected: ' + fileName;
    } else {
        fileNameElement.textContent = '';
    }
}

// Drag and drop functionality
const dropZone = document.querySelector('.border-dashed');
const fileInput = document.getElementById('file');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
}

function unhighlight(e) {
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0 && files[0].type === 'text/csv') {
        fileInput.files = files;
        updateFileName(fileInput);
    } else {
        alert('Please drop a CSV file');
    }
}
</script>
@endpush
