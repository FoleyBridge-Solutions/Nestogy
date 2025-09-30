<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Attributes\On;
use Livewire\Component;

class RecentSolutions extends Component
{
    public array $data = [];

    public bool $loading = true;

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-recentsolutions')]
    public function loadData()
    {
        $this->loading = true;

        // Mock data for now
        $this->data = [
            'items' => [],
            'stats' => [],
        ];

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.recent-solutions');
    }
}
