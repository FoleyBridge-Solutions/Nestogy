@extends('layouts.app')

@section('title', $itDocumentation->name)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-8 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-3xl">{{ $itDocumentation->category_icon }}</span>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $itDocumentation->name }}</h1>
                        <div class="flex items-center space-x-4 mt-1">
                            <span class="text-sm text-gray-500">{{ $itDocumentation->client->name }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ \App\Domains\Client\Models\ClientITDocumentation::getITCategories()[$itDocumentation->it_category] }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $itDocumentation->access_level_color }}-100 text-{{ $itDocumentation->access_level_color }}-800">
                                {{ \App\Domains\Client\Models\ClientITDocumentation::getAccessLevels()[$itDocumentation->access_level] }}
                            </span>
                            @if($itDocumentation->needsReview())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Review Due
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if($itDocumentation->hasFile())
                        @can('download', $itDocumentation)
                            <a href="{{ route('clients.it-documentation.download', $itDocumentation) }}" 
                               class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download
                            </a>
                        @endcan
                    @endif
                    
                    @can('update', $itDocumentation)
                        <a href="{{ route('clients.it-documentation.edit', $itDocumentation) }}" 
                           class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </a>
                    @endcan

                    <a href="{{ route('clients.it-documentation.index') }}" 
                       class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:flex-1 px-6-span-2 space-y-6">
            <!-- Description -->
            @if($itDocumentation->description)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Description</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div class="prose max-w-none">
                            {!! nl2br(e($itDocumentation->description)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Procedure Steps -->
            @if($itDocumentation->procedure_steps && count($itDocumentation->procedure_steps) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Procedure Steps</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div class="space-y-6">
                            @foreach($itDocumentation->procedure_steps as $index => $step)
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600">{{ $index + 1 }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-sm font-medium text-gray-900">{{ $step['title'] }}</h3>
                                        <div class="mt-1 text-sm text-gray-600 prose max-w-none">
                                            {!! nl2br(e($step['description'])) !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Network Diagram -->
            @if($itDocumentation->network_diagram && count($itDocumentation->network_diagram) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Network Infrastructure Diagram</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div id="network-diagram-viewer" class="border border-gray-300 rounded-lg bg-gray-50" style="height: 600px;"></div>
                        <div class="mt-6 flex justify-end">
                            <button type="button" onclick="exportDiagram()" 
                                    class="inline-flex items-center px-6 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Export Diagram
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- System References -->
            @if($itDocumentation->system_references && count($itDocumentation->system_references) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">System References</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div class="flex flex-wrap gap-2">
                            @foreach($itDocumentation->system_references as $reference)
                                <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    {{ $reference }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- IP Addresses -->
            @if($itDocumentation->ip_addresses && count($itDocumentation->ip_addresses) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">IP Addresses</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div class="flex flex-wrap gap-2">
                            @foreach($itDocumentation->ip_addresses as $ip)
                                <code class="px-6 py-1 bg-gray-100 rounded text-sm font-mono">{{ $ip }}</code>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Software Versions -->
            @if($itDocumentation->software_versions && count($itDocumentation->software_versions) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Software Versions</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div class="space-y-3">
                            @foreach($itDocumentation->software_versions as $software)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                    <span class="font-medium text-gray-900">{{ $software['name'] }}</span>
                                    <code class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">{{ $software['version'] }}</code>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Document Info -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Document Information</h2>
                </div>
                <div class="px-6 py-8 sm:px-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Version</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $itDocumentation->version }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Author</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $itDocumentation->author->name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $itDocumentation->created_at->format('M j, Y \a\t g:i A') }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $itDocumentation->updated_at->format('M j, Y \a\t g:i A') }}</dd>
                    </div>

                    @if($itDocumentation->last_reviewed_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Reviewed</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $itDocumentation->last_reviewed_at->format('M j, Y') }}</dd>
                        </div>
                    @endif

                    @if($itDocumentation->next_review_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Next Review</dt>
                            <dd class="mt-1 text-sm {{ $itDocumentation->needsReview() ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                {{ $itDocumentation->next_review_at->format('M j, Y') }}
                                @if($itDocumentation->needsReview())
                                    (Overdue)
                                @endif
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Review Schedule</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \App\Domains\Client\Models\ClientITDocumentation::getReviewSchedules()[$itDocumentation->review_schedule] }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Access Count</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $itDocumentation->access_count }}</dd>
                    </div>

                    @if($itDocumentation->hasFile())
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Attached File</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $itDocumentation->original_filename }}
                                <span class="text-gray-500">({{ $itDocumentation->file_size_human }})</span>
                            </dd>
                        </div>
                    @endif
                </div>

                <!-- Review Action -->
                @if($itDocumentation->needsReview())
                    @can('completeReview', $itDocumentation)
                        <div class="px-6 py-6 bg-red-50 border-t border-gray-200">
                            <form method="POST" action="{{ route('clients.it-documentation.complete-review', $itDocumentation) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Mark as Reviewed
                                </button>
                            </form>
                        </div>
                    @endcan
                @endif
            </div>

            <!-- Tags -->
            @if($itDocumentation->tags && count($itDocumentation->tags) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Tags</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <div class="flex flex-wrap gap-2">
                            @foreach($itDocumentation->tags as $tag)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Compliance Requirements -->
            @if($itDocumentation->compliance_requirements && count($itDocumentation->compliance_requirements) > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Compliance Requirements</h2>
                    </div>
                    <div class="px-6 py-8 sm:px-6">
                        <ul class="space-y-2">
                            @foreach($itDocumentation->compliance_requirements as $requirement)
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-900">{{ $requirement }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Version History -->
            @if($itDocumentation->versions->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Version History</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($itDocumentation->versions->sortByDesc('version') as $version)
                            <div class="px-6 py-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Version {{ $version->version }}</span>
                                        <p class="text-xs text-gray-500">{{ $version->created_at->format('M j, Y') }}</p>
                                    </div>
                                    @if($version->id !== $itDocumentation->id)
                                        <a href="{{ route('clients.it-documentation.show', $version) }}" 
                                           class="text-xs text-blue-600 hover:text-blue-800">View</a>
                                    @else
                                        <span class="text-xs text-green-600 font-medium">Current</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Related Documents -->
            @if($relatedDocuments->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Related Documentation</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($relatedDocuments as $related)
                            <div class="px-6 py-6">
                                <div class="flex items-center">
                                    <span class="text-lg mr-3">{{ $related->category_icon }}</span>
                                    <div class="flex-1">
                                        <a href="{{ route('clients.it-documentation.show', $related) }}" 
                                           class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            {{ $related->name }}
                                        </a>
                                        <p class="text-xs text-gray-500">{{ $related->client->name }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@push('styles')
<style>
    .joint-paper {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
    }
</style>
@endpush

@push('scripts')
@if($itDocumentation->network_diagram && count($itDocumentation->network_diagram) > 0)
@vite(['resources/js/it-documentation-diagram.js'])
<script type="module">
    let diagram = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for the module to load
        if (window.ITDocumentationDiagram) {
            // Initialize the diagram in read-only mode
            diagram = new window.ITDocumentationDiagram('network-diagram-viewer');
            
            // Load the saved diagram data
            const diagramData = @json($itDocumentation->network_diagram);
            if (diagramData) {
                diagram.importDiagram(diagramData);
                
                // Make it read-only
                if (diagram.paper) {
                    diagram.paper.setInteractivity(false);
                }
            }
        }
    });
    
    // Export diagram function
    window.exportDiagram = function() {
        if (diagram) {
            diagram.exportDiagram();
        }
    };
</script>
@endif
@endpush
@endsection
