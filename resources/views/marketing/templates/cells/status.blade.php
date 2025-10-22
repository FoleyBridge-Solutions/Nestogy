@props(['item'])

<flux:badge 
    size="sm" 
    :color="$item->is_active ? 'green' : 'red'" 
    inset="top bottom"
>
    {{ $item->is_active ? 'Active' : 'Inactive' }}
</flux:badge>
