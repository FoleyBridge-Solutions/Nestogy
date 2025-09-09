@props([
    'ticket'
])

<!-- Modern Ticket Merge Modal -->
<div x-data="ticketMerge()" 
     x-show="isOpen" 
     x-transition.opacity.duration.300ms
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     @ticket-merge-open.window="open()"
     @keydown.escape.window="close()">
     
    <!-- Backdrop -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-6 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             @click="close()"></div>
        
        <!-- Modal Container -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal Content -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
             
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="mt-6 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                Merge Ticket #{{ $ticket->number }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Merge "{{ $ticket->subject }}" into another ticket
                            </p>
                        </div>
                    </div>
                    <button @click="close()" class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4 sm:p-6">
                <div class="space-y-6">
                    <!-- Search Section -->
                    <div>
                        <label for="ticket-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Search for target ticket
                        </label>
                        <div class="relative">
                            <input x-ref="searchInput"
                                   x-model="searchQuery"
                                   @input="handleSearchInput()"
                                   type="text"
                                   placeholder="Search by ticket number, subject, or client..."
                                   class="block w-full px-6 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                   :disabled="selectedTicket">
                            
                            <!-- Search Icon -->
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg x-show="!isSearching" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <svg x-show="isSearching" class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            
                            <!-- Clear Selection Button -->
                            <button x-show="selectedTicket" 
                                    @click="clearSelection()"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Search Results -->
                        <div x-show="searchResults.length > 0" 
                             class="mt-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md shadow-lg bg-white dark:bg-gray-700">
                            <template x-for="ticket in searchResults" :key="ticket.id">
                                <div @click="selectTicket(ticket)" 
                                     class="px-6 py-6 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="`#${ticket.number}`"></span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="getStatusBadgeClass(ticket.status)"
                                                      x-text="ticket.status"></span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="getPriorityBadgeClass(ticket.priority)"
                                                      x-text="ticket.priority"></span>
                                            </div>
                                            <div class="mt-1">
                                                <p class="text-sm text-gray-600 dark:text-gray-300" x-text="ticket.subject"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="ticket.client?.name"></p>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="new Date(ticket.created_at).toLocaleDateString()"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- No Results -->
                        <div x-show="searchQuery.length >= 2 && searchResults.length === 0 && !isSearching" 
                             class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center py-6">
                            No tickets found matching your search.
                        </div>
                    </div>
                    
                    <!-- Selected Ticket Preview -->
                    <div x-show="selectedTicket" 
                         class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Merging into:</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200" x-text="selectedTicket ? `#${selectedTicket.number}` : ''"></span>
                                <div class="flex space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="selectedTicket ? getStatusBadgeClass(selectedTicket.status) : ''"
                                          x-text="selectedTicket?.status"></span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="selectedTicket ? getPriorityBadgeClass(selectedTicket.priority) : ''"
                                          x-text="selectedTicket?.priority"></span>
                                </div>
                            </div>
                            <p class="text-sm text-blue-700 dark:text-blue-300" x-text="selectedTicket?.subject"></p>
                            <p class="text-xs text-blue-600 dark:text-blue-400" x-text="selectedTicket?.client?.name"></p>
                        </div>
                    </div>
                    
                    <!-- Merge Comment -->
                    <div>
                        <label for="merge-comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Merge Comment <span class="text-gray-500">(optional)</span>
                        </label>
                        <textarea x-ref="commentInput"
                                  x-model="mergeComment"
                                  rows="3"
                                  placeholder="Explain why these tickets are being merged..."
                                  class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:text-white"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            This comment will be added to both tickets explaining the merge.
                        </p>
                    </div>
                    
                    <!-- Warning -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Important Notice</h3>
                                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>This action cannot be undone</li>
                                        <li>All replies, time entries, and attachments will be moved to the target ticket</li>
                                        <li>This ticket will be closed and marked as merged</li>
                                        <li>Notifications will be sent to all involved parties</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-6 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                <button @click="submitMerge()" 
                        :disabled="!selectedTicket || isLoading"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-6 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isLoading ? 'Merging...' : 'Merge Tickets'"></span>
                </button>
                <button @click="close()" 
                        :disabled="isLoading"
                        class="mt-6 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-6 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <!-- Hidden meta tags for component data -->
    <meta name="ticket-id" content="{{ $ticket->id }}">
    <meta name="ticket-number" content="{{ $ticket->number }}">
    <meta name="ticket-subject" content="{{ $ticket->subject }}">
</div>
