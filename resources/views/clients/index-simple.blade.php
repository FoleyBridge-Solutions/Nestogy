@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @livewire('clients-list')
</div>

@push('scripts')
<script>
    // Add smooth scroll to top when client is selected
    window.addEventListener('select-client', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endpush
@endsection