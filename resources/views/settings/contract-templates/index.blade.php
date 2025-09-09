@extends('layouts.settings')

@section('title', 'Contract Templates - Settings - Nestogy')

@section('settings-title', 'Contract Templates')
@section('settings-description', 'Manage your contract templates')

@section('settings-content')
<x-crud-layout title="Contract Templates" :breadcrumbs="[
    ['label' => 'Settings', 'url' => route('settings.index')],
    ['label' => 'Contract Templates', 'url' => route('settings.contract-templates.index')]
]">
    <x-slot name="actions">
        @can('create', App\Domains\Contract\Models\ContractTemplate::class)
            <a href="{{ route('settings.contract-templates.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Template
            </a>
        @endcan
    </x-slot>

    <x-filter-form :filters="[
        [
            'name' => 'search',
            'label' => 'Search',
            'type' => 'text',
            'placeholder' => 'Search templates...',
            'value' => request('search')
        ],
        [
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select',
            'placeholder' => 'All Statuses',
            'options' => $availableStatuses,
            'value' => request('status')
        ],
        [
            'name' => 'template_type',
            'label' => 'Type',
            'type' => 'select',
            'placeholder' => 'All Types',
            'options' => $availableTypes,
            'value' => request('template_type')
        ],
        [
            'name' => 'category',
            'label' => 'Category',
            'type' => 'select',
            'placeholder' => 'All Categories',
            'options' => $availableCategories,
            'value' => request('category')
        ],
        [
            'name' => 'is_default',
            'label' => 'Default',
            'type' => 'select',
            'placeholder' => 'All Templates',
            'options' => ['true' => 'Default Only', 'false' => 'Non-Default Only'],
            'value' => request('is_default')
        ]
    ]" />

    <x-crud-table 
        :items="$templates" 
        :columns="[
            'name' => ['label' => 'Name', 'sortable' => true],
            'template_type' => ['label' => 'Type', 'sortable' => true],
            'category' => ['label' => 'Category', 'sortable' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'version' => ['label' => 'Version', 'sortable' => true],
            'updated_at' => ['label' => 'Updated', 'sortable' => true]
        ]"
        route-prefix="settings.contract-templates"
        checkboxes="true" />
</x-crud-layout>
@endsection
