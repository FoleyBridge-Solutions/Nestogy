<div>
    @if($submitted)
        <flux:card class="border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-green-900 dark:text-green-100">Thank you for your feedback!</h3>
                    <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                        We appreciate you taking the time to rate this ticket. Your feedback helps us improve our service.
                    </p>
                    
                    <div class="mt-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm font-medium text-green-900 dark:text-green-100">Your Rating:</span>
                            <div class="flex gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= $rating ? 'text-yellow-400' : 'text-zinc-300 dark:text-zinc-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        
                        @if($feedback)
                            <div class="text-sm text-green-700 dark:text-green-300 italic">
                                "{{ $feedback }}"
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>
    @else
        <flux:card>
            <form wire:submit="submitRating">
                <flux:heading size="lg">How was your experience?</flux:heading>
                <flux:subheading class="mb-4">
                    Please rate your experience with ticket #{{ $ticket->number }}
                </flux:subheading>

                <div class="space-y-6">
                    <div>
                        <flux:label class="mb-2">Rating *</flux:label>
                        <div class="flex gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                <button
                                    type="button"
                                    wire:click="setRating({{ $i }})"
                                    class="group transition-transform hover:scale-110"
                                >
                                    <svg class="w-10 h-10 transition-colors {{ $rating >= $i ? 'text-yellow-400' : 'text-zinc-300 dark:text-zinc-600 group-hover:text-yellow-200' }}" 
                                         fill="currentColor" 
                                         viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </button>
                            @endfor
                        </div>
                        <div class="flex justify-between mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            <span>Poor</span>
                            <span>Excellent</span>
                        </div>
                        @error('rating')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <flux:textarea 
                            wire:model="feedback" 
                            label="Additional Feedback (Optional)"
                            placeholder="Tell us more about your experience..."
                            rows="4"
                        />
                        @error('feedback')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button type="submit" variant="primary">
                            Submit Feedback
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:card>
    @endif
</div>
