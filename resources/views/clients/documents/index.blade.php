@extends('layouts.app')

@section('title', 'Client Documents')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Client Documents</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage and organize client documents across your organization.</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.documents.standalone.export', request()->query()) }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export CSV
                        </a>
                        <a href="{{ route('clients.documents.standalone.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Upload Document
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <form method="GET" action="{{ route('clients.documents.standalone.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Name, description, filename..."
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category" 
                                    id="category" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Categories</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                            <select name="client_id" 
                                    id="client_id" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Confidential -->
                        <div>
                            <label for="confidential" class="block text-sm font-medium text-gray-700">Access Level</label>
                            <select name="confidential" 
                                    id="confidential" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Documents</option>
                                <option value="0" {{ request('confidential') === '0' ? 'selected' : '' }}>Public</option>
                                <option value="1" {{ request('confidential') === '1' ? 'selected' : '' }}>Confidential</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end space-x-3">
                            <div class="space-y-2">
                                <div class="flex items-center h-5">
                                    <input id="show_expired" 
                                           name="show_expired" 
                                           type="checkbox" 
                                           value="1"
                                           {{ request('show_expired') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="show_expired" class="ml-2 text-sm text-gray-700">Show expired</label>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Filter
                                    </button>
                                    <a href="{{ route('clients.documents.standalone.index') }}" 
                                       class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents Grid -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Documents 
                    <span class="text-sm text-gray-500">({{ $documents->total() }} total)</span>
                </h3>
            </div>
            
            @if($documents->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 p-6">
                    @foreach($documents as $document)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200 {{ $document->isExpired() ? 'bg-red-50 border-red-200' : '' }}">
                            <!-- Document Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-2">{{ $document->file_icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">{{ $document->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $document->original_filename }}</p>
                                    </div>
                                </div>
                                @if($document->is_confidential)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        🔒 Confidential
                                    </span>
                                @endif
                            </div>

                            <!-- Document Info -->
                            <div class="space-y-2 mb-4">
                                <div class="text-sm text-gray-600">
                                    <strong>Client:</strong> {{ $document->client->display_name }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    <strong>Category:</strong> {{ $categories[$document->category] ?? $document->category }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    <strong>Size:</strong> {{ $document->file_size_human }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    <strong>Uploaded:</strong> {{ $document->created_at->format('M d, Y') }}
                                </div>
                                @if($document->expires_at)
                                    <div class="text-sm {{ $document->isExpired() ? 'text-red-600' : 'text-gray-600' }}">
                                        <strong>{{ $document->isExpired() ? 'Expired:' : 'Expires:' }}</strong> {{ $document->expires_at->format('M d, Y') }}
                                    </div>
                                @endif
                                @if($document->version > 1)
                                    <div class="text-sm text-gray-600">
                                        <strong>Version:</strong> {{ $document->version }}
                                    </div>
                                @endif
                            </div>

                            <!-- Tags -->
                            @if($document->tags && count($document->tags) > 0)
                                <div class="mb-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice($document->tags, 0, 3) as $tag)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                        @if(count($document->tags) > 3)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                +{{ count($document->tags) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Description -->
                            @if($document->description)
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $document->description }}</p>
                            @endif

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    <a href="{{ route('clients.documents.standalone.download', $document) }}" 
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Download
                                    </a>
                                    <a href="{{ route('clients.documents.standalone.show', $document) }}" 
                                       class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                        View
                                    </a>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('clients.documents.standalone.edit', $document) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</a>
                                    <form method="POST" 
                                          action="{{ route('clients.documents.standalone.destroy', $document) }}" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this document? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 text-sm ml-2">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $documents->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No documents found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by uploading a new document.</p>
                    <div class="mt-6">
                        <a href="{{ route('clients.documents.standalone.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Upload Document
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection