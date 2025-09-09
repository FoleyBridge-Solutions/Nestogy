<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ContractDetailConfiguration Model
 * 
 * Configures how contract detail views are displayed including
 * sections, tabs, sidebar widgets, and related data.
 */
class ContractDetailConfiguration extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'contract_detail_configurations';

    protected $fillable = [
        'company_id',
        'contract_type_slug',
        'sections_config',
        'tabs_config',
        'sidebar_config',
        'actions_config',
        'related_data_config',
        'timeline_config',
        'show_timeline',
        'show_related_records',
        'is_active',
    ];

    protected $casts = [
        'sections_config' => 'array',
        'tabs_config' => 'array',
        'sidebar_config' => 'array',
        'actions_config' => 'array',
        'related_data_config' => 'array',
        'timeline_config' => 'array',
        'show_timeline' => 'boolean',
        'show_related_records' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get sections configuration
     */
    public function getSectionsConfig(): array
    {
        return $this->sections_config ?? [];
    }

    /**
     * Get tabs configuration
     */
    public function getTabsConfig(): array
    {
        return $this->tabs_config ?? [];
    }

    /**
     * Get sidebar configuration
     */
    public function getSidebarConfig(): array
    {
        return $this->sidebar_config ?? [];
    }

    /**
     * Get actions configuration
     */
    public function getActionsConfig(): array
    {
        return $this->actions_config ?? [];
    }

    /**
     * Get related data configuration
     */
    public function getRelatedDataConfig(): array
    {
        return $this->related_data_config ?? [];
    }

    /**
     * Get timeline configuration
     */
    public function getTimelineConfig(): array
    {
        return $this->timeline_config ?? [];
    }

    /**
     * Scope to get active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}