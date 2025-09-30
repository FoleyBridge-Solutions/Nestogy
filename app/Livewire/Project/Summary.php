<?php

namespace App\Livewire\Project;

use App\Domains\Project\Models\Project;
use Livewire\Component;

class Summary extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project.summary');
    }
}
