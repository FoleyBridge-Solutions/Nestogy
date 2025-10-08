@extends('layouts.app')

@section('title', 'Manage Client Tags')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-6">Manage Tags for {{ $client->name }}</h1>

        <form method="POST" action="{{ route('clients.tags', $client) }}">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Tags</label>
                
                <div class="space-y-2">
                    @foreach($allTags as $tag)
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="tags[]" 
                                value="{{ $tag->id }}" 
                                id="tag_{{ $tag->id }}"
                                {{ $client->tags->contains($tag->id) ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <label for="tag_{{ $tag->id }}" class="ml-2 text-sm text-gray-700">
                                {{ $tag->name }}
                            </label>
                        </div>
                    @endforeach
                </div>

                @error('tags')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @error('tags.0')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('clients.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                    Save Tags
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
