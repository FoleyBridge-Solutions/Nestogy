@extends('layouts.settings')

@section('title', 'Settings - Nestogy')

@section('settings-title', 'General Settings')
@section('settings-description', 'Configure basic company information and system preferences')

@section('settings-content')
<div>
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Company Information</h3>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Company Name</label>
                    <input type="text" 
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('company_name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror" 
                           id="company_name" 
                           name="company_name" 
                           value="{{ old('company_name', $settings['company_name'] ?? '') }}" 
                           required>
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timezone</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('timezone') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror" 
                            id="timezone" 
                            name="timezone" 
                            required>
                        @foreach($timezones as $value => $label)
                            <option value="{{ $value }}" 
                                    {{ old('timezone', $settings['timezone'] ?? '') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Format</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('date_format') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror" 
                            id="date_format" 
                            name="date_format" 
                            required>
                        @foreach($dateFormats as $value => $label)
                            <option value="{{ $value }}" 
                                    {{ old('date_format', $settings['date_format'] ?? '') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('date_format')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                    <select class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('currency') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror" 
                            id="currency" 
                            name="currency" 
                            required>
                        @foreach($currencies as $value => $label)
                            <option value="{{ $value }}" 
                                    {{ old('currency', $settings['currency'] ?? '') == $value ? 'selected' : '' }}>
                                {{ $value }} - {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('settings.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Save Settings</button>
        </div>
    </form>
</div>
@endsection