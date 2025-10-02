<div>
    @if($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('show') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" 
                    @click="$wire.closeModal()">
                </div>

                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                    
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                <i class="fas fa-exchange-alt mr-2 text-blue-600"></i>
                                Reassign Ticket
                            </h3>
                            @if($ticket)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    #{{ $ticket->number }} - {{ $ticket->subject }}
                                </p>
                            @endif
                        </div>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>

                    @if($ticket)
                        <form wire:submit="reassign" class="space-y-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">Currently Assigned To:</p>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $ticket->assignee?->name ?? 'Unassigned' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">Priority:</p>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            @if($ticket->priority === 'Critical') bg-red-100 text-red-800
                                            @elseif($ticket->priority === 'High') bg-orange-100 text-orange-800
                                            @elseif($ticket->priority === 'Medium') bg-yellow-100 text-yellow-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ $ticket->priority }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <flux:field>
                                <flux:label for="technician" required>Select Technician</flux:label>
                                <flux:select wire:model="selectedTechnicianId" id="technician" required>
                                    <option value="">-- Select Technician --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech['id'] }}" {{ $tech['is_current'] ? 'disabled' : '' }}>
                                            {{ $tech['name'] }} 
                                            ({{ $tech['active_tickets'] }} active, {{ $tech['overdue_tickets'] }} overdue)
                                            @if($tech['is_current']) - Current @endif
                                        </option>
                                    @endforeach
                                </flux:select>
                                <flux:error for="selectedTechnicianId" />
                            </flux:field>

                            @if($selectedTechnicianId)
                                @php
                                    $selectedTech = collect($technicians)->firstWhere('id', (int)$selectedTechnicianId);
                                @endphp
                                
                                @if($selectedTech)
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            {{ $selectedTech['name'] }}'s Current Workload
                                        </p>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div class="text-center p-2 bg-white dark:bg-gray-800 rounded">
                                                <p class="text-xl font-bold text-blue-600">{{ $selectedTech['active_tickets'] }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Active</p>
                                            </div>
                                            <div class="text-center p-2 bg-white dark:bg-gray-800 rounded">
                                                <p class="text-xl font-bold text-red-600">{{ $selectedTech['overdue_tickets'] }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Overdue</p>
                                            </div>
                                            <div class="text-center p-2 bg-white dark:bg-gray-800 rounded">
                                                <p class="text-xl font-bold text-purple-600">{{ $selectedTech['workload_score'] }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Workload</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            <flux:field>
                                <flux:label for="reason">Reason for Reassignment</flux:label>
                                <flux:textarea 
                                    wire:model="reason" 
                                    id="reason"
                                    rows="3"
                                    placeholder="Optional: Explain why this ticket is being reassigned...">
                                </flux:textarea>
                                <flux:description>This will be recorded in the ticket history</flux:description>
                                <flux:error for="reason" />
                            </flux:field>

                            <flux:checkbox 
                                wire:model="notify" 
                                label="Notify the new technician via email"
                                description="Send an email notification to the newly assigned technician" />

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <flux:button type="button" variant="ghost" wire:click="closeModal">
                                    Cancel
                                </flux:button>
                                <flux:button type="submit" variant="primary">
                                    <i class="fas fa-exchange-alt mr-2"></i>
                                    Reassign Ticket
                                </flux:button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('success', (event) => {
                Flux.toast({
                    text: event.message,
                    variant: 'success',
                    duration: 3000
                });
            });

            Livewire.on('error', (event) => {
                Flux.toast({
                    text: event.message,
                    variant: 'danger',
                    duration: 3000
                });
            });
        });
    </script>
</div>
