@extends('layouts.app')

@section('title', 'Import Assets')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="md:w-2/3 px-4 offset-md-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="bg-white rounded-lg shadow-md overflow-hidden-title">Import Assets from CSV/Excel</h3>
                </div>
                
                <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="p-6">
                        @if(session('error'))
                            <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <h5>Import Instructions</h5>
                            <ol>
                                <li>Download the <a href="{{ route('assets.template.download') }}">CSV template</a></li>
                                <li>Fill in your asset data following the template format</li>
                                <li>Select the client for these assets</li>
                                <li>Upload your completed CSV or Excel file</li>
                            </ol>
                        </div>

                        <div class="mb-3">
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-600">*</span></label>
                            <select name="client_id" id="client_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') is-invalid @enderror" required>
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">CSV/Excel File <span class="text-red-600">*</span></label>
                            <input type="file" name="file" id="file" 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('file') is-invalid @enderror" 
                                   accept=".csv,.xlsx" required>
                            <small class="text-gray-600">Accepted formats: CSV (.csv) or Excel (.xlsx)</small>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="px-4 py-3 rounded bg-cyan-100 border border-cyan-400 text-cyan-700">
                            <h6 class="alert-heading">CSV Format Requirements:</h6>
                            <p class="mb-0">The CSV file must contain the following columns in order:</p>
                            <ul class="mb-0 mt-2">
                                <li><strong>Name</strong> (required) - Asset name or tag</li>
                                <li><strong>Description</strong> (optional) - Asset description</li>
                                <li><strong>Type</strong> (required) - One of: {{ implode(', ', App\Models\Asset::TYPES) }}</li>
                                <li><strong>Make</strong> (required) - Manufacturer name</li>
                                <li><strong>Model</strong> (optional) - Model number/name</li>
                                <li><strong>Serial</strong> (optional) - Serial number</li>
                                <li><strong>OS</strong> (optional) - Operating system</li>
                                <li><strong>Assigned To</strong> (optional) - Contact name (must exist in the selected client)</li>
                                <li><strong>Location</strong> (optional) - Location name (must exist in the selected client)</li>
                            </ul>
                        </div>

                        <div class="px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700">
                            <h6 class="alert-heading">Important Notes:</h6>
                            <ul class="mb-0">
                                <li>The first row must contain column headers</li>
                                <li>Duplicate asset names will be skipped</li>
                                <li>Contact and Location names must exactly match existing records</li>
                                <li>Invalid data will cause the row to be skipped</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-upload"></i> Import Assets
                        </button>
                        <a href="{{ route('assets.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection