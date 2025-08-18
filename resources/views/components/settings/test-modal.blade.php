@props([
    'show' => 'showTestModal',
    'results' => 'testResults'
])

<div x-show="{{ $show }}" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="{{ $show }} = false"></div>

        <div x-show="{{ $show }}"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg x-show="{{ $results }} && {{ $results }}.status === 'testing'" 
                             class="animate-spin h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg x-show="{{ $results }} && {{ $results }}.status === 'success'" 
                             class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg x-show="{{ $results }} && {{ $results }}.status === 'error'" 
                             class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            <span x-show="{{ $results }} && {{ $results }}.status === 'testing'">Testing Connection...</span>
                            <span x-show="{{ $results }} && {{ $results }}.status === 'success'">Connection Successful</span>
                            <span x-show="{{ $results }} && {{ $results }}.status === 'error'">Connection Failed</span>
                        </h3>
                        <div class="mt-2">
                            <p x-show="{{ $results }} && {{ $results }}.message" 
                               class="text-sm text-gray-500" 
                               x-text="{{ $results }} && {{ $results }}.message"></p>
                            <div x-show="{{ $results }} && {{ $results }}.details" class="mt-3 text-sm text-gray-600">
                                <dl class="space-y-1">
                                    <div x-show="{{ $results }} && {{ $results }}.details && {{ $results }}.details.latency" 
                                         class="flex justify-between">
                                        <dt>Latency:</dt>
                                        <dd x-text="{{ $results }} && {{ $results }}.details && {{ $results }}.details.latency"></dd>
                                    </div>
                                    <div x-show="{{ $results }} && {{ $results }}.details && {{ $results }}.details.version" 
                                         class="flex justify-between">
                                        <dt>API Version:</dt>
                                        <dd x-text="{{ $results }} && {{ $results }}.details && {{ $results }}.details.version"></dd>
                                    </div>
                                    <div x-show="{{ $results }} && {{ $results }}.details && {{ $results }}.details.lastSync" 
                                         class="flex justify-between">
                                        <dt>Last Sync:</dt>
                                        <dd x-text="{{ $results }} && {{ $results }}.details && {{ $results }}.details.lastSync"></dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                <button type="button" 
                        @click="{{ $show }} = false; {{ $results }} = null"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>