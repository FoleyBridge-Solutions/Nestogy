@props(['breadcrumbs' => []])

@if(!empty($breadcrumbs))
<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        @foreach($breadcrumbs as $index => $breadcrumb)
            @if($loop->first)
                <li class="inline-flex items-center">
                    <a href="{{ route($breadcrumb['route']) }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        {{ $breadcrumb['name'] }}
                    </a>
                </li>
            @else
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        @if(isset($breadcrumb['route']) && !($breadcrumb['active'] ?? false))
                            <a href="{{ route($breadcrumb['route']) }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                                {{ $breadcrumb['name'] }}
                            </a>
                        @else
                            <span class="text-sm font-medium text-gray-500">
                                {{ $breadcrumb['name'] }}
                            </span>
                        @endif
                    </div>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif