@extends('layouts.app')

@section('title', 'Create Campaign')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Create New Campaign</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Set up a new marketing campaign to engage your leads and contacts.</p>
                    </div>
                    <a href="{{ route('marketing.campaigns.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Campaigns
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <form method="POST" action="{{ route('marketing.campaigns.store') }}" class="space-y-6">
                @csrf
                
                <div class="px-4 py-5 sm:px-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Campaign Information -->
                        <div class="col-span-2">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Campaign Information</h4>
                        </div>

                        <!-- Campaign Name -->
                        <div class="col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Campaign Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   placeholder="e.g., Welcome Series, MSP Services Nurture, Q1 Promotion"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') border-red-300 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Campaign Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Campaign Type *</label>
                            <select name="type" id="type" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('type') border-red-300 @enderror">
                                <option value="">Select a type</option>
                                <option value="email" {{ old('type') === 'email' ? 'selected' : '' }}>Email Campaign</option>
                                <option value="nurture" {{ old('type') === 'nurture' ? 'selected' : '' }}>Nurture Sequence</option>
                                <option value="drip" {{ old('type') === 'drip' ? 'selected' : '' }}>Drip Campaign</option>
                                <option value="event" {{ old('type') === 'event' ? 'selected' : '' }}>Event Follow-up</option>
                                <option value="webinar" {{ old('type') === 'webinar' ? 'selected' : '' }}>Webinar Series</option>
                                <option value="content" {{ old('type') === 'content' ? 'selected' : '' }}>Content Marketing</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Auto Enroll -->
                        <div class="flex items-center">
                            <input type="checkbox" name="auto_enroll" id="auto_enroll" value="1" {{ old('auto_enroll') ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="auto_enroll" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Auto-enroll matching leads
                            </label>
                        </div>

                        <!-- Description -->
                        <div class="col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea name="description" id="description" rows="3" 
                                      placeholder="Describe the purpose and goals of this campaign..."
                                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Scheduling -->
                        <div class="col-span-2 mt-8">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Scheduling</h4>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                            <input type="datetime-local" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('start_date') border-red-300 @enderror">
                            <p class="mt-1 text-xs text-gray-500">Leave blank to start manually</p>
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                            <input type="datetime-local" name="end_date" id="end_date" value="{{ old('end_date') }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('end_date') border-red-300 @enderror">
                            <p class="mt-1 text-xs text-gray-500">Optional automatic end date</p>
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Target Criteria -->
                        <div class="col-span-2 mt-8">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Target Criteria</h4>
                            <p class="text-sm text-gray-500 mb-4">Define who should receive this campaign. You can enroll leads manually after creating the campaign.</p>
                        </div>

                        <!-- Lead Score Range -->
                        <div>
                            <label for="min_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Score</label>
                            <input type="number" name="target_criteria[min_score]" id="min_score" min="0" max="100" 
                                   value="{{ old('target_criteria.min_score', 0) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="max_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maximum Score</label>
                            <input type="number" name="target_criteria[max_score]" id="max_score" min="0" max="100" 
                                   value="{{ old('target_criteria.max_score', 100) }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Lead Statuses -->
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Target Lead Statuses</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach(['new', 'contacted', 'qualified', 'proposal', 'negotiation'] as $status)
                                    <div class="flex items-center">
                                        <input type="checkbox" name="target_criteria[statuses][]" value="{{ $status }}" 
                                               id="status_{{ $status }}" 
                                               {{ in_array($status, old('target_criteria.statuses', ['new', 'contacted'])) ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="status_{{ $status }}" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                            {{ ucfirst($status) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6 rounded-b-lg">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('marketing.campaigns.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Create Campaign
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Help Text -->
        <div class="mt-6 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Next Steps</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p>After creating your campaign, you'll be able to:</p>
                        <ul class="mt-1 ml-4 list-disc">
                            <li>Add email sequences and templates</li>
                            <li>Enroll leads and contacts</li>
                            <li>Set up automation rules</li>
                            <li>Monitor performance metrics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection