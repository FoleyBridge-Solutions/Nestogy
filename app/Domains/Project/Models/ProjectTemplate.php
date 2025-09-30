<?php

namespace App\Domains\Project\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProjectTemplate Model
 *
 * Manages project templates for quick project creation with predefined
 * tasks, milestones, and team structures.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property array|null $default_settings
 * @property array|null $task_templates
 * @property array|null $milestone_templates
 * @property array|null $role_templates
 * @property int|null $estimated_duration_days
 * @property decimal|null $estimated_budget
 * @property string|null $budget_currency
 * @property bool $is_active
 * @property bool $is_public
 * @property int $usage_count
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ProjectTemplate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'project_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'category',
        'default_settings',
        'task_templates',
        'milestone_templates',
        'role_templates',
        'estimated_duration_days',
        'estimated_budget',
        'budget_currency',
        'is_active',
        'is_public',
        'usage_count',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'default_settings' => 'array',
        'task_templates' => 'array',
        'milestone_templates' => 'array',
        'role_templates' => 'array',
        'estimated_duration_days' => 'integer',
        'estimated_budget' => 'decimal:2',
        'usage_count' => 'integer',
        'created_by' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Template categories
     */
    const CATEGORY_DEVELOPMENT = 'development';

    const CATEGORY_DESIGN = 'design';

    const CATEGORY_MARKETING = 'marketing';

    const CATEGORY_CONSULTING = 'consulting';

    const CATEGORY_MAINTENANCE = 'maintenance';

    const CATEGORY_RESEARCH = 'research';

    const CATEGORY_GENERAL = 'general';

    const CATEGORY_CUSTOM = 'custom';

    /**
     * Get the company that owns the template.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get projects created from this template.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'template_id');
    }

    /**
     * Get the category with human-readable formatting.
     */
    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_DEVELOPMENT => 'Software Development',
            self::CATEGORY_DESIGN => 'Design & Creative',
            self::CATEGORY_MARKETING => 'Marketing & Advertising',
            self::CATEGORY_CONSULTING => 'Consulting & Advisory',
            self::CATEGORY_MAINTENANCE => 'Maintenance & Support',
            self::CATEGORY_RESEARCH => 'Research & Analysis',
            self::CATEGORY_GENERAL => 'General Purpose',
            self::CATEGORY_CUSTOM => 'Custom Template',
            default => 'Unknown Category',
        };
    }

    /**
     * Get template statistics.
     */
    public function getStatistics(): array
    {
        return [
            'usage' => [
                'total_projects' => $this->usage_count,
                'active_projects' => $this->projects()->where('status', Project::STATUS_ACTIVE)->count(),
                'completed_projects' => $this->projects()->where('status', Project::STATUS_COMPLETED)->count(),
            ],
            'template_data' => [
                'task_count' => count($this->task_templates ?? []),
                'milestone_count' => count($this->milestone_templates ?? []),
                'role_count' => count($this->role_templates ?? []),
            ],
            'estimates' => [
                'duration_days' => $this->estimated_duration_days,
                'budget' => $this->estimated_budget,
                'currency' => $this->budget_currency,
            ],
        ];
    }

    /**
     * Create a project from this template.
     */
    public function createProject(array $projectData): Project
    {
        // Merge template defaults with provided data
        $defaultSettings = $this->default_settings ?? [];
        $mergedData = array_merge($defaultSettings, $projectData, [
            'template_id' => $this->id,
            'is_template' => false,
        ]);

        // Create the project
        $project = Project::create($mergedData);

        // Copy template structure to project
        $this->copyToProject($project);

        // Increment usage count
        $this->increment('usage_count');

        return $project;
    }

    /**
     * Copy template structure to a project.
     */
    public function copyToProject(Project $project): void
    {
        // Copy milestones
        if (! empty($this->milestone_templates)) {
            foreach ($this->milestone_templates as $milestoneTemplate) {
                $milestoneData = [
                    'project_id' => $project->id,
                    'name' => $milestoneTemplate['name'],
                    'description' => $milestoneTemplate['description'] ?? null,
                    'status' => ProjectMilestone::STATUS_PENDING,
                    'priority' => $milestoneTemplate['priority'] ?? ProjectMilestone::PRIORITY_NORMAL,
                    'deliverables' => $milestoneTemplate['deliverables'] ?? null,
                    'acceptance_criteria' => $milestoneTemplate['acceptance_criteria'] ?? null,
                    'is_critical' => $milestoneTemplate['is_critical'] ?? false,
                    'sort_order' => $milestoneTemplate['sort_order'] ?? 0,
                ];

                // Calculate due date based on project start date and offset
                if (isset($milestoneTemplate['days_from_start']) && $project->start_date) {
                    $milestoneData['due_date'] = $project->start_date->addDays($milestoneTemplate['days_from_start']);
                }

                $milestone = ProjectMilestone::create($milestoneData);

                // Store milestone ID for task assignment
                $milestoneTemplate['created_id'] = $milestone->id;
            }
        }

        // Copy tasks
        if (! empty($this->task_templates)) {
            $createdTasks = [];

            foreach ($this->task_templates as $taskTemplate) {
                $taskData = [
                    'project_id' => $project->id,
                    'name' => $taskTemplate['name'],
                    'description' => $taskTemplate['description'] ?? null,
                    'status' => Task::STATUS_TODO,
                    'priority' => $taskTemplate['priority'] ?? Task::PRIORITY_NORMAL,
                    'category' => $taskTemplate['category'] ?? null,
                    'estimated_hours' => $taskTemplate['estimated_hours'] ?? null,
                    'is_billable' => $taskTemplate['is_billable'] ?? true,
                    'sort_order' => $taskTemplate['sort_order'] ?? 0,
                ];

                // Calculate dates based on project start date and offsets
                if (isset($taskTemplate['days_from_start']) && $project->start_date) {
                    $taskData['start_date'] = $project->start_date->addDays($taskTemplate['days_from_start']);
                }

                if (isset($taskTemplate['duration_days']) && isset($taskData['start_date'])) {
                    $taskData['due_date'] = Carbon::parse($taskData['start_date'])->addDays($taskTemplate['duration_days']);
                }

                // Assign to milestone if specified
                if (isset($taskTemplate['milestone_template_id'])) {
                    $milestoneTemplate = collect($this->milestone_templates)
                        ->where('id', $taskTemplate['milestone_template_id'])
                        ->first();

                    if ($milestoneTemplate && isset($milestoneTemplate['created_id'])) {
                        $taskData['milestone_id'] = $milestoneTemplate['created_id'];
                    }
                }

                $task = Task::create($taskData);
                $createdTasks[$taskTemplate['id'] ?? $task->id] = $task;
            }

            // Create task dependencies
            foreach ($this->task_templates as $taskTemplate) {
                if (! empty($taskTemplate['dependencies'])) {
                    $task = $createdTasks[$taskTemplate['id']] ?? null;
                    if ($task) {
                        foreach ($taskTemplate['dependencies'] as $dependencyId) {
                            $dependencyTask = $createdTasks[$dependencyId] ?? null;
                            if ($dependencyTask) {
                                $task->addDependency($dependencyTask);
                            }
                        }
                    }
                }
            }
        }

        // Copy team roles
        if (! empty($this->role_templates)) {
            foreach ($this->role_templates as $roleTemplate) {
                // Role templates are just suggestions - actual users need to be assigned later
                // Store as project metadata for reference
                $project->update([
                    'custom_fields' => array_merge($project->custom_fields ?? [], [
                        'suggested_roles' => $this->role_templates,
                    ]),
                ]);
            }
        }
    }

    /**
     * Clone template for customization.
     */
    public function clone(?string $newName = null): ProjectTemplate
    {
        $attributes = $this->toArray();
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);

        $attributes['name'] = $newName ?: 'Copy of '.$attributes['name'];
        $attributes['usage_count'] = 0;
        $attributes['created_by'] = auth()->id();

        return ProjectTemplate::create($attributes);
    }

    /**
     * Export template as JSON.
     */
    public function exportAsJson(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'default_settings' => $this->default_settings,
            'task_templates' => $this->task_templates,
            'milestone_templates' => $this->milestone_templates,
            'role_templates' => $this->role_templates,
            'estimated_duration_days' => $this->estimated_duration_days,
            'estimated_budget' => $this->estimated_budget,
            'budget_currency' => $this->budget_currency,
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Import template from JSON.
     */
    public static function importFromJson(array $data, int $companyId): ProjectTemplate
    {
        $templateData = [
            'company_id' => $companyId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? self::CATEGORY_CUSTOM,
            'default_settings' => $data['default_settings'] ?? null,
            'task_templates' => $data['task_templates'] ?? null,
            'milestone_templates' => $data['milestone_templates'] ?? null,
            'role_templates' => $data['role_templates'] ?? null,
            'estimated_duration_days' => $data['estimated_duration_days'] ?? null,
            'estimated_budget' => $data['estimated_budget'] ?? null,
            'budget_currency' => $data['budget_currency'] ?? 'USD',
            'is_active' => true,
            'is_public' => false,
            'usage_count' => 0,
            'created_by' => auth()->id(),
        ];

        return self::create($templateData);
    }

    /**
     * Activate template.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate template.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Make template public.
     */
    public function makePublic(): void
    {
        $this->update(['is_public' => true]);
    }

    /**
     * Make template private.
     */
    public function makePrivate(): void
    {
        $this->update(['is_public' => false]);
    }

    /**
     * Get template data for project creation.
     */
    public function getTemplateData(): array
    {
        $data = $this->default_settings ?? [];

        // Add template-specific defaults
        if ($this->estimated_duration_days) {
            $data['start_date'] = $data['start_date'] ?? now()->format('Y-m-d');
            $data['due_date'] = $data['due_date'] ?? now()->addDays($this->estimated_duration_days)->format('Y-m-d');
        }

        if ($this->estimated_budget) {
            $data['budget'] = $data['budget'] ?? $this->estimated_budget;
            $data['budget_currency'] = $data['budget_currency'] ?? $this->budget_currency;
        }

        $data['category'] = $data['category'] ?? $this->category;
        $data['priority'] = $data['priority'] ?? Project::PRIORITY_NORMAL;

        return $data;
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get public templates.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get templates by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get most used templates.
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope to search templates.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('category', 'like', '%'.$search.'%');
        });
    }

    /**
     * Get validation rules for template creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:'.implode(',', self::getAvailableCategories()),
            'default_settings' => 'nullable|array',
            'task_templates' => 'nullable|array',
            'milestone_templates' => 'nullable|array',
            'role_templates' => 'nullable|array',
            'estimated_duration_days' => 'nullable|integer|min:1',
            'estimated_budget' => 'nullable|numeric|min:0',
            'budget_currency' => 'nullable|string|size:3',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get available categories.
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_DEVELOPMENT,
            self::CATEGORY_DESIGN,
            self::CATEGORY_MARKETING,
            self::CATEGORY_CONSULTING,
            self::CATEGORY_MAINTENANCE,
            self::CATEGORY_RESEARCH,
            self::CATEGORY_GENERAL,
            self::CATEGORY_CUSTOM,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set created_by for new templates
        static::creating(function ($template) {
            if (! $template->created_by && auth()->user()) {
                $template->created_by = auth()->user()->id;
            }

            if (! $template->company_id && auth()->user()) {
                $template->company_id = auth()->user()->company_id;
            }

            $template->is_active = $template->is_active ?? true;
            $template->is_public = $template->is_public ?? false;
            $template->usage_count = $template->usage_count ?? 0;
        });
    }
}
