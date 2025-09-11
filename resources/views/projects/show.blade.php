@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <livewire:project-detail :project="$project" />
@endsection

@push('scripts')
<script>
    // Listen for Livewire notifications
    window.addEventListener('notify', event => {
        const { type, message } = event.detail;
        
        // You can integrate with your notification system here
        // For now, just console log
        console.log(`${type}: ${message}`);
        
        // Or use a toast notification library if available
        if (window.toastr) {
            toastr[type](message);
        }
    });
</script>
@endpush