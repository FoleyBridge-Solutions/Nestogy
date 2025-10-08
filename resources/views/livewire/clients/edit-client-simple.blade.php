<div>
    <h1>Edit Client Test</h1>
    <p>Client Name: {{ $name }}</p>
    <p>Client Email: {{ $email }}</p>
    
    <form wire:submit="save">
        <div>
            <label for="name">Name:</label>
            <input type="text" id="name" wire:model="name" />
        </div>
        
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" wire:model="email" />
        </div>
        
        <button type="submit">Save</button>
    </form>
</div>