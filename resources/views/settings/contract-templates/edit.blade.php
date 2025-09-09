@extends('layouts.settings')

@section('title', 'Edit ' . $template->name . ' - Contract Templates - Settings - Nestogy')

@section('settings-title', 'Edit ' . $template->name)
@section('settings-description', 'Modify contract template settings')

@section('settings-content')
<x-crud-layout :title="'Edit ' . $template->name" :breadcrumbs="[
    ['label' => 'Settings', 'url' => route('settings.index')],
    ['label' => 'Contract Templates', 'url' => route('settings.contract-templates.index')],
    ['label' => $template->name, 'url' => route('settings.contract-templates.show', $template)],
    ['label' => 'Edit', 'url' => route('settings.contract-templates.edit', $template)]
]">

    <form method="POST" action="{{ route('settings.contract-templates.update', $template) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <x-crud-form :fields="[
            [
                'name' => 'name',
                'label' => 'Template Name',
                'type' => 'text',
                'required' => true,
                'value' => $template->name,
                'placeholder' => 'Enter template name',
                'help' => 'A descriptive name for this contract template'
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'value' => $template->slug,
                'placeholder' => 'Auto-generated from name',
                'help' => 'URL-friendly identifier'
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'rows' => 3,
                'value' => $template->description,
                'placeholder' => 'Describe the purpose and use of this template'
            ],
            [
                'name' => 'template_type',
                'label' => 'Template Type',
                'type' => 'select',
                'required' => true,
                'options' => $availableTypes,
                'value' => $template->template_type,
                'placeholder' => 'Select template type'
            ],
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'text',
                'value' => $template->category,
                'placeholder' => 'e.g., MSP, Software, Hardware'
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'required' => true,
                'options' => $availableStatuses,
                'value' => $template->status
            ],
            [
                'name' => 'version',
                'label' => 'Version',
                'type' => 'text',
                'value' => $template->version,
                'placeholder' => '1.0',
                'help' => 'Template version number'
            ],
            [
                'name' => 'is_default',
                'label' => 'Set as Default',
                'type' => 'checkbox',
                'checked' => $template->is_default,
                'help' => 'Make this the default template for its type'
            ]
        ]" />

        <!-- Billing Configuration -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Billing Configuration</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="billing_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Billing Model
                    </label>
                    <select name="billing_model" id="billing_model" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Select billing model</option>
                        @foreach($availableBillingModels as $key => $label)
                            <option value="{{ $key }}" {{ (old('billing_model', $template->billing_model) == $key) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('billing_model')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="default_per_asset_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Default Per Asset Rate ($)
                    </label>
                    <input type="number" name="default_per_asset_rate" id="default_per_asset_rate" 
                           step="0.01" min="0"
                           value="{{ old('default_per_asset_rate', $template->default_per_asset_rate) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('default_per_asset_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="default_per_contact_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Default Per Contact Rate ($)
                    </label>
                    <input type="number" name="default_per_contact_rate" id="default_per_contact_rate" 
                           step="0.01" min="0"
                           value="{{ old('default_per_contact_rate', $template->default_per_contact_rate) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('default_per_contact_rate')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="next_review_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Next Review Date
                    </label>
                    <input type="date" name="next_review_date" id="next_review_date" 
                           value="{{ old('next_review_date', $template->next_review_date?->format('Y-m-d')) }}"
                           min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('next_review_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="requires_approval" value="1" 
                           {{ old('requires_approval', $template->requires_approval) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Requires approval before use</span>
                </label>
            </div>
        </div>

        <!-- Advanced Configuration -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Advanced Configuration</h3>
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tags
                    </label>
                    <input type="text" name="tags_input" id="tags_input" 
                           value="{{ old('tags_input', is_array($template->tags) ? implode(', ', $template->tags) : '') }}"
                           placeholder="Enter tags separated by commas"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Separate multiple tags with commas</p>
                    @error('tags')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('settings.contract-templates.show', $template) }}" 
               class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit" 
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Update Template
            </button>
        </div>
    </form>

</x-crud-layout>

<script>
// Convert tags input to array for form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const tagsInput = document.getElementById('tags_input');
    if (tagsInput.value) {
        const tags = tagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
        
        // Create hidden inputs for each tag
        tags.forEach((tag, index) => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `tags[${index}]`;
            hiddenInput.value = tag;
            this.appendChild(hiddenInput);
        });
    }
});
</script>

@endsection
