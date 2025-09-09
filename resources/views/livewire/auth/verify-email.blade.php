<div>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
    </div>

    @if ($status)
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ $status }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <flux:button wire:click="sendVerification" variant="primary">Resend Verification Email</flux:button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost">Log Out</flux:button>
        </form>
    </div>
</div>
