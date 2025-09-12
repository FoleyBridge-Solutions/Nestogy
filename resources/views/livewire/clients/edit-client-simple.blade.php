<div>
    <h1>Edit Client Test</h1>
    <p>Client Name: {{ $name }}</p>
    <p>Client Email: {{ $email }}</p>
    
    <form wire:submit="save">
        <div>
            <label>Name:</label>
            <input type="text" wire:model="name" />
        </div>
        
        <div>
            <label>Email:</label>
            <input type="email" wire:model="email" />
        </div>
        
        <button type="submit">Save</button>
    </form>
</div>