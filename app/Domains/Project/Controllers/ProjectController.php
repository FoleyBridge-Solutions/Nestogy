<?php

namespace App\Domains\Project\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectTemplate;
use App\Domains\Project\Models\ProjectMember;
use App\Domains\Project\Models\ProjectMilestone;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects with advanced filtering
     */
    public function index(Request $request)
    {
        $query = Project::with(['client', 'manager', 'members.user'])
            ->where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'completed') {
                $query->completed();
            } elseif ($status === 'overdue') {
                $query->overdue();
            } elseif ($status === 'due_soon') {
                $query->dueSoon();
            } else {
                $query->byStatus($status);
            }
        }

        // Apply priority filters
        if ($priority = $request->get('priority')) {
            $query->byPriority($priority);
        }

        // Apply category filters
        if ($category = $request->get('category')) {
            $query->byCategory($category);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->forClient($clientId);
        }

        // Apply manager filter
        if ($managerId = $request->get('manager_id')) {
            $query->forManager($managerId);
        }

        // Apply team member filter
        if ($memberId = $request->get('member_id')) {
            $query->whereHas('members', function($q) use ($memberId) {
                $q->where('user_id', $memberId)->where('is_active', true);
            });
        }

        // Apply date filters
        if ($dateRange = $request->get('date_range')) {
            switch ($dateRange) {
                case 'this_week':
                    $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()]);
                    break;
                case 'this_quarter':
                    $query->whereBetween('due_date', [now()->startOfQuarter(), now()->endOfQuarter()]);
                    break;
                case 'custom':
                    if ($request->get('start_date') && $request->get('end_date')) {
                        $query->whereBetween('due_date', [$request->get('start_date'), $request->get('end_date')]);
                    }
                    break;
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortBy, ['name', 'status', 'priority', 'due_date', 'created_at', 'progress_percentage'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Special view handling
        if ($request->get('view') === 'timeline') {
            return $this->timelineView($query, $request);
        }

        if ($request->get('view') === 'kanban') {
            return $this->kanbanView($query, $request);
        }

        $projects = $query->paginate(20)->appends($request->query());

        // Get filter options
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $managers = User::where('company_id', auth()->user()->company_id)
                       ->orderBy('name')
                       ->get();

        $members = User::where('company_id', auth()->user()->company_id)
                      ->whereHas('projectMembers', function($q) {
                          $q->where('is_active', true);
                      })
                      ->distinct()
                      ->orderBy('name')
                      ->get();

        // Calculate statistics
        $statistics = $this->calculateStatistics();

        return view('projects.index', compact(
            'projects', 'clients', 'managers', 'members', 'statistics'
        ));
    }

    /**
     * Show project timeline view
     */
    protected function timelineView($query, Request $request)
    {
        $projects = $query->with(['tasks', 'milestones'])->get();
        
        // Prepare timeline data
        $timelineData = $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'due_date' => $project->due_date?->format('Y-m-d'),
                'progress' => $project->getCalculatedProgress(),
                'status' => $project->status,
                'health' => $project->getHealthStatus(),
                'tasks' => $project->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'name' => $task->name,
                        'start_date' => $task->start_date?->format('Y-m-d'),
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'progress' => $task->progress_percentage,
                        'status' => $task->status,
                    ];
                }),
                'milestones' => $project->milestones->map(function ($milestone) {
                    return [
                        'id' => $milestone->id,
                        'name' => $milestone->name,
                        'due_date' => $milestone->due_date?->format('Y-m-d'),
                        'is_completed' => $milestone->isCompleted(),
                        'is_critical' => $milestone->is_critical,
                    ];
                }),
            ];
        });

        return view('projects.timeline', compact('timelineData'));
    }

    /**
     * Show project kanban view
     */
    protected function kanbanView($query, Request $request)
    {
        $projects = $query->get();
        
        $kanbanColumns = [
            Project::STATUS_PLANNING => 'Planning',
            Project::STATUS_ACTIVE => 'Active',
            Project::STATUS_ON_HOLD => 'On Hold',
            Project::STATUS_COMPLETED => 'Completed',
        ];

        $kanbanData = [];
        foreach ($kanbanColumns as $status => $label) {
            $kanbanData[$status] = [
                'label' => $label,
                'projects' => $projects->where('status', $status)->values(),
            ];
        }

        return view('projects.kanban', compact('kanbanData', 'kanbanColumns'));
    }

    /**
     * Show the form for creating a new project
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $managers = User::where('company_id', auth()->user()->company_id)
                       ->orderBy('name')
                       ->get();

        $templates = ProjectTemplate::where('company_id', auth()->user()->company_id)
                                   ->orWhere('is_public', true)
                                   ->active()
                                   ->orderBy('name')
                                   ->get();

        $selectedClientId = $request->get('client_id');
        $selectedTemplateId = $request->get('template_id');

        return view('projects.create', compact(
            'clients', 'managers', 'templates', 'selectedClientId', 'selectedTemplateId'
        ));
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Project::getValidationRules());

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        DB::beginTransaction();
        
        try {
            $projectData = $request->all();
            $projectData['company_id'] = auth()->user()->company_id;

            // Create project from template if specified
            if ($request->get('template_id')) {
                $template = ProjectTemplate::findOrFail($request->get('template_id'));
                $project = $template->createProject($projectData);
            } else {
                $project = Project::create($projectData);
            }

            // Add initial team members
            if ($request->has('team_members')) {
                foreach ($request->get('team_members', []) as $memberData) {
                    ProjectMember::create([
                        'project_id' => $project->id,
                        'user_id' => $memberData['user_id'],
                        'role' => $memberData['role'] ?? ProjectMember::ROLE_DEVELOPER,
                        'hourly_rate' => $memberData['hourly_rate'] ?? null,
                        'can_edit' => $memberData['can_edit'] ?? false,
                        'can_manage_tasks' => $memberData['can_manage_tasks'] ?? false,
                        'can_view_reports' => $memberData['can_view_reports'] ?? true,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('projects.show', $project)
                           ->with('success', 'Project created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to create project: ' . $e->getMessage()])
                           ->withInput();
        }
    }

    /**
     * Display the specified project
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'client',
            'manager',
            'members.user',
            'tasks.assignedUser',
            'milestones',
            'timeEntries.user',
        ]);

        // Get project statistics
        $statistics = $project->getStatistics();

        // Get recent activities (tasks, comments, time entries)
        $recentTasks = $project->tasks()
            ->with('assignedUser')
            ->latest()
            ->limit(10)
            ->get();

        $recentMilestones = $project->milestones()
            ->latest()
            ->limit(5)
            ->get();

        // Get project health status
        $healthStatus = $project->getHealthStatus();

        // Get upcoming deadlines
        $upcomingDeadlines = collect()
            ->merge($project->tasks()->dueSoon(7)->get())
            ->merge($project->milestones()->dueSoon(7)->get())
            ->sortBy('due_date');

        // Get project timeline data for mini chart
        $timelineData = [
            'start_date' => $project->start_date?->format('Y-m-d'),
            'due_date' => $project->due_date?->format('Y-m-d'),
            'progress' => $project->getCalculatedProgress(),
            'expected_progress' => $project->getExpectedProgress(),
        ];

        return view('projects.show', compact(
            'project',
            'statistics',
            'recentTasks',
            'recentMilestones',
            'healthStatus',
            'upcomingDeadlines',
            'timelineData'
        ));
    }

    /**
     * Show the form for editing the specified project
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $managers = User::where('company_id', auth()->user()->company_id)
                       ->orderBy('name')
                       ->get();

        return view('projects.edit', compact('project', 'clients', 'managers'));
    }

    /**
     * Update the specified project
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), Project::getValidationRules());

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $project->fill($request->all());
        $project->save();

        return redirect()->route('projects.show', $project)
                        ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        DB::beginTransaction();
        
        try {
            // Archive related entities
            $project->tasks()->delete();
            $project->milestones()->delete();
            $project->members()->delete();

            $project->delete();

            DB::commit();

            return redirect()->route('projects.index')
                           ->with('success', 'Project deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to delete project: ' . $e->getMessage()]);
        }
    }

    /**
     * Update project status
     */
    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', Project::getAvailableStatuses()),
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $project->status;
        $newStatus = $request->get('status');

        // Handle status-specific logic
        switch ($newStatus) {
            case Project::STATUS_ACTIVE:
                $project->markAsActive();
                break;
            case Project::STATUS_COMPLETED:
                $project->markAsCompleted();
                break;
            case Project::STATUS_ON_HOLD:
                $project->putOnHold();
                break;
            case Project::STATUS_CANCELLED:
                $project->cancel();
                break;
            case Project::STATUS_ARCHIVED:
                $project->archive();
                break;
            default:
                $project->update(['status' => $newStatus]);
        }

        // Log status change if reason provided
        if ($request->get('reason')) {
            // You can add a project activity log here
        }

        return response()->json([
            'success' => true,
            'message' => "Project status changed from {$oldStatus} to {$newStatus}",
            'project' => $project->fresh(),
        ]);
    }

    /**
     * Clone project
     */
    public function clone(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'copy_tasks' => 'boolean',
            'copy_milestones' => 'boolean',
            'copy_members' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        
        try {
            $newProject = $project->replicate();
            $newProject->name = $request->get('name');
            $newProject->status = Project::STATUS_PLANNING;
            $newProject->progress_percentage = 0;
            $newProject->actual_start_date = null;
            $newProject->actual_end_date = null;
            $newProject->actual_cost = null;
            $newProject->save();

            // Copy tasks if requested
            if ($request->get('copy_tasks', false)) {
                foreach ($project->tasks as $task) {
                    $newTask = $task->replicate();
                    $newTask->project_id = $newProject->id;
                    $newTask->status = Task::STATUS_TODO;
                    $newTask->progress_percentage = 0;
                    $newTask->actual_start_date = null;
                    $newTask->actual_end_date = null;
                    $newTask->actual_hours = null;
                    $newTask->save();
                }
            }

            // Copy milestones if requested
            if ($request->get('copy_milestones', false)) {
                foreach ($project->milestones as $milestone) {
                    $newMilestone = $milestone->replicate();
                    $newMilestone->project_id = $newProject->id;
                    $newMilestone->status = ProjectMilestone::STATUS_PENDING;
                    $newMilestone->completed_at = null;
                    $newMilestone->completion_percentage = 0;
                    $newMilestone->save();
                }
            }

            // Copy members if requested
            if ($request->get('copy_members', false)) {
                foreach ($project->members as $member) {
                    $newMember = $member->replicate();
                    $newMember->project_id = $newProject->id;
                    $newMember->joined_at = now();
                    $newMember->left_at = null;
                    $newMember->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project cloned successfully.',
                'project_url' => route('projects.show', $newProject),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone project: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export projects to CSV
     */
    public function export(Request $request)
    {
        $query = Project::with(['client', 'manager'])
            ->where('company_id', auth()->user()->company_id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($status = $request->get('status')) {
            $query->byStatus($status);
        }

        if ($priority = $request->get('priority')) {
            $query->byPriority($priority);
        }

        if ($category = $request->get('category')) {
            $query->byCategory($category);
        }

        $projects = $query->orderBy('name')->get();

        $filename = 'projects_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($projects) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Project Code',
                'Project Name',
                'Status',
                'Priority',
                'Category',
                'Client',
                'Manager',
                'Start Date',
                'Due Date',
                'Progress %',
                'Budget',
                'Actual Cost',
                'Team Size',
                'Tasks Count',
                'Health Status',
                'Created At'
            ]);

            // CSV data
            foreach ($projects as $project) {
                $health = $project->getHealthStatus();
                
                fputcsv($file, [
                    $project->project_code,
                    $project->name,
                    $project->getStatusLabel(),
                    $project->getPriorityLabel(),
                    $project->category,
                    $project->client?->name ?? 'N/A',
                    $project->manager?->name ?? 'N/A',
                    $project->start_date?->format('Y-m-d') ?? 'N/A',
                    $project->due_date?->format('Y-m-d') ?? 'N/A',
                    $project->getCalculatedProgress() . '%',
                    $project->budget ? ($project->budget_currency ?? 'USD') . ' ' . number_format($project->budget, 2) : 'N/A',
                    $project->actual_cost ? ($project->budget_currency ?? 'USD') . ' ' . number_format($project->actual_cost, 2) : 'N/A',
                    $project->members()->active()->count(),
                    $project->tasks()->count(),
                    $health['status'],
                    $project->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get project dashboard data (AJAX)
     */
    public function getDashboardData(Request $request)
    {
        $statistics = $this->calculateStatistics();
        
        $recentProjects = Project::with(['client', 'manager'])
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->getStatusLabel(),
                    'client' => $project->client?->name,
                    'progress' => $project->getCalculatedProgress(),
                    'due_date' => $project->due_date?->format('Y-m-d'),
                    'health' => $project->getHealthStatus(),
                ];
            });

        return response()->json([
            'statistics' => $statistics,
            'recent_projects' => $recentProjects,
        ]);
    }

    /**
     * Calculate project statistics
     */
    protected function calculateStatistics(): array
    {
        $companyId = auth()->user()->company_id;
        
        $baseQuery = Project::where('company_id', $companyId);
        
        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->active()->count(),
            'completed' => (clone $baseQuery)->completed()->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
            'due_soon' => (clone $baseQuery)->dueSoon()->count(),
            'on_hold' => (clone $baseQuery)->byStatus(Project::STATUS_ON_HOLD)->count(),
            'planning' => (clone $baseQuery)->byStatus(Project::STATUS_PLANNING)->count(),
            
            // Priority breakdown
            'high_priority' => (clone $baseQuery)->byPriority(Project::PRIORITY_HIGH)->active()->count(),
            'critical_priority' => (clone $baseQuery)->byPriority(Project::PRIORITY_CRITICAL)->active()->count(),
            
            // Health status
            'healthy' => (clone $baseQuery)->active()->get()->filter(function($p) {
                return $p->getHealthStatus()['status'] === 'good';
            })->count(),
            'warning' => (clone $baseQuery)->active()->get()->filter(function($p) {
                return $p->getHealthStatus()['status'] === 'warning';
            })->count(),
            'critical' => (clone $baseQuery)->active()->get()->filter(function($p) {
                return $p->getHealthStatus()['status'] === 'critical';
            })->count(),
            
            // Financial
            'total_budget' => (clone $baseQuery)->sum('budget') ?? 0,
            'total_actual_cost' => (clone $baseQuery)->sum('actual_cost') ?? 0,
        ];
    }
}