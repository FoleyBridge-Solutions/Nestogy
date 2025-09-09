<div>
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-medium">Notes</h3>
        <div class="flex items-center space-x-2 text-xs">
            @if($saving)
                <span class="text-gray-500">Saving...</span>
            @endif
            @if($saved)
                <span class="text-green-600" wire:poll.5s="$set('saved', false)">Saved</span>
            @endif
        </div>
    </div>
    <flux:textarea 
        wire:model.lazy="notes"
        placeholder="Add notes about this client..."
        rows="8"
        class="w-full"
    />
</div>