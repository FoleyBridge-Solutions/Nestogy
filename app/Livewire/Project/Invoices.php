<?php

namespace App\Livewire\Project;

use Livewire\Component;
use App\Domains\Project\Models\Project;

class Invoices extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function render()
    {
        $invoices = \App\Models\Invoice::where('client_id', $this->project->client_id)->get();
        return view('livewire.project.invoices', compact('invoices'));
    }
}
