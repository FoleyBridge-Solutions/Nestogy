<div>
    @if($showModal)
        <flux:modal wire:model="showModal" title="Stop All Timers" class="max-w-4xl">
            <form wire:submit.prevent="confirmStopAll">
                <div class="space-y-4">
                    {{-- Timer Summary --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Stopping {{ $totalCount }} active {{ Str::plural('timer', $totalCount) }}
                        </div>

                        {{-- Timer List --}}
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($timerDetails as $timer)
                                <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                                    <div>
                                        <div class="font-medium text-sm">
                                            #{{ $timer['ticket_number'] }} - {{ Str::limit($timer['ticket_subject'], 40) }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-mono text-sm font-medium">
                                            {{ $timer['elapsed_display'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $timer['elapsed_hours'] }} hours
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Apply to All Toggle --}}
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="flex-1">
                            <label for="applyToAll" class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Use Same Settings for All Timers
                            </label>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                Apply the same description, work type, and billable status to all timers
                            </p>
                        </div>
                        <flux:checkbox wire:model.live="applyToAll" id="applyToAll" />
                    </div>

                    @if($applyToAll)
                        {{-- Batch Settings --}}
                        <div class="space-y-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <flux:textarea
                                wire:model="batchDescription"
                                label="Work Description (for all timers)"
                                placeholder="Describe the work completed..."
                                rows="3"
                                required
                                :error="$errors->first('batchDescription')"
                            />

                            <div class="grid grid-cols-2 gap-4">
                                <flux:select
                                    wire:model="batchWorkType"
                                    label="Work Type"
                                    required
                                    :error="$errors->first('batchWorkType')"
                                >
                                    @foreach($workTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </flux:select>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-2">
                                        <flux:checkbox wire:model="batchIsBillable" />
                                        <span class="text-sm">Billable Work</span>
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <flux:checkbox wire:model="batchAddComment" />
                                        <span class="text-sm">Add to Ticket Activity</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Individual Settings --}}
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach($timerDetails as $index => $timer)
                                <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="font-medium text-sm mb-2">
                                        #{{ $timer['ticket_number'] }} ({{ $timer['elapsed_display'] }})
                                    </div>

                                    <div class="space-y-3">
                                        <flux:input
                                            wire:model="individualSettings.{{ $timer['id'] }}.description"
                                            placeholder="Work description..."
                                            required
                                            :error="$errors->first('individualSettings.' . $timer['id'] . '.description')"
                                        />

                                        <div class="grid grid-cols-2 gap-3">
                                            <flux:select
                                                wire:model="individualSettings.{{ $timer['id'] }}.work_type"
                                                required
                                            >
                                                @foreach($workTypes as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </flux:select>

                                            <div class="space-y-2">
                                                <label class="flex items-center gap-1 text-xs">
                                                    <flux:checkbox wire:model="individualSettings.{{ $timer['id'] }}.is_billable" />
                                                    <span>Billable</span>
                                                </label>
                                                <label class="flex items-center gap-1 text-xs">
                                                    <flux:checkbox wire:model="individualSettings.{{ $timer['id'] }}.add_comment" />
                                                    <span>Add Comment</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Processing Indicator --}}
                    @if($isProcessing)
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="flex items-center gap-3">
                                <flux:icon.arrow-path class="size-5 text-blue-600 dark:text-blue-400 animate-spin" />
                                <div>
                                    <div class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                        Processing timers...
                                    </div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300">
                                        {{ $processedCount }} of {{ $totalCount }} completed
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <flux:button
                            type="button"
                            wire:click="cancelBatchStop"
                            variant="ghost"
                            :disabled="$isProcessing"
                        >
                            Cancel
                        </flux:button>

                        <flux:button
                            type="submit"
                            variant="primary"
                            :disabled="$isProcessing"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="confirmStopAll">
                                <flux:icon.stop class="size-4" />
                                Stop All Timers
                            </span>
                            <span wire:loading wire:target="confirmStopAll">
                                Processing...
                            </span>
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:modal>
    @endif
</div>