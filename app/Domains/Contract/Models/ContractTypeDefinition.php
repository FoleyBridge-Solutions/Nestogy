<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * ContractTypeDefinition Model
 *
 * Defines custom contract types per company, replacing hardcoded constants.
 * Each company can define their own contract types with custom configuration.
 */
class ContractTypeDefinition extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'contract_type_definitions';

    protected $fillable = [
        'company_id',
        'slug',
        'name',
        'description',
        'icon',
        'color',
        'config',
        'default_values',
        'business_rules',
        'permissions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'default_values' => 'array',
        'business_rules' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get contracts of this type
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_type', 'slug');
    }

    /**
     * Get form mappings for this contract type
     */
    public function formMappings(): HasMany
    {
        return $this->hasMany(ContractTypeFormMapping::class, 'contract_type_slug', 'slug');
    }

    /**
     * Scope to get active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Generate slug from name
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Check if user has permission to create this contract type
     */
    public function hasCreatePermission($user): bool
    {
        if (empty($this->permissions['create'])) {
            return true;
        }

        foreach ($this->permissions['create'] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get default field values for this contract type
     */
    public function getDefaultValues(): array
    {
        return $this->default_values ?? [];
    }

    /**
     * Get business rules for this contract type
     */
    public function getBusinessRules(): array
    {
        return $this->business_rules ?? [];
    }
}
