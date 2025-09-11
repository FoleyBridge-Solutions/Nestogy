<?php

namespace App\Livewire;

use Livewire\Component;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectTask;
use App\Domains\Project\Models\ProjectMember;
use App\Domains\Project\Services\ProjectService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectDetail extends Component
{
    public Project $project;
    
    // Modal states
    public $showNewTaskModal = false;
    public $showAddMemberModal = false;
    public $showEditProjectModal = false;
    public $showDeleteConfirmModal = false;
    
    // Form data for new task
    public $taskName = '';
    public $taskDescription = '';
    public $taskAssignee = '';
    public $taskPriority = 'medium';
    public $taskDueDate = '';
    public $taskStatus = 'not_started';
    
    // Form data for adding member
    public $selectedUserId = '';
    public $memberRole = 'member';
    
    // Active tab
    public $activeTab = 'overview';
    
    // Data from service
    public $dashboard;
    public $health;
    public $risks;
    public $activity;
    
    protected $rules = [
        'taskName' => 'required|min:3',
        'taskDescription' => 'nullable',
        'taskAssignee' => 'nullable|exists:users,id',
        'taskPriority' => 'required|in:low,medium,high,critical',
        'taskDueDate' => 'nullable|date',
        'taskStatus' => 'required|in:todo,in_progress,completed,blocked',
        'selectedUserId' => 'required|exists:users,id',
        'memberRole' => 'required|string',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->project->load(['client', 'manager', 'members.user', 'tasks', 'milestones', 'timeEntries', 'expenses']);
        $this->loadDashboard();
    }
    
    protected function loadDashboard()
    {
        $service = app(ProjectService::class);
        $this->dashboard = $service->getProjectDashboard($this->project);
    }
    
    public function loadProjectData()
    {
        $service = app(ProjectService::class);
        $this->dashboard = $service->getProjectDashboard($this->project);
        $this->health = $this->dashboard['health'];
        $this->risks = $this->dashboard['risks'];
        $this->activity = $this->dashboard['activity'];
    }

    public function createTask()
    {
        $this->validate([
            'taskName' => 'required|string|max:255',
            'taskDescription' => 'nullable|string',
            'taskAssignee' => 'nullable|exists:users,id',
            'taskPriority' => 'required|in:low,medium,high,urgent',
            'taskStatus' => 'required|in:not_started,in_progress,completed,on_hold,cancelled',
            'taskDueDate' => 'nullable|date',
        ]);

        $assignedTo = $this->taskAssignee ?: null;
$dueDate = $this->taskDueDate ?: null;
ProjectTask::create([
    'project_id' => $this->project->id,
    'name' => $this->taskName,
    'description' => $this->taskDescription,
    'assigned_to' => $assignedTo,
    'priority' => $this->taskPriority,
    'status' => $this->taskStatus,
    'due_date' => $dueDate,
]);

        $this->resetTaskForm();
        $this->showNewTaskModal = false; // Close the modal
        $this->dispatch('notify', type: 'success', message: 'Task created successfully');
    }
    
    public function addTeamMember()
    {
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'memberRole' => 'required|in:member,developer,lead,observer',
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->selectedUserId,
            'role' => $this->memberRole,
            'joined_at' => now(),
        ]);

        $this->selectedUserId = '';
        $this->memberRole = 'member';
        $this->showAddMemberModal = false; // Close the modal
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Team member added successfully');
    }
    
    public function updateTaskStatus($taskId, $status)
    {
        $task = ProjectTask::findOrFail($taskId);
        $task->status = $status;
        $task->save();
        
        $this->project->refresh();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Task status updated']);
    }
    
    public function deleteTask($taskId)
    {
        $task = ProjectTask::findOrFail($taskId);
        $task->delete();
        
        $this->project->refresh();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Task deleted']);
    }
    
    public function removeMember($memberId)
    {
        $member = ProjectMember::findOrFail($memberId);
        $member->left_at = now();
        $member->save();
        
        $this->project->refresh();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Team member removed']);
    }
    
    public function archiveProject()
    {
        $this->project->archived_at = now();
        $this->project->save();
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Project archived']);
        return redirect()->route('projects.index');
    }
    
    public function deleteProject()
    {
        $this->project->delete();
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Project deleted']);
        return redirect()->route('projects.index');
    }
    
    public function resetTaskForm()
    {
        $this->taskName = '';
        $this->taskDescription = '';
        $this->taskAssignee = '';
        $this->taskPriority = 'medium';
        $this->taskDueDate = '';
        $this->taskStatus = 'todo';
    }
    
    public function resetMemberForm()
    {
        $this->selectedUserId = '';
        $this->memberRole = 'member';
    }

    public function render()
    {
        $availableUsers = User::where('company_id', auth()->user()->company_id)
            ->whereNotIn('id', $this->project->members->pluck('user_id'))
            ->orderBy('name')
            ->get();
            
        $teamMembers = User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('livewire.project-detail', [
            'availableUsers' => $availableUsers,
            'teamMembers' => $teamMembers,
        ]);
    }
}