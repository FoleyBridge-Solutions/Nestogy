<?php

namespace App\Livewire\Projects;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectTask;
use App\Domains\Project\Models\ProjectNote;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class ProjectShow extends Component
{
    use WithFileUploads;

    public Project $project;
    public $activeTab = 'summary';
    
    // Task management
    public $showTaskModal = false;
    public $taskName = '';
    public $taskDescription = '';
    public $taskDueDate = '';
    public $taskAssignedTo = '';
    public $taskPriority = 'medium';
    
    // Note management
    public $noteContent = '';
    public $noteIsPrivate = false;
    
    // File uploads
    public $files = [];

    protected $listeners = ['refreshProject' => '$refresh'];

    protected $rules = [
        'taskName' => 'required|min:3',
        'taskDescription' => 'nullable|string',
        'taskDueDate' => 'nullable|date',
        'taskAssignedTo' => 'nullable|exists:users,id',
        'taskPriority' => 'required|in:low,medium,high,urgent',
        'noteContent' => 'required|min:3',
        'noteIsPrivate' => 'boolean',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        
        // Load relationships
        $this->project->load([
            'client',
            'manager',
            'teamMembers',
            'tasks' => function($query) {
                $query->orderBy('due_date')->orderBy('priority', 'desc');
            },
            'notes' => function($query) {
                $query->latest();
            },
            'tickets',
            'invoices',
            'files',
            'activities.user'
        ]);
    }

    public function createTask()
    {
        $this->validate([
            'taskName' => 'required|min:3',
            'taskDescription' => 'nullable|string',
            'taskDueDate' => 'nullable|date',
            'taskAssignedTo' => 'nullable|exists:users,id',
            'taskPriority' => 'required|in:low,medium,high,urgent',
        ]);

        $this->project->tasks()->create([
            'name' => $this->taskName,
            'description' => $this->taskDescription,
            'due_date' => $this->taskDueDate,
            'assigned_to' => $this->taskAssignedTo,
            'priority' => $this->taskPriority,
            'status' => 'pending',
            'created_by' => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ]);

        $this->reset(['taskName', 'taskDescription', 'taskDueDate', 'taskAssignedTo', 'taskPriority', 'showTaskModal']);
        $this->project->load('tasks');
        
        session()->flash('message', 'Task created successfully.');
    }

    public function toggleTaskStatus($taskId)
    {
        $task = ProjectTask::where('id', $taskId)
            ->where('project_id', $this->project->id)
            ->first();
            
        if ($task) {
            $task->status = $task->status === 'completed' ? 'pending' : 'completed';
            $task->completed_at = $task->status === 'completed' ? now() : null;
            $task->completed_by = $task->status === 'completed' ? Auth::id() : null;
            $task->save();
            
            $this->project->load('tasks');
            $this->updateProjectProgress();
        }
    }

    public function deleteTask($taskId)
    {
        ProjectTask::where('id', $taskId)
            ->where('project_id', $this->project->id)
            ->delete();
            
        $this->project->load('tasks');
        $this->updateProjectProgress();
        
        session()->flash('message', 'Task deleted successfully.');
    }

    public function addNote()
    {
        $this->validate([
            'noteContent' => 'required|min:3',
            'noteIsPrivate' => 'boolean',
        ]);

        $this->project->notes()->create([
            'content' => $this->noteContent,
            'is_private' => $this->noteIsPrivate,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ]);

        $this->reset(['noteContent', 'noteIsPrivate']);
        $this->project->load('notes');
        
        session()->flash('message', 'Note added successfully.');
    }

    public function deleteNote($noteId)
    {
        ProjectNote::where('id', $noteId)
            ->where('project_id', $this->project->id)
            ->where('user_id', Auth::id())
            ->delete();
            
        $this->project->load('notes');
        
        session()->flash('message', 'Note deleted successfully.');
    }

    public function uploadFiles()
    {
        $this->validate([
            'files.*' => 'file|max:10240', // 10MB max
        ]);

        foreach ($this->files as $file) {
            $path = $file->store('project-files', 'public');
            $this->project->files()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => Auth::id(),
                'company_id' => Auth::user()->company_id,
            ]);
        }

        $this->reset('files');
        $this->project->load('files');
        
        session()->flash('message', 'Files uploaded successfully.');
    }

    public function updateProjectStatus($status)
    {
        $this->project->status = $status;
        $this->project->save();
        
        // Log activity
        $this->project->activities()->create([
            'type' => 'status_changed',
            'description' => "Status changed to {$status}",
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ]);
        
        session()->flash('message', 'Project status updated successfully.');
    }

    public function updateProjectPriority($priority)
    {
        $this->project->priority = $priority;
        $this->project->save();
        
        session()->flash('message', 'Project priority updated successfully.');
    }

    private function updateProjectProgress()
    {
        $totalTasks = $this->project->tasks()->count();
        $completedTasks = $this->project->tasks()->where('status', 'completed')->count();
        
        if ($totalTasks > 0) {
            $this->project->progress = round(($completedTasks / $totalTasks) * 100);
            $this->project->save();
        }
    }

    public function render()
    {
        $technicians = \App\Models\User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();
            
        return view('livewire.projects.project-show', [
            'technicians' => $technicians,
            'statuses' => ['planning', 'active', 'in_progress', 'on_hold', 'completed', 'cancelled'],
            'priorities' => ['low', 'medium', 'high', 'urgent'],
        ]);
    }
}