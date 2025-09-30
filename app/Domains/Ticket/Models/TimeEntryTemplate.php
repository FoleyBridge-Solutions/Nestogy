<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Time Entry Template Model
 *
 * Represents pre-defined templates for common time tracking tasks
 * with default hours, work types, and smart categorization keywords.
 */
class TimeEntryTemplate extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'work_type',
        'default_hours',
        'category',
        'keywords',
        'is_active',
        'is_billable',
        'usage_count',
        'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'default_hours' => 'decimal:2',
        'keywords' => 'array',
        'is_active' => 'boolean',
        'is_billable' => 'boolean',
        'usage_count' => 'integer',
        'metadata' => 'array',
    ];

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if template matches given keywords from ticket
     */
    public function matchesKeywords(string $text): int
    {
        if (! $this->keywords || empty($this->keywords)) {
            return 0;
        }

        $text = strtolower($text);
        $matches = 0;

        foreach ($this->keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                $matches++;
            }
        }

        return $matches;
    }

    /**
     * Calculate confidence score for this template matching a ticket
     */
    public function getConfidenceScore(string $subject, string $details = ''): float
    {
        $text = strtolower($subject.' '.$details);
        $keywordMatches = $this->matchesKeywords($text);

        if (! $this->keywords || empty($this->keywords)) {
            return 0.0;
        }

        $score = ($keywordMatches / count($this->keywords)) * 100;

        // Boost score based on usage frequency (popular templates get slight boost)
        $usageBoost = min($this->usage_count * 0.5, 10);

        return min($score + $usageBoost, 100);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Create time entry from this template
     */
    public function createTimeEntry(int $ticketId, int $userId, array $overrides = []): TicketTimeEntry
    {
        $data = array_merge([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'company_id' => $this->company_id,
            'hours_worked' => $this->default_hours,
            'work_type' => $this->work_type,
            'billable' => $this->is_billable,
            'description' => $this->description,
            'entry_type' => TicketTimeEntry::TYPE_MANUAL,
            'work_date' => now()->toDateString(),
            'metadata' => [
                'template_id' => $this->id,
                'template_name' => $this->name,
            ],
        ], $overrides);

        $timeEntry = TicketTimeEntry::create($data);

        // Increment usage count
        $this->incrementUsage();

        return $timeEntry;
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

    public function scopeByWorkType($query, string $workType)
    {
        return $query->where('work_type', $workType);
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    public function scopeForTicket($query, string $subject, string $details = '')
    {
        $text = strtolower($subject.' '.$details);

        return $query->active()->get()->sortByDesc(function ($template) use ($text) {
            return $template->getConfidenceScore($text);
        });
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    /**
     * Get suggested templates for a ticket
     */
    public static function getSuggestionsForTicket(int $companyId, string $subject, string $details = '', int $limit = 5): \Illuminate\Support\Collection
    {
        $templates = self::where('company_id', $companyId)->active()->get();

        $suggestions = $templates->map(function ($template) use ($subject, $details) {
            $confidence = $template->getConfidenceScore($subject, $details);

            if ($confidence < 10) { // Only show if at least 10% confidence
                return null;
            }

            return [
                'template' => $template,
                'confidence' => $confidence,
            ];
        })->filter()->sortByDesc('confidence')->take($limit);

        return $suggestions;
    }

    /**
     * Create default templates for a company
     */
    public static function createDefaultTemplates(int $companyId): void
    {
        $defaults = [
            [
                'name' => 'Password Reset',
                'description' => 'Reset user password and security questions',
                'work_type' => 'account_management',
                'default_hours' => 0.25,
                'category' => 'Account Support',
                'keywords' => ['password', 'reset', 'login', 'account', 'locked'],
                'is_billable' => true,
            ],
            [
                'name' => 'Email Setup',
                'description' => 'Configure email account and settings',
                'work_type' => 'email_support',
                'default_hours' => 0.5,
                'category' => 'Email Support',
                'keywords' => ['email', 'outlook', 'mail', 'setup', 'configure'],
                'is_billable' => true,
            ],
            [
                'name' => 'Network Troubleshooting',
                'description' => 'Diagnose and resolve network connectivity issues',
                'work_type' => 'network_support',
                'default_hours' => 1.0,
                'category' => 'Network Support',
                'keywords' => ['network', 'internet', 'connection', 'wifi', 'ethernet'],
                'is_billable' => true,
            ],
            [
                'name' => 'Software Installation',
                'description' => 'Install and configure software applications',
                'work_type' => 'software_support',
                'default_hours' => 0.75,
                'category' => 'Software Support',
                'keywords' => ['install', 'software', 'application', 'program', 'setup'],
                'is_billable' => true,
            ],
            [
                'name' => 'Backup/Recovery',
                'description' => 'Backup data or recover from backup',
                'work_type' => 'backup_recovery',
                'default_hours' => 1.5,
                'category' => 'Data Management',
                'keywords' => ['backup', 'recovery', 'restore', 'data', 'file'],
                'is_billable' => true,
            ],
            [
                'name' => 'Security Incident',
                'description' => 'Respond to security threats and malware',
                'work_type' => 'security_support',
                'default_hours' => 2.0,
                'category' => 'Security',
                'keywords' => ['virus', 'malware', 'security', 'threat', 'infected'],
                'is_billable' => true,
            ],
            [
                'name' => 'System Maintenance',
                'description' => 'Routine system maintenance and updates',
                'work_type' => 'maintenance',
                'default_hours' => 2.0,
                'category' => 'Maintenance',
                'keywords' => ['maintenance', 'update', 'patch', 'system', 'routine'],
                'is_billable' => true,
            ],
        ];

        foreach ($defaults as $template) {
            self::create(array_merge($template, ['company_id' => $companyId]));
        }
    }

    /**
     * Get available work types
     */
    public static function getAvailableWorkTypes(): array
    {
        return [
            'general_support' => 'General Support',
            'account_management' => 'Account Management',
            'email_support' => 'Email Support',
            'network_support' => 'Network Support',
            'software_support' => 'Software Support',
            'backup_recovery' => 'Backup/Recovery',
            'security_support' => 'Security Support',
            'maintenance' => 'System Maintenance',
            'troubleshooting' => 'Troubleshooting',
            'consultation' => 'Consultation',
            'project_work' => 'Project Work',
            'emergency_support' => 'Emergency Support',
        ];
    }

    /**
     * Get available categories
     */
    public static function getAvailableCategories(): array
    {
        return [
            'Account Support',
            'Email Support',
            'Network Support',
            'Software Support',
            'Data Management',
            'Security',
            'Maintenance',
            'Hardware Support',
            'Project Work',
            'Consultation',
        ];
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'work_type' => 'required|string|max:100',
            'default_hours' => 'required|numeric|min:0.01|max:24',
            'category' => 'nullable|string|max:100',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:50',
            'is_active' => 'boolean',
            'is_billable' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
