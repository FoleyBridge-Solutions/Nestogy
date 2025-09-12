<div class="space-y-4">
    <!-- Debug Info -->
    <div class="p-4 bg-gray-100 rounded">
        <h3 class="font-bold mb-2">Debug Info:</h3>
        <p>Client ID: {{ $client->id }}</p>
        <p>Client Name from DB: {{ $client->name }}</p>
        <p>Name Property: {{ $name }}</p>
        <p>Email Property: {{ $email }}</p>
        <p>Type Property: {{ $type }}</p>
    </div>

    <!-- Simple Test Form -->
    <div class="p-4 border rounded">
        <h3 class="font-bold mb-4">Test Form</h3>
        <form wire:submit="save">
            <div class="space-y-4">
                <!-- Regular input to test -->
                <div>
                    <label class="block mb-1">Name (regular input)</label>
                    <input type="text" wire:model="name" class="w-full border rounded px-3 py-2" />
                    <span class="text-sm text-gray-500">Current value: {{ $name }}</span>
                </div>

                <!-- Flux input to test -->
                <flux:field>
                    <flux:label>Name (flux input)</flux:label>
                    <flux:input wire:model="name" />
                </flux:field>

                <!-- Email -->
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" />
                </flux:field>

                <!-- Submit -->
                <flux:button type="submit" variant="primary">Save Test</flux:button>
            </div>
        </form>
    </div>
</div>