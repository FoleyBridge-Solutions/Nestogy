<div>
    <flux:card>
        <form wire:submit="save">
            <div class="space-y-6">
                <flux:heading size="lg">Add Credential</flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:select wire:model="client_id" label="Client" placeholder="Select client..." required>
                        @foreach($clients as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="name" label="Credential Name" placeholder="My Database" required />
                </div>

                <flux:textarea wire:model="description" label="Description" placeholder="Description of this credential..." rows="2" />

                @include('livewire.clients.partials.credential-form-fields')

                <flux:separator />
                <flux:heading size="md">Additional Settings</flux:heading>

                @if(in_array($credential_type, ['ssl_certificate', 'software', 'domain']))
                    <flux:input wire:model="expires_at" type="date" label="Expires At" />
                @else
                    <flux:input wire:model="expires_at" type="date" label="Expires At (optional)" />
                @endif

                <div class="flex gap-6">
                    <flux:checkbox wire:model="is_active" label="Active" />
                    <flux:checkbox wire:model="is_shared" label="Shared with team" />
                </div>

                <flux:textarea wire:model="notes" label="Notes" placeholder="Additional notes..." rows="3" />

                <flux:separator />

                <div class="flex gap-3 justify-end">
                    <flux:button variant="ghost" href="{{ route('clients.credentials.index') }}">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Create Credential</flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</div>
