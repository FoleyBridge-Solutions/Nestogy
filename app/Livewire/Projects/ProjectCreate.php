<?php

namespace App\Livewire\Projects;

use App\Domains\Client\Models\Client;
use App\Domains\Core\Models\User;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProjectCreate extends Component
{
    public $client_id;
    public $name = '';
    public $description = '';
    public $prefix = '';
    
    public $manager_id;
    public $start_date;
    public $due_date;
    public $priority = 'medium';
    public $status = 'pending';
    public $budget;
    
    public $template_id;
    public $apply_template_tasks = true;
    public $apply_template_milestones = true;
    public $apply_template_roles = true;
    
    public $currentStep = 1;
    public $showTemplatePreview = false;
    
    public function mount($selectedClientId = null, $selectedTemplateId = null)
    {
        if ($selectedClientId) {
            $this->client_id = $selectedClientId;
        } elseif (session('selected_client_id')) {
            $this->client_id = session('selected_client_id');
        }
        
        if ($selectedTemplateId) {
            $this->template_id = $selectedTemplateId;
            $this->applyTemplate();
        }
        
        if (!$this->manager_id) {
            $this->manager_id = Auth::id();
        }
        
        if (!$this->start_date) {
            $this->start_date = now()->format('Y-m-d');
        }
    }
    
    public function updatedClientId()
    {
        // Client changed, could reset dependent fields if needed
    }
    
    public function updatedTemplateId()
    {
        if ($this->template_id) {
            $this->applyTemplate();
            $this->showTemplatePreview = true;
        } else {
            $this->showTemplatePreview = false;
        }
    }
    
    public function applyTemplate()
    {
        if (!$this->template_id) {
            return;
        }
        
        $template = ProjectTemplate::find($this->template_id);
        
        if (!$template) {
            return;
        }
        
        $settings = $template->default_settings ?? [];
        
        if (isset($settings['priority'])) {
            $this->priority = $settings['priority'];
        }
        
        if ($template->estimated_budget) {
            $this->budget = $template->estimated_budget;
        }
        
        if ($template->estimated_duration_days && $this->start_date) {
            $this->due_date = now()->parse($this->start_date)
                ->addDays($template->estimated_duration_days)
                ->format('Y-m-d');
        }
    }
    
    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }
    
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 4) {
            $this->currentStep = $step;
        }
    }
    
    protected function validateCurrentStep()
    {
        $rules = match ($this->currentStep) {
            1 => [
                'client_id' => 'required|exists:clients,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ],
            2 => [
                'manager_id' => 'nullable|exists:users,id',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date|after:start_date',
                'priority' => 'required|in:low,medium,high,critical',
                'status' => 'required|in:pending,active,on_hold',
                'budget' => 'nullable|numeric|min:0',
            ],
            3 => [
                'template_id' => 'nullable|exists:project_templates,id',
            ],
            default => [],
        };
        
        $this->validate($rules);
    }
    
    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prefix' => 'nullable|string|max:10',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after:start_date',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:pending,active,on_hold,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'template_id' => 'nullable|exists:project_templates,id',
        ];
    }
    
    public function messages()
    {
        return [
            'client_id.required' => 'Please select a client for this project.',
            'name.required' => 'Please enter a project name.',
            'name.max' => 'Project name must not exceed 255 characters.',
            'due_date.after' => 'Due date must be after the start date.',
            'budget.min' => 'Budget must be a positive number.',
        ];
    }
    
    public function save()
    {
        try {
            $this->validate();
            
            DB::beginTransaction();
            
            $projectData = [
                'company_id' => Auth::user()->company_id,
                'client_id' => $this->client_id,
                'name' => $this->name,
                'description' => $this->description,
                'prefix' => $this->prefix ?: null,
                'manager_id' => $this->manager_id,
                'start_date' => $this->start_date,
                'due' => $this->due_date,
                'priority' => $this->priority,
                'status' => $this->status,
                'budget' => $this->budget,
            ];
            
            if ($this->template_id) {
                $template = ProjectTemplate::findOrFail($this->template_id);
                
                if (method_exists($template, 'createProject')) {
                    $project = $template->createProject($projectData);
                } else {
                    $project = Project::create($projectData);
                }
                
                $template->increment('usage_count');
            } else {
                $project = Project::create($projectData);
            }
            
            DB::commit();
            
            session()->flash('success', 'Project created successfully.');
            
            return redirect()->route('projects.show', $project->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Project creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'client_id' => $this->client_id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('error', 'Failed to create project. Please try again or contact support.');
            
            return null;
        }
    }
    
    public function render()
    {
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
        
        $managers = User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();
        
        $templates = ProjectTemplate::where(function ($query) {
            $query->where('company_id', Auth::user()->company_id)
                ->orWhere('is_public', true);
        })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $selectedClient = $this->client_id ? Client::find($this->client_id) : null;
        $selectedTemplate = $this->template_id ? ProjectTemplate::find($this->template_id) : null;
        
        return view('livewire.projects.project-create', [
            'clients' => $clients,
            'managers' => $managers,
            'templates' => $templates,
            'selectedClient' => $selectedClient,
            'selectedTemplate' => $selectedTemplate,
        ]);
    }
}
