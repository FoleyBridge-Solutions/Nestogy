<?php

namespace App\Domains\Ticket\Models;

use App\Traits\BelongsToCompany;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Recurring Ticket Model
 * 
 * Represents automated ticket creation schedules based on templates
 * with flexible frequency configurations and occurrence limits.
 */
class RecurringTicket extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'template_id',
        'client_id',
        'name',
        'frequency',
        'interval_value',
        'frequency_config',
        'next_run_date',
        'last_run_date',
        'end_date',
        'max_occurrences',
        'occurrences_count',
        'is_active',
        'template_overrides',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'template_id' => 'integer',
        'client_id' => 'integer',
        'interval_value' => 'integer',
        'frequency_config' => 'array',
        'next_run_date' => 'date',
        'last_run_date' => 'date',
        'end_date' => 'date',
        'max_occurrences' => 'integer',
        'occurrences_count' => 'integer',
        'is_active' => 'boolean',
        'template_overrides' => 'array',
    ];

    // ===========================================
    // FREQUENCY CONSTANTS
    // ===========================================

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_YEARLY = 'yearly';

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function template(): BelongsTo
    {
        return $this->belongsTo(TicketTemplate::class, 'template_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function generatedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'recurring_ticket_id');
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if this recurring ticket should run today
     */
    public function shouldRun(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if we've reached the end date
        if ($this->end_date && now()->toDateString() > $this->end_date->toDateString()) {
            return false;
        }

        // Check if we've reached max occurrences
        if ($this->max_occurrences && $this->occurrences_count >= $this->max_occurrences) {
            return false;
        }

        // Check if it's time to run
        return now()->toDateString() >= $this->next_run_date->toDateString();
    }

    /**
     * Calculate the next run date based on frequency
     */
    public function calculateNextRunDate(): Carbon
    {
        $base = $this->last_run_date ?? $this->next_run_date ?? now();
        $interval = $this->interval_value ?? 1;

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                return $base->copy()->addDays($interval);

            case self::FREQUENCY_WEEKLY:
                return $base->copy()->addWeeks($interval);

            case self::FREQUENCY_MONTHLY:
                return $this->calculateMonthlyNextRun($base, $interval);

            case self::FREQUENCY_YEARLY:
                return $base->copy()->addYears($interval);

            default:
                return $base->copy()->addDays($interval);
        }
    }

    /**
     * Handle complex monthly recurring logic
     */
    private function calculateMonthlyNextRun(Carbon $base, int $interval): Carbon
    {
        $config = $this->frequency_config ?? [];

        // If specific day of month is configured
        if (isset($config['day_of_month'])) {
            $nextMonth = $base->copy()->addMonths($interval);
            $dayOfMonth = min($config['day_of_month'], $nextMonth->daysInMonth);
            return $nextMonth->day($dayOfMonth);
        }

        // If specific week and weekday (e.g., "second Tuesday")
        if (isset($config['week']) && isset($config['weekday'])) {
            $nextMonth = $base->copy()->addMonths($interval)->startOfMonth();
            return $this->getNthWeekdayOfMonth($nextMonth, $config['week'], $config['weekday']);
        }

        // Default: same day of month
        return $base->copy()->addMonths($interval);
    }

    /**
     * Get nth weekday of month (e.g., 2nd Tuesday)
     */
    private function getNthWeekdayOfMonth(Carbon $month, int $week, int $weekday): Carbon
    {
        $firstDay = $month->copy()->startOfMonth();
        $firstWeekday = $firstDay->copy()->next($weekday);
        
        return $firstWeekday->addWeeks($week - 1);
    }

    /**
     * Generate a ticket from this recurring schedule
     */
    public function generateTicket(): Ticket
    {
        if (!$this->shouldRun()) {
            throw new \Exception('Recurring ticket is not scheduled to run.');
        }

        // Get template data
        $templateData = [];
        if ($this->template) {
            $templateData = [
                'subject' => $this->template->subject_template,
                'details' => $this->template->body_template,
                'priority' => $this->template->priority,
                'category' => $this->template->category,
                'assigned_to' => $this->template->default_assignee_id,
            ];
        }

        // Apply overrides
        if ($this->template_overrides) {
            $templateData = array_merge($templateData, $this->template_overrides);
        }

        // Create the ticket
        $ticket = Ticket::create(array_merge($templateData, [
            'client_id' => $this->client_id,
            'template_id' => $this->template_id,
            'recurring_ticket_id' => $this->id,
            'company_id' => $this->company_id,
        ]));

        // Update run tracking
        $this->update([
            'last_run_date' => now()->toDateString(),
            'next_run_date' => $this->calculateNextRunDate(),
            'occurrences_count' => $this->occurrences_count + 1,
        ]);

        // Auto-deactivate if we've reached limits
        if ($this->max_occurrences && $this->occurrences_count >= $this->max_occurrences) {
            $this->update(['is_active' => false]);
        }

        if ($this->end_date && now()->toDateString() >= $this->end_date->toDateString()) {
            $this->update(['is_active' => false]);
        }

        return $ticket;
    }

    /**
     * Preview upcoming ticket generation dates
     */
    public function previewUpcomingRuns(int $count = 10): array
    {
        $dates = [];
        $currentDate = $this->next_run_date->copy();
        $occurrences = $this->occurrences_count;

        for ($i = 0; $i < $count; $i++) {
            // Check limits
            if ($this->end_date && $currentDate->gt($this->end_date)) {
                break;
            }
            
            if ($this->max_occurrences && $occurrences >= $this->max_occurrences) {
                break;
            }

            $dates[] = $currentDate->toDateString();
            
            // Calculate next date
            switch ($this->frequency) {
                case self::FREQUENCY_DAILY:
                    $currentDate->addDays($this->interval_value);
                    break;
                case self::FREQUENCY_WEEKLY:
                    $currentDate->addWeeks($this->interval_value);
                    break;
                case self::FREQUENCY_MONTHLY:
                    $currentDate->addMonths($this->interval_value);
                    break;
                case self::FREQUENCY_YEARLY:
                    $currentDate->addYears($this->interval_value);
                    break;
            }
            
            $occurrences++;
        }

        return $dates;
    }

    /**
     * Pause this recurring schedule
     */
    public function pause(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Resume this recurring schedule
     */
    public function resume(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Get schedule summary for display
     */
    public function getScheduleSummary(): string
    {
        $summary = "Every {$this->interval_value} ";
        
        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                $summary .= $this->interval_value === 1 ? 'day' : 'days';
                break;
            case self::FREQUENCY_WEEKLY:
                $summary .= $this->interval_value === 1 ? 'week' : 'weeks';
                break;
            case self::FREQUENCY_MONTHLY:
                $summary .= $this->interval_value === 1 ? 'month' : 'months';
                break;
            case self::FREQUENCY_YEARLY:
                $summary .= $this->interval_value === 1 ? 'year' : 'years';
                break;
        }

        // Add end conditions
        $conditions = [];
        if ($this->max_occurrences) {
            $remaining = $this->max_occurrences - $this->occurrences_count;
            $conditions[] = "{$remaining} occurrences remaining";
        }
        
        if ($this->end_date) {
            $conditions[] = "until {$this->end_date->format('M j, Y')}";
        }

        if (!empty($conditions)) {
            $summary .= ' (' . implode(', ', $conditions) . ')';
        }

        return $summary;
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueToRun($query)
    {
        return $query->where('is_active', true)
            ->where('next_run_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('max_occurrences')
                  ->orWhereRaw('occurrences_count < max_occurrences');
            });
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getAvailableFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_YEARLY => 'Yearly',
        ];
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    public static function getValidationRules(): array
    {
        return [
            'template_id' => 'required|exists:ticket_templates,id',
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval_value' => 'required|integer|min:1|max:365',
            'frequency_config' => 'nullable|array',
            'next_run_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:next_run_date',
            'max_occurrences' => 'nullable|integer|min:1|max:9999',
            'is_active' => 'boolean',
            'template_overrides' => 'nullable|array',
        ];
    }
}