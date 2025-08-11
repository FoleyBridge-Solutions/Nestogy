@extends('layouts.app')

@section('title', 'Create IT Documentation')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create IT Documentation</h1>
                    <p class="mt-1 text-sm text-gray-500">Add new technical documentation and procedures</p>
                </div>
                <a href="{{ route('clients.it-documentation.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('clients.it-documentation.store') }}" method="POST" enctype="multipart/form-data" x-data="documentationForm()">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Basic Information</h2>
                </div>
                <div class="px-4 py-5 sm:px-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Client Selection -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                            <select name="client_id" id="client_id" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') border-red-500 @enderror">
                                <option value="">Select a client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="it_category" class="block text-sm font-medium text-gray-700">IT Category *</label>
                            <select name="it_category" id="it_category" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('it_category') border-red-500 @enderror">
                                <option value="">Select a category</option>
                                @foreach($categories as $key => $category)
                                    <option value="{{ $key }}" {{ old('it_category') == $key ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                            @error('it_category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Documentation Name *</label>
                        <input type="text" name="name" id="name" required
                               value="{{ old('name') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Access & Review Settings -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Access & Review Settings</h2>
                </div>
                <div class="px-4 py-5 sm:px-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Access Level -->
                        <div>
                            <label for="access_level" class="block text-sm font-medium text-gray-700">Access Level *</label>
                            <select name="access_level" id="access_level" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('access_level') border-red-500 @enderror">
                                @foreach($accessLevels as $key => $level)
                                    <option value="{{ $key }}" {{ old('access_level', 'confidential') == $key ? 'selected' : '' }}>
                                        {{ $level }}
                                    </option>
                                @endforeach
                            </select>
                            @error('access_level')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Review Schedule -->
                        <div>
                            <label for="review_schedule" class="block text-sm font-medium text-gray-700">Review Schedule *</label>
                            <select name="review_schedule" id="review_schedule" required 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('review_schedule') border-red-500 @enderror">
                                @foreach($reviewSchedules as $key => $schedule)
                                    <option value="{{ $key }}" {{ old('review_schedule', 'annually') == $key ? 'selected' : '' }}>
                                        {{ $schedule }}
                                    </option>
                                @endforeach
                            </select>
                            @error('review_schedule')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags</label>
                        <input type="text" name="tags" id="tags"
                               value="{{ old('tags') }}"
                               placeholder="Enter tags separated by commas"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('tags') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Separate multiple tags with commas (e.g., network, security, backup)</p>
                        @error('tags')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Procedure Steps -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900">Procedure Steps</h2>
                        <button type="button" @click="addStep()" 
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Step
                        </button>
                    </div>
                </div>
                <div class="px-4 py-5 sm:px-6">
                    <div x-show="steps.length === 0" class="text-center text-gray-500 py-8">
                        <p>No procedure steps added yet. Click "Add Step" to get started.</p>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(step, index) in steps" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-900" x-text="`Step ${index + 1}`"></h4>
                                    <button type="button" @click="removeStep(index)" 
                                            class="text-red-600 hover:text-red-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <input type="text" 
                                               :name="`procedure_steps[${index}][title]`"
                                               x-model="step.title"
                                               placeholder="Step title"
                                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <textarea :name="`procedure_steps[${index}][description]`"
                                                  x-model="step.description"
                                                  rows="3"
                                                  placeholder="Step description"
                                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                    </div>
                                    <input type="hidden" 
                                           :name="`procedure_steps[${index}][order]`"
                                           :value="index + 1">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- File Attachment -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">File Attachment</h2>
                </div>
                <div class="px-4 py-5 sm:px-6">
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700">Attach File (Optional)</label>
                        <input type="file" name="file" id="file"
                               accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.gif,.zip,.xlsx,.xls,.pptx,.ppt"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('file') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Max file size: 50MB. Allowed formats: PDF, DOC, DOCX, TXT, images, ZIP, Excel, PowerPoint</p>
                        @error('file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-3 sm:px-6 flex justify-end space-x-3">
                <a href="{{ route('clients.it-documentation.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Create Documentation
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function documentationForm() {
    return {
        steps: [],
        
        addStep() {
            this.steps.push({
                title: '',
                description: '',
            });
        },
        
        removeStep(index) {
            this.steps.splice(index, 1);
        }
    };
}
</script>
@endsection