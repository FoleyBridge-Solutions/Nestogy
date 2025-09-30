<?php

namespace App\Domains\Lead\Models;

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'lead_source_id',
        'assigned_user_id',
        'client_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company_name',
        'title',
        'website',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'status',
        'priority',
        'industry',
        'company_size',
        'estimated_value',
        'notes',
        'custom_fields',
        'total_score',
        'demographic_score',
        'behavioral_score',
        'fit_score',
        'urgency_score',
        'last_scored_at',
        'first_contact_date',
        'last_contact_date',
        'qualified_at',
        'converted_at',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'lead_source_id' => 'integer',
        'assigned_user_id' => 'integer',
        'client_id' => 'integer',
        'company_size' => 'integer',
        'estimated_value' => 'decimal:2',
        'custom_fields' => 'array',
        'total_score' => 'integer',
        'demographic_score' => 'integer',
        'behavioral_score' => 'integer',
        'fit_score' => 'integer',
        'urgency_score' => 'integer',
        'last_scored_at' => 'datetime',
        'first_contact_date' => 'datetime',
        'last_contact_date' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_NEW = 'new';

    const STATUS_CONTACTED = 'contacted';

    const STATUS_QUALIFIED = 'qualified';

    const STATUS_UNQUALIFIED = 'unqualified';

    const STATUS_NURTURING = 'nurturing';

    const STATUS_CONVERTED = 'converted';

    const STATUS_LOST = 'lost';

    // Priority constants
    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    // Scoring constants
    const SCORE_EXCELLENT = 80;

    const SCORE_GOOD = 60;

    const SCORE_FAIR = 40;

    const SCORE_POOR = 20;

    /**
     * Get the lead source.
     */
    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    /**
     * Get the assigned user.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the converted client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the lead activities.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('activity_date', 'desc');
    }

    /**
     * Get recent activities.
     */
    public function recentActivities(): HasMany
    {
        return $this->activities()->limit(10);
    }

    /**
     * Get the full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get the display name (full name or company name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->company_name ?: $this->email;
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if lead is qualified.
     */
    public function isQualified(): bool
    {
        return $this->status === self::STATUS_QUALIFIED;
    }

    /**
     * Check if lead is converted.
     */
    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED && ! is_null($this->client_id);
    }

    /**
     * Check if lead is high priority.
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Check if lead has excellent score.
     */
    public function hasExcellentScore(): bool
    {
        return $this->total_score >= self::SCORE_EXCELLENT;
    }

    /**
     * Check if lead has good score.
     */
    public function hasGoodScore(): bool
    {
        return $this->total_score >= self::SCORE_GOOD;
    }

    /**
     * Get days since first contact.
     */
    public function getDaysSinceFirstContactAttribute(): ?int
    {
        if (! $this->first_contact_date) {
            return null;
        }

        return $this->first_contact_date->diffInDays(now());
    }

    /**
     * Get days since last contact.
     */
    public function getDaysSinceLastContactAttribute(): ?int
    {
        if (! $this->last_contact_date) {
            return null;
        }

        return $this->last_contact_date->diffInDays(now());
    }

    /**
     * Get the score category.
     */
    public function getScoreCategoryAttribute(): string
    {
        if ($this->total_score >= self::SCORE_EXCELLENT) {
            return 'Excellent';
        } elseif ($this->total_score >= self::SCORE_GOOD) {
            return 'Good';
        } elseif ($this->total_score >= self::SCORE_FAIR) {
            return 'Fair';
        }

        return 'Poor';
    }

    /**
     * Get the score color for display.
     */
    public function getScoreColorAttribute(): string
    {
        if ($this->total_score >= self::SCORE_EXCELLENT) {
            return 'green';
        } elseif ($this->total_score >= self::SCORE_GOOD) {
            return 'blue';
        } elseif ($this->total_score >= self::SCORE_FAIR) {
            return 'yellow';
        }

        return 'red';
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_UNQUALIFIED => 'Unqualified',
            self::STATUS_NURTURING => 'Nurturing',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_LOST => 'Lost',
        ];
    }

    /**
     * Get available priorities.
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * Scope to get qualified leads.
     */
    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope to get new leads.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope to get high priority leads.
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope to get high-scoring leads.
     */
    public function scopeHighScore($query, int $minScore = self::SCORE_GOOD)
    {
        return $query->where('total_score', '>=', $minScore);
    }

    /**
     * Scope to get leads assigned to user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Scope to get unassigned leads.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_user_id');
    }

    /**
     * Scope to search leads.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', '%'.$search.'%')
                ->orWhere('last_name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('company_name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%');
        });
    }

    /**
     * Scope to filter by source.
     */
    public function scopeFromSource($query, int $sourceId)
    {
        return $query->where('lead_source_id', $sourceId);
    }

    /**
     * Scope to filter by industry.
     */
    public function scopeInIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Mark lead as contacted.
     */
    public function markAsContacted(): void
    {
        $this->update([
            'status' => self::STATUS_CONTACTED,
            'last_contact_date' => now(),
            'first_contact_date' => $this->first_contact_date ?? now(),
        ]);
    }

    /**
     * Mark lead as qualified.
     */
    public function markAsQualified(): void
    {
        $this->update([
            'status' => self::STATUS_QUALIFIED,
            'qualified_at' => now(),
        ]);
    }

    /**
     * Convert lead to client.
     */
    public function convertToClient(array $clientData = []): Client
    {
        // Create client from lead data
        $defaultClientData = [
            'company_id' => $this->company_id,
            'name' => $this->full_name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'website' => $this->website,
            'notes' => $this->notes,
            'lead' => false, // Mark as converted client
            'status' => 'active',
        ];

        $clientData = array_merge($defaultClientData, $clientData);
        $client = Client::create($clientData);

        // Create primary contact from lead
        $contact = Contact::create([
            'company_id' => $this->company_id,
            'client_id' => $client->id,
            'name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'title' => $this->title,
            'primary' => true,
        ]);

        // Update lead as converted
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'client_id' => $client->id,
            'converted_at' => now(),
        ]);

        return $client;
    }

    /**
     * Update lead score.
     */
    public function updateScore(array $scores): void
    {
        $totalScore = array_sum($scores);

        $this->update(array_merge($scores, [
            'total_score' => $totalScore,
            'last_scored_at' => now(),
        ]));
    }

    /**
     * Add activity to lead.
     */
    public function addActivity(string $type, ?string $subject = null, ?string $description = null, array $metadata = []): LeadActivity
    {
        return $this->activities()->create([
            'type' => $type,
            'subject' => $subject,
            'description' => $description,
            'metadata' => $metadata,
            'activity_date' => now(),
        ]);
    }

    /**
     * Get the latest activity.
     */
    public function getLatestActivityAttribute(): ?LeadActivity
    {
        return $this->activities()->first();
    }

    /**
     * Check if lead needs follow-up.
     */
    public function needsFollowUp(int $daysSinceLastContact = 7): bool
    {
        if (! $this->last_contact_date) {
            return true;
        }

        return $this->last_contact_date->diffInDays(now()) >= $daysSinceLastContact;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {
            // Auto-assign lead source if not provided
            if (! $lead->lead_source_id) {
                $defaultSource = LeadSource::where('company_id', $lead->company_id)
                    ->where('type', 'manual')
                    ->where('is_active', true)
                    ->first();

                if ($defaultSource) {
                    $lead->lead_source_id = $defaultSource->id;
                }
            }
        });

        static::created(function ($lead) {
            // Add initial activity
            $lead->addActivity(
                'lead_created',
                'Lead Created',
                'Lead was created in the system'
            );
        });
    }
}
