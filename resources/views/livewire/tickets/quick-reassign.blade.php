<div>
    @if($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('show') }">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="show = false"></div>

            <!-- Modal -->
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                <i class="fas fa-exchange-alt mr-2"></i>Quick Reassign Tickets
                            </h2>
                            <button wire:click="close" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        @if(count($tickets) > 0)
                            <!-- Selection Controls -->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ count($selectedTickets) }} of {{ count($tickets) }} selected
                                </div>
                                <div class="flex gap-2">
                                    <flux:button wire:click="selectAll" variant="ghost" size="sm">
                                        Select All
                                    </flux:button>
                                    <flux:button wire:click="clearSelection" variant="ghost" size="sm">
                                        Clear
                                    </flux:button>
                                </div>
                            </div>

                            <!-- Ticket List -->
                            <div class="space-y-2 mb-6">
                                @foreach($tickets as $ticket)
                                    <label class="flex items-center gap-3 p-3 rounded-lg border {{ in_array($ticket['id'], $selectedTickets) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }} cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input 
                                            type="checkbox"
                                            wire:click="toggleTicket({{ $ticket['id'] }})"
                                            {{ in_array($ticket['id'], $selectedTickets) ? 'checked' : '' }}
                                            class="rounded text-blue-600"
                                        >
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900 dark:text-gray-100">#{{ $ticket['number'] }}</span>
                                                <flux:badge class="{{ $ticket['priority'] === 'Critical' ? 'bg-red-100 text-red-800' : ($ticket['priority'] === 'High' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ $ticket['priority'] }}
                                                </flux:badge>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $ticket['subject'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $ticket['client']['name'] ?? 'No client' }} • {{ \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <!-- Reassignment Form -->
                            <div class="space-y-4">
                                <!-- Select New Assignee -->
                                <div>
                                    <flux:label for="newAssignee" required>Assign To</flux:label>
                                    <flux:select wire:model="newAssigneeId" id="newAssignee">
                                        <option value="">Select technician...</option>
                                        @foreach($technicians as $tech)
                                            <option value="{{ $tech['id'] }}">
                                                {{ $tech['name'] }} ({{ $tech['active_count'] }} active tickets)
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    @error('newAssigneeId')
                                        <span class="text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Reason -->
                                <div>
                                    <flux:label for="reason">Reason (Optional)</flux:label>
                                    <flux:textarea 
                                        wire:model="reassignReason"
                                        id="reason"
                                        rows="3"
                                        placeholder="Why are these tickets being reassigned?"
                                    ></flux:textarea>
                                </div>

                                @error('selectedTickets')
                                    <div class="text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <div class="text-center py-12">
                                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600 dark:text-gray-400">No active tickets to reassign</p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                        <flux:button wire:click="close" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button
                            wire:click="reassign"
                            variant="primary"
                            :disabled="count($selectedTickets) === 0 || !$newAssigneeId">
                            <i class="fas fa-check mr-2"></i>
                            Reassign {{ count($selectedTickets) > 0 ? '(' . count($selectedTickets) . ')' : '' }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
