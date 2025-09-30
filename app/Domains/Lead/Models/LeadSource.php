<?php

namespace App\Domains\Lead\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadSource extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'description',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // Source type constants
    const TYPE_MANUAL = 'manual';

    const TYPE_WEBSITE = 'website';

    const TYPE_REFERRAL = 'referral';

    const TYPE_CAMPAIGN = 'campaign';

    const TYPE_IMPORT = 'import';

    const TYPE_SOCIAL = 'social';

    const TYPE_EVENT = 'event';

    const TYPE_COLD_OUTREACH = 'cold_outreach';

    /**
     * Get the leads from this source.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get active leads from this source.
     */
    public function activeLeads(): HasMany
    {
        return $this->leads()->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST]);
    }

    /**
     * Get qualified leads from this source.
     */
    public function qualifiedLeads(): HasMany
    {
        return $this->leads()->where('status', Lead::STATUS_QUALIFIED);
    }

    /**
     * Get converted leads from this source.
     */
    public function convertedLeads(): HasMany
    {
        return $this->leads()->where('status', Lead::STATUS_CONVERTED);
    }

    /**
     * Get available source types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_MANUAL => 'Manual Entry',
            self::TYPE_WEBSITE => 'Website Form',
            self::TYPE_REFERRAL => 'Referral',
            self::TYPE_CAMPAIGN => 'Marketing Campaign',
            self::TYPE_IMPORT => 'Data Import',
            self::TYPE_SOCIAL => 'Social Media',
            self::TYPE_EVENT => 'Event/Trade Show',
            self::TYPE_COLD_OUTREACH => 'Cold Outreach',
        ];
    }

    /**
     * Get conversion rate for this source.
     */
    public function getConversionRateAttribute(): float
    {
        $totalLeads = $this->leads()->count();
        $convertedLeads = $this->convertedLeads()->count();

        if ($totalLeads === 0) {
            return 0;
        }

        return ($convertedLeads / $totalLeads) * 100;
    }

    /**
     * Get qualification rate for this source.
     */
    public function getQualificationRateAttribute(): float
    {
        $totalLeads = $this->leads()->count();
        $qualifiedLeads = $this->qualifiedLeads()->count();

        if ($totalLeads === 0) {
            return 0;
        }

        return ($qualifiedLeads / $totalLeads) * 100;
    }

    /**
     * Get average lead score for this source.
     */
    public function getAverageScoreAttribute(): float
    {
        return $this->leads()->avg('total_score') ?? 0;
    }

    /**
     * Get total leads count.
     */
    public function getLeadsCountAttribute(): int
    {
        return $this->leads()->count();
    }

    /**
     * Get leads this month.
     */
    public function getLeadsThisMonthAttribute(): int
    {
        return $this->leads()->whereMonth('created_at', now()->month)->count();
    }

    /**
     * Scope to get active sources.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Deactivate the source.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate the source.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
}
