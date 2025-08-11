<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket Template Model
 * 
 * Represents reusable templates for creating tickets with predefined
 * subject, body, priority, and other settings.
 */
class TicketTemplate extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'subject_template',
        'body_template',
        'priority',
        'category',
        'estimated_hours',
        'default_assignee_id',
        'is_active',
        'custom_fields',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'estimated_hours' => 'decimal:2',
        'default_assignee_id' => 'integer',
        'is_active' => 'boolean',
        'custom_fields' => 'array',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'template_id');
    }

    public function recurringTickets(): HasMany
    {
        return $this->hasMany(RecurringTicket::class, 'template_id');
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Process template variables in subject and body
     */
    public function processTemplate(array $variables = []): array
    {
        $subject = $this->subject_template;
        $body = $this->body_template;

        // Default variables
        $defaultVariables = [
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
            'time' => now()->format('H:i:s'),
        ];

        $allVariables = array_merge($defaultVariables, $variables);

        // Replace variables in format {{variable_name}}
        foreach ($allVariables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Get template usage statistics
     */
    public function getUsageStats(): array
    {
        $totalTickets = $this->tickets()->count();
        $recentTickets = $this->tickets()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_tickets' => $totalTickets,
            'recent_tickets' => $recentTickets,
            'recurring_schedules' => $this->recurringTickets()->where('is_active', true)->count(),
            'last_used' => $this->tickets()->latest()->first()?->created_at,
        ];
    }

    /**
     * Create ticket from this template
     */
    public function createTicket(array $data = []): Ticket
    {
        $processed = $this->processTemplate($data['variables'] ?? []);

        $ticketData = array_merge([
            'subject' => $processed['subject'],
            'details' => $processed['body'],
            'priority' => $this->priority,
            'category' => $this->category,
            'assigned_to' => $this->default_assignee_id,
            'template_id' => $this->id,
        ], $data);

        // Remove variables key if it exists
        unset($ticketData['variables']);

        return Ticket::create($ticketData);
    }

    /**
     * Duplicate this template
     */
    public function duplicate(string $newName = null): self
    {
        $copy = $this->replicate();
        $copy->name = $newName ?? ($this->name . ' (Copy)');
        $copy->is_active = false; // New templates start as inactive
        $copy->save();

        return $copy;
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRecentlyUsed($query, int $days = 30)
    {
        return $query->whereHas('tickets', function ($q) use ($days) {
            $q->where('created_at', '>=', now()->subDays($days));
        });
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit($limit);
    }

    // ===========================================
    // ACCESSORS & MUTATORS
    // ===========================================

    /**
     * Get available template variables
     */
    public function getAvailableVariablesAttribute(): array
    {
        $variables = [];
        
        // Extract variables from subject and body templates
        preg_match_all('/\{\{(\w+)\}\}/', $this->subject_template . ' ' . $this->body_template, $matches);
        
        if (!empty($matches[1])) {
            $variables = array_unique($matches[1]);
        }

        return $variables;
    }

    /**
     * Get template preview with sample data
     */
    public function getPreviewAttribute(): array
    {
        $sampleData = [
            'client_name' => 'Sample Client',
            'contact_name' => 'John Doe',
            'location' => 'Main Office',
            'asset_name' => 'Server-001',
        ];

        return $this->processTemplate($sampleData);
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_template' => 'required|string|max:255',
            'body_template' => 'required|string',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'category' => 'nullable|string|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'default_assignee_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'custom_fields' => 'nullable|array',
        ];
    }

    // ===========================================
    // MODEL EVENTS
    // ===========================================

    protected static function boot()
    {
        parent::boot();

        // Validate template syntax on saving
        static::saving(function ($template) {
            // Check for balanced template variables
            $subject = $template->subject_template;
            $body = $template->body_template;
            
            $openCount = substr_count($subject . $body, '{{');
            $closeCount = substr_count($subject . $body, '}}');
            
            if ($openCount !== $closeCount) {
                throw new \InvalidArgumentException('Template variables are not properly balanced. Check {{ and }} syntax.');
            }
        });
    }
}