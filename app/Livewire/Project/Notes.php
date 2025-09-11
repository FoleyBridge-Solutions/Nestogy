<?php

namespace App\Livewire\Project;

use Livewire\Component;
use App\Domains\Project\Models\Project;

class Notes extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        return view('livewire.project.notes');
    }
}
