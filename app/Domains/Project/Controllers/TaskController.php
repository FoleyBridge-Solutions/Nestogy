<?php

namespace App\Domains\Project\Controllers;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\Task;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks for a project
     */
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $query = $project->tasks()->with(['assignedUser', 'creator', 'parentTask', 'subtasks']);

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

        // Apply assignee filter
        if ($assignedTo = $request->get('assigned_to')) {
            if ($assignedTo === 'me') {
                $query->assignedTo(auth()->id());
            } elseif ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->assignedTo($assignedTo);
            }
        }

        // Apply category filter
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Apply milestone filter
        if ($milestoneId = $request->get('milestone_id')) {
            $query->where('milestone_id', $milestoneId);
        }

        // Apply parent task filter (show only main tasks or subtasks)
        if ($request->get('view_type') === 'main_tasks') {
            $query->parentTasks();
        } elseif ($request->get('view_type') === 'subtasks') {
            $query->subtasks();
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'sort_order');
        $sortDirection = $request->get('direction', 'asc');

        if (in_array($sortBy, ['name', 'status', 'priority', 'due_date', 'created_at', 'sort_order'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Special view handling
        if ($request->get('view') === 'kanban') {
            return $this->kanbanView($project, $query, $request);
        }

        if ($request->get('view') === 'calendar') {
            return $this->calendarView($project, $query, $request);
        }

        if ($request->get('view') === 'gantt') {
            return $this->ganttView($project, $query, $request);
        }

        $tasks = $query->paginate(50)->appends($request->query());

        // Get filter options
        $assignees = User::where('company_id', auth()->user()->company_id)
            ->whereHas('assignedTasks', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->orderBy('name')
            ->get();

        $milestones = $project->milestones()->orderBy('sort_order')->get();

        // Calculate task statistics
        $statistics = $this->calculateTaskStatistics($project);

        return view('projects.tasks.index', compact(
            'project', 'tasks', 'assignees', 'milestones', 'statistics'
        ));
    }

    /**
     * Show task kanban view
     */
    protected function kanbanView(Project $project, $query, Request $request)
    {
        $tasks = $query->get();

        $kanbanColumns = [
            Task::STATUS_TODO => 'To Do',
            Task::STATUS_IN_PROGRESS => 'In Progress',
            Task::STATUS_IN_REVIEW => 'In Review',
            Task::STATUS_BLOCKED => 'Blocked',
            Task::STATUS_COMPLETED => 'Completed',
        ];

        $kanbanData = [];
        foreach ($kanbanColumns as $status => $label) {
            $kanbanData[$status] = [
                'label' => $label,
                'tasks' => $tasks->where('status', $status)->values(),
                'count' => $tasks->where('status', $status)->count(),
            ];
        }

        return view('projects.tasks.kanban', compact(
            'project', 'kanbanData', 'kanbanColumns'
        ));
    }

    /**
     * Show task calendar view
     */
    protected function calendarView(Project $project, $query, Request $request)
    {
        $tasks = $query->whereNotNull('due_date')->get();

        $calendarEvents = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->name,
                'start' => $task->start_date?->format('Y-m-d'),
                'end' => $task->due_date?->format('Y-m-d'),
                'color' => $this->getTaskColor($task),
                'className' => 'task-event task-'.$task->status,
                'extendedProps' => [
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assignee' => $task->assignedUser?->name,
                    'progress' => $task->progress_percentage,
                ],
            ];
        });

        return view('projects.tasks.calendar', compact(
            'project', 'calendarEvents'
        ));
    }

    /**
     * Show task Gantt chart view
     */
    protected function ganttView(Project $project, $query, Request $request)
    {
        $tasks = $query->with(['dependencies', 'dependentTasks'])->get();

        $ganttData = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'name' => $task->name,
                'start' => $task->start_date?->format('Y-m-d'),
                'end' => $task->due_date?->format('Y-m-d'),
                'progress' => $task->progress_percentage,
                'dependencies' => $task->dependencies->pluck('id')->toArray(),
                'assignee' => $task->assignedUser?->name,
                'status' => $task->status,
                'priority' => $task->priority,
                'parent' => $task->parent_task_id,
            ];
        });

        return view('projects.tasks.gantt', compact(
            'project', 'ganttData'
        ));
    }

    /**
     * Show the form for creating a new task
     */
    public function create(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $assignees = User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $milestones = $project->milestones()->orderBy('sort_order')->get();

        $parentTasks = $project->tasks()->parentTasks()
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->orderBy('name')
            ->get();

        $availableDependencies = $project->tasks()
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->orderBy('name')
            ->get();

        $selectedParentId = $request->get('parent_id');
        $selectedMilestoneId = $request->get('milestone_id');

        return view('projects.tasks.create', compact(
            'project', 'assignees', 'milestones', 'parentTasks',
            'availableDependencies', 'selectedParentId', 'selectedMilestoneId'
        ));
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), array_merge(
            Task::getValidationRules(),
            ['project_id' => 'required|exists:projects,id']
        ));

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $taskData = $request->all();
            $taskData['project_id'] = $project->id;
            $taskData['created_by'] = auth()->id();

            $task = Task::create($taskData);

            // Add dependencies if specified
            if ($request->has('dependencies')) {
                foreach ($request->get('dependencies', []) as $dependencyId) {
                    $dependencyTask = Task::find($dependencyId);
                    if ($dependencyTask && $dependencyTask->project_id === $project->id) {
                        $task->addDependency($dependencyTask);
                    }
                }
            }

            // Add watchers
            if ($request->has('watchers')) {
                $task->watchers()->attach($request->get('watchers'));
            }

            // Create checklist items if provided
            if ($request->has('checklist_items')) {
                foreach ($request->get('checklist_items', []) as $item) {
                    if (! empty($item['name'])) {
                        $task->checklistItems()->create([
                            'name' => $item['name'],
                            'is_completed' => false,
                            'sort_order' => $item['sort_order'] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task created successfully.',
                    'task' => $task->load(['assignedUser', 'project']),
                ]);
            }

            return redirect()->route('projects.tasks.show', [$project, $task])
                ->with('success', 'Task created successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create task: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Failed to create task: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified task
     */
    public function show(Project $project, Task $task)
    {
        $this->authorize('view', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $task->load([
            'assignedUser',
            'creator',
            'parentTask',
            'subtasks.assignedUser',
            'dependencies.assignedUser',
            'dependentTasks.assignedUser',
            'comments.user',
            'attachments',
            'checklistItems',
            'timeEntries.user',
            'watchers',
        ]);

        // Get task statistics
        $statistics = [
            'time_logged' => $task->getTotalTimeLogged(),
            'time_remaining' => $task->getTimeRemaining(),
            'subtasks_count' => $task->subtasks->count(),
            'completed_subtasks' => $task->subtasks->where('status', Task::STATUS_COMPLETED)->count(),
            'checklist_progress' => $task->getCalculatedProgress(),
            'watchers_count' => $task->watchers->count(),
        ];

        // Get task health status
        $healthStatus = $task->getHealthStatus();

        // Get recent activities
        $recentComments = $task->comments()->with('user')->latest()->limit(10)->get();
        $recentTimeEntries = $task->timeEntries()->with('user')->latest()->limit(5)->get();

        return view('projects.tasks.show', compact(
            'project', 'task', 'statistics', 'healthStatus', 'recentComments', 'recentTimeEntries'
        ));
    }

    /**
     * Show the form for editing the specified task
     */
    public function edit(Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $assignees = User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $milestones = $project->milestones()->orderBy('sort_order')->get();

        $parentTasks = $project->tasks()->parentTasks()
            ->where('id', '!=', $task->id)
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->orderBy('name')
            ->get();

        $availableDependencies = $project->tasks()
            ->where('id', '!=', $task->id)
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CLOSED])
            ->orderBy('name')
            ->get();

        $currentDependencies = $task->dependencies->pluck('id')->toArray();

        return view('projects.tasks.edit', compact(
            'project', 'task', 'assignees', 'milestones', 'parentTasks',
            'availableDependencies', 'currentDependencies'
        ));
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), Task::getValidationRules());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $task->fill($request->all());
            $task->save();

            // Update dependencies
            if ($request->has('dependencies')) {
                $task->dependencies()->detach();

                foreach ($request->get('dependencies', []) as $dependencyId) {
                    $dependencyTask = Task::find($dependencyId);
                    if ($dependencyTask && $dependencyTask->project_id === $project->id) {
                        $task->addDependency($dependencyTask);
                    }
                }
            }

            // Update watchers
            if ($request->has('watchers')) {
                $task->watchers()->sync($request->get('watchers'));
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task updated successfully.',
                    'task' => $task->fresh(['assignedUser', 'project']),
                ]);
            }

            return redirect()->route('projects.tasks.show', [$project, $task])
                ->with('success', 'Task updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update task: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Failed to update task: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        DB::beginTransaction();

        try {
            // Remove dependencies
            $task->dependencies()->detach();
            $task->dependentTasks()->detach();

            // Delete related data
            $task->comments()->delete();
            $task->attachments()->delete();
            $task->checklistItems()->delete();
            $task->timeEntries()->delete();
            $task->watchers()->detach();

            // Delete subtasks or move them to parent
            foreach ($task->subtasks as $subtask) {
                $subtask->update(['parent_task_id' => $task->parent_task_id]);
            }

            $task->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task deleted successfully.',
                ]);
            }

            return redirect()->route('projects.tasks.index', $project)
                ->with('success', 'Task deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete task: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete task: '.$e->getMessage()]);
        }
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:'.implode(',', Task::getAvailableStatuses()),
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldStatus = $task->status;
        $newStatus = $request->get('status');

        try {
            // Handle status-specific logic
            switch ($newStatus) {
                case Task::STATUS_IN_PROGRESS:
                    $task->startWork();
                    break;
                case Task::STATUS_COMPLETED:
                    $task->markAsCompleted();
                    break;
                case Task::STATUS_BLOCKED:
                    $task->block($request->get('comment'));
                    break;
                default:
                    $task->update(['status' => $newStatus]);
            }

            // Add comment if provided
            if ($request->get('comment')) {
                $task->comments()->create([
                    'user_id' => auth()->id(),
                    'comment' => $request->get('comment'),
                    'type' => 'status_change',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Task status changed from {$oldStatus} to {$newStatus}",
                'task' => $task->fresh(['assignedUser']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign task to a user
     */
    public function assign(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $task->update(['assigned_to' => $request->get('assigned_to')]);

            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully.',
                'task' => $task->fresh(['assignedUser']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update task progress
     */
    public function updateProgress(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'progress' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $progress = $request->get('progress');
        $task->update(['progress_percentage' => $progress]);

        // Auto-update status based on progress
        if ($progress === 100 && ! $task->isCompleted()) {
            $task->markAsCompleted();
        } elseif ($progress > 0 && $task->status === Task::STATUS_TODO) {
            $task->update(['status' => Task::STATUS_IN_PROGRESS]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task progress updated successfully.',
            'task' => $task->fresh(['assignedUser']),
        ]);
    }

    /**
     * Reorder tasks (drag and drop)
     */
    public function reorder(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validator = Validator::make($request->all(), [
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|integer|exists:project_tasks,id',
            'tasks.*.sort_order' => 'required|integer|min:0',
            'tasks.*.status' => 'nullable|in:'.implode(',', Task::getAvailableStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->get('tasks') as $taskData) {
                $task = Task::where('id', $taskData['id'])
                    ->where('project_id', $project->id)
                    ->first();

                if ($task) {
                    $updateData = ['sort_order' => $taskData['sort_order']];

                    // Update status if provided (for kanban moves)
                    if (isset($taskData['status']) && $taskData['status'] !== $task->status) {
                        $updateData['status'] = $taskData['status'];
                    }

                    $task->update($updateData);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tasks reordered successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder tasks: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clone task
     */
    public function clone(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'copy_subtasks' => 'boolean',
            'copy_dependencies' => 'boolean',
            'copy_checklist' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $newTask = $task->clone([
                'name' => $request->get('name'),
                'status' => Task::STATUS_TODO,
                'progress_percentage' => 0,
                'actual_start_date' => null,
                'actual_end_date' => null,
                'actual_hours' => null,
            ]);

            // Copy subtasks if requested
            if ($request->get('copy_subtasks', false)) {
                foreach ($task->subtasks as $subtask) {
                    $subtask->clone(['parent_task_id' => $newTask->id]);
                }
            }

            // Copy dependencies if requested
            if ($request->get('copy_dependencies', false)) {
                foreach ($task->dependencies as $dependency) {
                    $newTask->addDependency($dependency);
                }
            }

            // Copy checklist if requested
            if ($request->get('copy_checklist', false)) {
                foreach ($task->checklistItems as $item) {
                    $newTask->checklistItems()->create([
                        'name' => $item->name,
                        'is_completed' => false,
                        'sort_order' => $item->sort_order,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task cloned successfully.',
                'task_url' => route('projects.tasks.show', [$project, $newTask]),
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to clone task: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get task color for calendar/kanban views
     */
    protected function getTaskColor(Task $task): string
    {
        if ($task->isOverdue()) {
            return '#dc3545'; // Red
        }

        return match ($task->priority) {
            Task::PRIORITY_CRITICAL => '#dc3545',
            Task::PRIORITY_URGENT => '#fd7e14',
            Task::PRIORITY_HIGH => '#ffc107',
            Task::PRIORITY_NORMAL => '#28a745',
            Task::PRIORITY_LOW => '#6c757d',
            default => '#007bff',
        };
    }

    /**
     * Calculate task statistics for a project
     */
    protected function calculateTaskStatistics(Project $project): array
    {
        $baseQuery = $project->tasks();

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->completed()->count(),
            'in_progress' => (clone $baseQuery)->byStatus(Task::STATUS_IN_PROGRESS)->count(),
            'todo' => (clone $baseQuery)->byStatus(Task::STATUS_TODO)->count(),
            'blocked' => (clone $baseQuery)->byStatus(Task::STATUS_BLOCKED)->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
            'due_soon' => (clone $baseQuery)->dueSoon()->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_to')->count(),

            // Priority breakdown
            'high_priority' => (clone $baseQuery)->byPriority(Task::PRIORITY_HIGH)->active()->count(),
            'critical_priority' => (clone $baseQuery)->byPriority(Task::PRIORITY_CRITICAL)->active()->count(),

            // Time tracking
            'total_estimated_hours' => (clone $baseQuery)->sum('estimated_hours') ?? 0,
            'total_actual_hours' => (clone $baseQuery)->sum('actual_hours') ?? 0,
        ];
    }
}
