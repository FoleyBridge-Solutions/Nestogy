@extends('layouts.app')

@section('title', 'Import Assets')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import Assets from CSV/Excel</h3>
                </div>
                
                <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">
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
                            <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                            <select name="client_id" id="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
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
                            <label for="file" class="form-label">CSV/Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   accept=".csv,.xlsx" required>
                            <small class="text-muted">Accepted formats: CSV (.csv) or Excel (.xlsx)</small>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
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

                        <div class="alert alert-warning">
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Assets
                        </button>
                        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection