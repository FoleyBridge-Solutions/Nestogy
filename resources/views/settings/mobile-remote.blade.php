@extends('layouts.settings')

@section('title', 'Mobile & Remote Settings - Nestogy')

@section('settings-title', 'Mobile & Remote Settings')
@section('settings-description', 'Configure mobile application features and remote access options')

@section('settings-content')
<div>
    <form method="POST" action="{{ route('settings.mobile-remote.update') }}">
        @csrf
        @method('PUT')

        <!-- Settings Configuration -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Configuration Options</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                               type="checkbox" 
                               id="feature_enabled" 
                               name="feature_enabled" 
                               value="1"
                               {{ old('feature_enabled', $setting->feature_enabled ?? false) ? 'checked' : '' }}>
                        <label class="ml-3 block text-sm font-medium text-gray-700" for="feature_enabled">
                            Enable Feature
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
            <a href="{{ route('settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Save Settings</button>
        </div>
    </form>
</div>
@endsection
