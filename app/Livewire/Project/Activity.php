<?php

namespace App\Livewire\Project;

use Livewire\Component;
use App\Domains\Project\Models\Project;

class Activity extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        $activities = $this->project->comments()->with('user')->latest()->get();
        return view('livewire.project.activity', compact('activities'));
    }
}
