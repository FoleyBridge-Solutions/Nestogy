<div class="space-y-6">
    {{-- Page Header --}}
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <div>
                <flux:heading size="lg">Contacts</flux:heading>
                <flux:subheading>
                    Manage contacts for this client
                </flux:subheading>
            </div>
            <flux:button variant="primary" href="{{ route('clients.contacts.create') }}">
                <flux:icon.plus class="size-4" />
                Add Contact
            </flux:button>
        </div>
    </flux:card>

    {{-- Use base index view --}}
    @include('livewire.base-index', [
        'stats' => $this->getStats(),
    ])
</div>
