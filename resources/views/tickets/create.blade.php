@extends('layouts.app')

@section('title', 'Create Ticket')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Create New Ticket</flux:heading>
                <flux:text>Submit a support request for your client</flux:text>
            </div>
            <flux:button href="{{ route('tickets.index') }}" 
                        variant="ghost"
                        icon="arrow-left">
                Back to Tickets
            </flux:button>
        </div>
    </flux:card>

    <form action="{{ route('tickets.store') }}" method="POST" x-data="ticketCreateForm()">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Basic Ticket Information (takes 2 columns on large screens) -->
            <div class="lg:col-span-2">
                <x-tickets.basic-info 
                    :selectedClient="old('client_id', session('selected_client_id')) ? \App\Models\Client::where('company_id', auth()->user()->company_id)->find(old('client_id', session('selected_client_id'))) : null" />
            </div>
            
            <!-- Additional Information (takes 1 column on large screens) -->
            <div class="lg:col-span-1">
                <x-tickets.additional-info />
            </div>
        </div>
        
        <!-- Form Actions -->
        <flux:card class="mt-4">
            <div class="flex items-center justify-between">
                <flux:text size="sm" class="flex items-center gap-1">
                    <flux:icon name="information-circle" variant="mini" />
                    All ticket information will be saved automatically
                </flux:text>
                <div class="flex gap-2">
                    <flux:button href="{{ route('tickets.index') }}" 
                                variant="ghost"
                                icon="x-mark">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" 
                                variant="primary"
                                icon="plus"
                                x-bind:disabled="!isFormValid || submitting"
                                x-show="!submitting">
                        Create Ticket
                    </flux:button>
                    <flux:button type="button" 
                                variant="primary"
                                disabled
                                x-show="submitting"
                                x-cloak>
                        <flux:icon name="arrow-path" class="animate-spin" />
                        Creating...
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </form>
</div>
@endsection

