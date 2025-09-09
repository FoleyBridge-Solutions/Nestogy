@extends('layouts.app')

@section('title', 'Import Leads')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Import Leads from CSV</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Upload a CSV file to import multiple leads at once
                    </p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('clients.leads.import.template') }}" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Template
                    </a>
                    <a href="{{ route('clients.leads') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Leads
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Instructions -->
    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">CSV Format Requirements</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <p>Your CSV file should include the following columns (column names can vary):</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <strong>Required:</strong> Last, First, Email<br>
                            <strong>Optional:</strong> Middle, Phone, Website
                        </div>
                        <div>
                            <strong>Company:</strong> Company Name, Address Line 1, Address Line 2, City, State, ZIP
                        </div>
                    </div>
                    <p class="mt-2"><strong>Expected Format:</strong> Last First Middle Company Name Company Address Line 1 Company Address Line 2 City State ZIP Email Website Phone</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <form method="POST" action="{{ route('clients.leads.import') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- File Upload -->
                    <div class="col-span-2">
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CSV File *</label>
                        <div id="drop_zone" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 focus-within:border-blue-500 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span id="file_label">Upload a CSV file</span>
                                        <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" required class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">CSV or TXT files up to 10MB</p>
                                <div id="file_info" class="hidden mt-2 text-sm text-green-600"></div>
                            </div>
                        </div>
                        @error('csv_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Import Settings -->
                    <div class="col-span-2 mt-8">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Import Settings</h4>
                    </div>

                    <!-- Default Status -->
                    <div>
                        <label for="default_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Status *</label>
                        <select name="default_status" id="default_status" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="active" {{ old('default_status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('default_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <!-- Client Type -->
                    <div>
                        <label for="default_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Type</label>
                        <select name="default_type" id="default_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Not specified</option>
                            <option value="prospect" {{ old('default_type', 'prospect') === 'prospect' ? 'selected' : '' }}>Prospect</option>
                            <option value="customer" {{ old('default_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="partner" {{ old('default_type') === 'partner' ? 'selected' : '' }}>Partner</option>
                        </select>
                    </div>

                    <!-- Skip Duplicates -->
                    <div class="col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="skip_duplicates" id="skip_duplicates" value="1" 
                                   {{ old('skip_duplicates', true) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="skip_duplicates" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Skip duplicate emails (recommended)
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Leads with email addresses that already exist will be skipped.</p>
                    </div>

                    <!-- Import Notes -->
                    <div class="col-span-2">
                        <label for="import_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Import Notes</label>
                        <textarea name="import_notes" id="import_notes" rows="3" 
                                  placeholder="Optional notes to add to all imported leads..."
                                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('import_notes', 'Imported from CSV on ' . date('Y-m-d')) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right rounded-b-lg">
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('clients.leads') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Import Leads
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Show Import Results -->
    @if(session('import_details'))
        <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-5">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Import Results</h3>
                <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        @foreach(session('import_details') as $detail)
                            <div class="@if(str_contains($detail, 'Error')) text-red-600 @elseif(str_contains($detail, 'Skipped')) text-yellow-600 @else text-green-600 @endif">
                                {{ $detail }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('csv_file');
    const dropZone = document.getElementById('drop_zone');
    const fileLabel = document.getElementById('file_label');
    const fileInfo = document.getElementById('file_info');

    // File validation function
    function validateFile(file) {
        console.log('Validating file:', file.name, 'Size:', file.size, 'Type:', file.type);
        
        // Check file size (10MB limit)
        const fileSize = file.size / 1024 / 1024; // MB
        if (fileSize > 10) {
            alert('File size must be less than 10MB. Your file is ' + fileSize.toFixed(2) + 'MB');
            return false;
        }
        
        // Check file extension
        const fileName = file.name.toLowerCase();
        if (!fileName.endsWith('.csv') && !fileName.endsWith('.txt')) {
            alert('Please select a CSV or TXT file. Selected: ' + fileName);
            return false;
        }
        
        return true;
    }

    // Handle file selection
    function handleFile(file) {
        console.log('Handling file:', file);
        
        if (!validateFile(file)) {
            fileInput.value = '';
            return;
        }

        // Update UI to show selected file
        const fileSize = (file.size / 1024).toFixed(1);
        fileLabel.textContent = file.name;
        fileInfo.textContent = `Selected: ${file.name} (${fileSize} KB)`;
        fileInfo.classList.remove('hidden');
        dropZone.classList.add('border-green-400', 'bg-green-50');
        dropZone.classList.remove('border-gray-300');
        
        console.log('File successfully selected:', file.name);
    }

    // File input change event
    fileInput.addEventListener('change', function(e) {
        console.log('File input changed');
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        } else {
            // Reset UI if no file selected
            fileLabel.textContent = 'Upload a CSV file';
            fileInfo.classList.add('hidden');
            dropZone.classList.remove('border-green-400', 'bg-green-50');
            dropZone.classList.add('border-gray-300');
        }
    });

    // Drag and drop events
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        console.log('File dropped');
        
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            fileInput.files = files; // Set the file input
            handleFile(file);
        }
    });

    // Form submission debug
    const form = fileInput.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitting...');
            const file = fileInput.files[0];
            if (!file) {
                e.preventDefault();
                alert('Please select a CSV file to upload');
                return false;
            }
            console.log('Form submitted with file:', file.name);
        });
    }

    console.log('File upload handlers initialized');
});
</script>
@endsection