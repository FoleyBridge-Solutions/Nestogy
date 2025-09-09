<div>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        This is a secure area of the application. Please confirm your password before continuing.
    </div>

    <form wire:submit="confirmPassword" class="space-y-6">
        @csrf
        <flux:input 
            wire:model="password" 
            type="password" 
            label="Password"
            placeholder="Enter your password"
            required 
            autofocus
            viewable
        />

        <div>
            <flux:button type="submit" variant="primary" class="w-full">Confirm</flux:button>
        </div>
    </form>
</div>
