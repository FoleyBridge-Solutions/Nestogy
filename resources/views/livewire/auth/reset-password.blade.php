<div>
    <form wire:submit="resetPassword" class="space-y-6">
        @csrf
        <input type="hidden" wire:model="token">

        <flux:input 
            wire:model="email" 
            type="email" 
            label="Email Address"
            placeholder="Enter your email address"
            required 
            autofocus 
        />

        <flux:input 
            wire:model="password" 
            type="password" 
            label="Password"
            placeholder="Enter your new password"
            required 
            viewable
        />

        <flux:input 
            wire:model="password_confirmation" 
            type="password" 
            label="Confirm Password"
            placeholder="Confirm your new password"
            required 
            viewable
        />

        <div>
            <flux:button type="submit" variant="primary" class="w-full">Reset Password</flux:button>
        </div>
    </form>
</div>
