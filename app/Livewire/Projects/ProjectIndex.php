<?php

namespace App\Livewire\Projects;

use App\Domains\Project\Models\Project;
use App\Models\Client;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ProjectIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $clientId = '';
    public $managerId = '';
    public $priority = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;
    
    public $selectedProjects = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'clientId' => ['except' => ''],
        'managerId' => ['except' => ''],
        'priority' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 25]
    ];

    public function mount()
    {
        // Get client from session if available
        $selectedClient = app(\App\Domains\Core\Services\NavigationService::class)->getSelectedClient();
        if ($selectedClient) {
            // Extract the ID if it's an object, otherwise use the value directly
            $this->clientId = is_object($selectedClient) ? $selectedClient->id : $selectedClient;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedProjects = $this->getProjects()->pluck('id')->toArray();
        } else {
            $this->selectedProjects = [];
        }
    }

    public function bulkUpdateStatus($status)
    {
        $count = count($this->selectedProjects);
        
        Project::whereIn('id', $this->selectedProjects)
            ->where('company_id', Auth::user()->company_id)
            ->update(['status' => $status]);

        $this->selectedProjects = [];
        $this->selectAll = false;
        
        session()->flash('message', "$count projects have been updated to $status status.");
    }

    public function archiveProject($projectId)
    {
        $project = Project::where('id', $projectId)
            ->where('company_id', Auth::user()->company_id)
            ->first();
            
        if ($project) {
            $project->update(['archived_at' => now()]);
            session()->flash('message', "Project '{$project->name}' has been archived.");
        }
    }

    public function getProjects()
    {
        return Project::query()
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('project_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->priority, function ($query) {
                $query->where('priority', $this->priority);
            })
            ->when($this->clientId, function ($query) {
                $query->where('client_id', $this->clientId);
            })
            ->when($this->managerId, function ($query) {
                $query->where('manager_id', $this->managerId);
            })
            ->with(['client', 'manager', 'teamMembers'])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        $projects = $this->getProjects();
        
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
            
        $users = User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('livewire.projects.project-index', [
            'projects' => $projects,
            'clients' => $clients,
            'users' => $users,
            'statuses' => ['planning', 'active', 'in_progress', 'on_hold', 'completed', 'cancelled'],
            'priorities' => ['low', 'medium', 'high', 'urgent']
        ]);
    }
}