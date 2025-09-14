<div>
    @if($showModal)
        <flux:modal wire:model="showModal" title="Complete Timer Entry" class="max-w-2xl">
            <form wire:submit.prevent="confirmStopTimer">
                <div class="space-y-4">
                    {{-- Timer Summary --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Ticket</div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    #{{ $ticketNumber }} - {{ Str::limit($ticketSubject, 50) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Time Worked</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $elapsedDisplay }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $elapsedHours }} hours
                                </div>
                            </div>
                        </div>

                        {{-- Rate Information --}}
                        @if(!empty($rateInfo))
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <flux:badge
                                        :color="$rateInfo['visual_indicator']['color'] ?? 'gray'"
                                        size="sm"
                                    >
                                        {{ $rateInfo['visual_indicator']['badge'] ?? 'Standard' }}
                                    </flux:badge>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $rateInfo['description'] ?? '' }}
                                    </span>
                                    @if($rateInfo['is_premium'] ?? false)
                                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                            {{ $rateInfo['multiplier'] ?? 1 }}x rate
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Template Suggestions --}}
                    @if(!empty($suggestedTemplates))
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Quick Templates
                            </label>
                            <div class="space-y-2">
                                @foreach($suggestedTemplates as $template)
                                    <button
                                        type="button"
                                        wire:click="selectTemplate({{ $template['id'] }})"
                                        class="w-full text-left p-3 rounded-lg border transition-colors
                                            {{ $selectedTemplateId == $template['id']
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $template['name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $template['description'] }}
                                                </div>
                                            </div>
                                            @if($template['confidence'] > 0)
                                                <flux:badge size="xs" variant="subtle">
                                                    {{ $template['confidence'] }}% match
                                                </flux:badge>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Work Description --}}
                    <div>
                        <flux:textarea
                            wire:model="workDescription"
                            label="What did you work on?"
                            placeholder="Describe the work completed..."
                            rows="4"
                            required
                            :error="$errors->first('workDescription')"
                        />
                    </div>

                    {{-- Work Type --}}
                    <div>
                        <flux:select
                            wire:model="workType"
                            label="Work Type"
                            required
                            :error="$errors->first('workType')"
                        >
                            @foreach($workTypes as $value => $label)
                                <option value="{{ $value }}"
                                    @if($value === $suggestedWorkType && !$selectedTemplateId)
                                        class="font-semibold"
                                    @endif
                                >
                                    {{ $label }}
                                    @if($value === $suggestedWorkType && !$selectedTemplateId)
                                        (Suggested)
                                    @endif
                                </option>
                            @endforeach
                        </flux:select>
                    </div>

                    {{-- Billable Checkbox --}}
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex-1">
                            <label for="billable" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Billable Work
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Mark this time entry as billable to the client
                            </p>
                        </div>
                        <flux:checkbox wire:model="isBillable" id="billable" />
                    </div>

                    {{-- Add Comment Checkbox --}}
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex-1">
                            <label for="addComment" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Add to Ticket Activity
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Log this time entry as a comment in the ticket timeline
                            </p>
                        </div>
                        <flux:checkbox wire:model="addCommentToTicket" id="addComment" />
                    </div>

                    {{-- Validation Warnings --}}
                    @if($elapsedHours > 8)
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                            <div class="font-medium text-amber-800 dark:text-amber-200 mb-1">Long Timer Duration</div>
                            <div class="text-sm text-amber-700 dark:text-amber-300">
                                This timer has been running for over 8 hours. Please verify the time is accurate.
                            </div>
                        </div>
                    @endif

                    @if($elapsedMinutes < 1)
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                            <div class="font-medium text-blue-800 dark:text-blue-200 mb-1">Very Short Duration</div>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                Timer ran for less than 1 minute. Minimum billing rules may apply.
                            </div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <flux:button
                            type="button"
                            wire:click="discardTimer"
                            variant="ghost"
                            size="sm"
                            class="text-red-600 hover:text-red-700"
                            wire:loading.attr="disabled"
                        >
                            <flux:icon.trash class="size-4" />
                            <span wire:loading.remove wire:target="discardTimer">Discard Timer</span>
                            <span wire:loading wire:target="discardTimer">Processing...</span>
                        </flux:button>

                        <div class="flex gap-2">
                            <flux:button
                                type="button"
                                wire:click="cancelTimer"
                                variant="ghost"
                            >
                                Keep Running
                            </flux:button>

                            <flux:button
                                type="submit"
                                variant="primary"
                            >
                                <flux:icon.check class="size-4" />
                                Complete Timer
                            </flux:button>
                        </div>
                    </div>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Discard Confirmation Modal --}}
    @if($showDiscardConfirmation)
        <flux:modal wire:model="showDiscardConfirmation" title="Discard Timer?" class="max-w-md">
            <div class="space-y-4">
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
                    <div class="flex items-start gap-3">
                        <flux:icon.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400 mt-0.5" />
                        <div>
                            <div class="font-medium text-amber-800 dark:text-amber-200">
                                Are you sure you want to discard this timer?
                            </div>
                            <div class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                The time tracked ({{ $elapsedDisplay }}) will be permanently deleted and cannot be recovered.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button
                        wire:click="cancelDiscard"
                        variant="ghost"
                        wire:loading.attr="disabled"
                    >
                        Cancel
                    </flux:button>

                    <flux:button
                        wire:click="confirmDiscardTimer"
                        variant="danger"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="confirmDiscardTimer">
                            <flux:icon.trash class="size-4" />
                            Yes, Discard Timer
                        </span>
                        <span wire:loading wire:target="confirmDiscardTimer">
                            Discarding...
                        </span>
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>