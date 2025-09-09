@extends('layouts.app')

@section('title', 'Create Quote')

@section('content')
<div class="container mx-auto mx-auto">
    <!-- Livewire Quote Wizard -->
    @livewire('financial.quote-wizard')
    
    <!-- Legacy Alpine.js implementation (hidden by default) -->
    <div style="display: none;">
        <!-- This section is preserved for rollback purposes if needed -->
        <!-- The old Alpine.js implementation can be restored by removing the style="display: none;" -->
    </div>
</div>

@endsection
