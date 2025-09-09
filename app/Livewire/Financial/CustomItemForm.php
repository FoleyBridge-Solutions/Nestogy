<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use Flux\Flux;

class CustomItemForm extends Component
{
    // This component is now just a placeholder for the Alpine modal
    // All state is managed in Alpine.js to prevent Livewire reactivity issues
    
    public function render()
    {
        return view('livewire.financial.custom-item-form');
    }
}