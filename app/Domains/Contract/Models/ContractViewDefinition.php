<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ContractViewDefinition Model
 *
 * Defines how contract views are configured for different contract types.
 * Controls layout, fields display, and available actions.
 */
class ContractViewDefinition extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'contract_view_definitions';

    protected $fillable = [
        'company_id',
        'contract_type_slug',
        'view_type',
        'layout_config',
        'fields_config',
        'actions_config',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'fields_config' => 'array',
        'actions_config' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * View type constants
     */
    const VIEW_INDEX = 'index';

    const VIEW_SHOW = 'show';

    const VIEW_EDIT = 'edit';

    const VIEW_CREATE = 'create';

    /**
     * Get layout configuration with defaults
     */
    public function getLayoutConfig(): array
    {
        return array_merge([
            'theme' => 'default',
            'sidebar' => true,
            'breadcrumbs' => true,
            'actions_bar' => true,
        ], $this->layout_config ?? []);
    }

    /**
     * Get fields configuration
     */
    public function getFieldsConfig(): array
    {
        return $this->fields_config ?? [];
    }

    /**
     * Get actions configuration
     */
    public function getActionsConfig(): array
    {
        return $this->actions_config ?? [];
    }

    /**
     * Scope to get active views
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if user has permission to access this view
     */
    public function hasPermission($user): bool
    {
        if (empty($this->permissions)) {
            return true;
        }

        foreach ($this->permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
