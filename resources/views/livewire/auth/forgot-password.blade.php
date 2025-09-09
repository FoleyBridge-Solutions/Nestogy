<div>
    @if ($status)
        <flux:callout variant="success">
            {{ $status }}
        </flux:callout>
    @else
        <form wire:submit="sendResetLink" class="space-y-6">
            @csrf
            <flux:input 
                wire:model="email" 
                type="email" 
                label="Email Address"
                placeholder="Enter your email address"
                required 
                autofocus 
            />

            <div>
                <flux:button type="submit" variant="primary" class="w-full">Email Password Reset Link</flux:button>
            </div>
        </form>
    @endif

    <div class="mt-6 text-center">
        <flux:link href="{{ route('login') }}">Back to Login</flux:link>
    </div>
</div>
