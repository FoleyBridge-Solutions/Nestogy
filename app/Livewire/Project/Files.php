<?php

namespace App\Livewire\Project;

use App\Domains\Project\Models\Project;
use Livewire\Component;

class Files extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        $files = $this->project->files;

        return view('livewire.project.files', compact('files'));
    }
}
