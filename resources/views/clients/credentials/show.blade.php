@extends('layouts.app')

@section('title', 'Credential Details')

@php
$sidebarContext = 'clients';
$activeDomain = 'clients';
$pageTitle = 'Credential Details';
$pageSubtitle = $credential->name;
$pageActions = [
    ['label' => 'Edit', 'href' => route('clients.credentials.edit', $credential), 'icon' => 'pencil', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        <flux:card>
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:heading size="sm">Client</flux:heading>
                        <flux:text>{{ $credential->client->name ?? 'N/A' }}</flux:text>
                    </div>

                    <div>
                        <flux:heading size="sm">Type</flux:heading>
                        <flux:badge>{{ $credential->credential_type }}</flux:badge>
                    </div>
                </div>

                @if($credential->description)
                    <div>
                        <flux:heading size="sm">Description</flux:heading>
                        <flux:text>{{ $credential->description }}</flux:text>
                    </div>
                @endif

                <flux:separator />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($credential->service_name)
                        <div>
                            <flux:heading size="sm">Service Name</flux:heading>
                            <flux:text>{{ $credential->service_name }}</flux:text>
                        </div>
                    @endif

                    @if($credential->username)
                        <div>
                            <flux:heading size="sm">Username</flux:heading>
                            <flux:text>{{ $credential->username }}</flux:text>
                        </div>
                    @endif

                    @if($credential->email)
                        <div>
                            <flux:heading size="sm">Email</flux:heading>
                            <flux:text>{{ $credential->email }}</flux:text>
                        </div>
                    @endif

                    @if($credential->url)
                        <div>
                            <flux:heading size="sm">URL</flux:heading>
                            <flux:text>{{ $credential->url }}</flux:text>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 justify-end">
                    <flux:button variant="ghost" href="{{ route('clients.credentials.index') }}">Back to Credentials</flux:button>
                    <flux:button variant="primary" href="{{ route('clients.credentials.edit', $credential) }}" icon="pencil">Edit</flux:button>
                </div>
            </div>
        </flux:card>
    </div>
@endsection
