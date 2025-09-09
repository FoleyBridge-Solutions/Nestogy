<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

class CollectionMetrics extends Component
{
    public array $data = [];
    public bool $loading = true;
    
    public function mount()
    {
        $this->loadData();
    }
    
    #[On('refresh-collectionmetrics')]
    public function loadData()
    {
        $this->loading = true;
        
        // Mock data for now
        $this->data = [
            'items' => [],
            'stats' => []
        ];
        
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.collection-metrics');
    }
}
