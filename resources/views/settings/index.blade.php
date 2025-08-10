@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4">
        <h1 class="h3 mb-0 text-gray-800">Settings</h1>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="{{ route('settings.index') }}">
                            <i class="fas fa-cog me-2"></i> General
                        </a>
                        <a class="nav-link" href="{{ route('settings.security') }}">
                            <i class="fas fa-shield-alt me-2"></i> Security
                        </a>
                        <a class="nav-link" href="{{ route('settings.email') }}">
                            <i class="fas fa-envelope me-2"></i> Email
                        </a>
                        <a class="nav-link" href="{{ route('settings.integrations') }}">
                            <i class="fas fa-plug me-2"></i> Integrations
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h4>General Settings</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="#">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="{{ Auth::user()->company->name ?? '' }}">
                        </div>

                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-control" id="timezone" name="timezone">
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">Eastern Time</option>
                                <option value="America/Chicago">Central Time</option>
                                <option value="America/Denver">Mountain Time</option>
                                <option value="America/Los_Angeles">Pacific Time</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="date_format" class="form-label">Date Format</label>
                            <select class="form-control" id="date_format" name="date_format">
                                <option value="Y-m-d">YYYY-MM-DD</option>
                                <option value="m/d/Y">MM/DD/YYYY</option>
                                <option value="d/m/Y">DD/MM/YYYY</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-control" id="currency" name="currency">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="CAD">CAD (C$)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection