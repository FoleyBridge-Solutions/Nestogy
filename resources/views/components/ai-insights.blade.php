@props(['enabled' => false, 'loading' => false, 'error' => null, 'insights' => []])

@if($enabled)
    <div {{ $attributes->merge(['class' => 'ai-insights-widget bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4']) }}>
        <div class="ai-header flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                </svg>
                AI Insights
            </h3>
            
            @if($loading)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    <svg class="animate-spin -ml-1 mr-2 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Analyzing...
                </span>
            @endif
        </div>
        
        @if($error)
            <div class="alert alert-warning bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 mb-4">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ $error }}</p>
            </div>
        @endif
        
        @if(!empty($insights))
            <div class="ai-content space-y-4">
                @if(isset($insights['summary']) && $insights['summary'])
                    <div class="ai-section">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Summary</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $insights['summary'] }}</p>
                    </div>
                @endif
                
                @if(isset($insights['sentiment']) && $insights['sentiment'])
                    <div class="ai-section">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Sentiment</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $insights['sentiment'] }}</p>
                    </div>
                @endif
                
                @if(isset($insights['category']) && $insights['category'])
                    <div class="ai-section">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Category</h4>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                {{ $insights['category'] }}
                            </span>
                            @if(isset($insights['confidence']))
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $insights['confidence'] }}% confidence
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
                
                @if(isset($insights['priority']) && $insights['priority'])
                    <div class="ai-section">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Suggested Priority</h4>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($insights['priority'] === 'high') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @elseif($insights['priority'] === 'medium') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @endif">
                            {{ ucfirst($insights['priority']) }}
                        </span>
                    </div>
                @endif
                
                @if(isset($insights['suggestions']))
                    @php
                        $suggestions = is_string($insights['suggestions']) 
                            ? json_decode($insights['suggestions'], true) 
                            : $insights['suggestions'];
                    @endphp
                    
                    @if(is_array($suggestions) && count($suggestions) > 0)
                        <div class="ai-section">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Suggestions</h4>
                            <ul class="space-y-2">
                                @foreach($suggestions as $suggestion)
                                    <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ $suggestion }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
                
                @if(isset($insights['analyzed_at']) && $insights['analyzed_at'])
                    <div class="ai-footer pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Last analyzed 
                            @if(is_string($insights['analyzed_at']))
                                {{ \Carbon\Carbon::parse($insights['analyzed_at'])->diffForHumans() }}
                            @else
                                {{ $insights['analyzed_at']->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @elseif(!$loading)
            <div class="ai-empty text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">AI analysis pending...</p>
            </div>
        @endif
        
        {{ $slot }}
    </div>
@endif
