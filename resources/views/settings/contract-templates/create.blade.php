@extends('layouts.settings')

@section('title', 'Create Contract Template - Settings - Nestogy')

@section('settings-title', 'Create Contract Template')
@section('settings-description', 'Create a new contract template')

@section('settings-content')
<!-- Breadcrumbs -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="{{ route('settings.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                Settings
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                <a href="{{ route('settings.contract-templates.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Contract Templates</a>
            </div>
        </li>
        <li aria-current="page">
            <div class="flex items-center">
                <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Create</span>
            </div>
        </li>
    </ol>
</nav>

<x-crud-form 
    :action="route('settings.contract-templates.store')" 
    title="Contract Template Details"
    :cancel-route="route('settings.contract-templates.index')"
    :fields="[
            [
                'name' => 'name',
                'label' => 'Template Name',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter template name',
                'help' => 'A descriptive name for this contract template'
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'placeholder' => 'Auto-generated from name',
                'help' => 'URL-friendly identifier (auto-generated if left empty)'
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'rows' => 3,
                'placeholder' => 'Describe the purpose and use of this template'
            ],
            [
                'name' => 'template_type',
                'label' => 'Template Type',
                'type' => 'select',
                'required' => true,
                'options' => $availableTypes,
                'placeholder' => 'Select template type'
            ],
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'options' => $availableCategories,
                'placeholder' => 'Select category'
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'required' => true,
                'options' => $availableStatuses,
                'value' => 'draft'
            ],
            [
                'name' => 'version',
                'label' => 'Version',
                'type' => 'text',
                'placeholder' => '1.0',
                'help' => 'Template version number'
            ],
            [
                'name' => 'is_default',
                'label' => 'Set as Default',
                'type' => 'checkbox',
                'help' => 'Make this the default template for its type'
            ],
            [
                'name' => 'billing_model',
                'label' => 'Billing Model',
                'type' => 'select',
                'options' => $availableBillingModels,
                'placeholder' => 'Select billing model'
            ],
            [
                'name' => 'default_per_asset_rate',
                'label' => 'Default Per Asset Rate ($)',
                'type' => 'number',
                'step' => '0.01',
                'min' => '0'
            ],
            [
                'name' => 'default_per_contact_rate', 
                'label' => 'Default Per Contact Rate ($)',
                'type' => 'number',
                'step' => '0.01',
                'min' => '0'
            ],
            [
                'name' => 'next_review_date',
                'label' => 'Next Review Date',
                'type' => 'date'
            ],
            [
                'name' => 'requires_approval',
                'label' => 'Requires approval before use',
                'type' => 'checkbox'
            ]
        ]" 
    submit-text="Create Template" />
@endsection
