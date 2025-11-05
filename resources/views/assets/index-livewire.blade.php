@extends('layouts.app')
@php
$pageTitle = 'Assets';
$pageSubtitle = 'Manage and track your IT assets';
$pageActions = [];

// Get active RMM integration for deployment link
$rmmIntegration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)
    ->where('is_active', true)
    ->first();
@endphp
@section('content')
    <div class="container-fluid">
        <div class="mb-4 flex justify-end gap-3">
            @if($rmmIntegration)
                @livewire('assets.copy-agent-deployment-link')
            @endif
            <flux:button 
                variant="primary" 
                icon="plus"
                href="{{ route('assets.create') }}">
                Create Asset
            </flux:button>
        </div>
        @livewire('assets.asset-index')
    </div>
@endsection