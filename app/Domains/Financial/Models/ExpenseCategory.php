<?php

namespace App\Domains\Financial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'code',
        'color',
        'is_active',
        'requires_approval',
        'approval_limit',
        'is_billable_default',
        'markup_percentage_default',
        'sort_order',
    ];

    protected $casts = [
        'approval_limit' => 'decimal:2',
        'markup_percentage_default' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'is_billable_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get expenses in this category
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for categories ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get default expense categories
     */
    public static function getDefaultCategories()
    {
        return [
            [
                'name' => 'Office Supplies',
                'description' => 'General office supplies and materials',
                'code' => 'OFFICE',
                'color' => '#3B82F6',
                'requires_approval' => false,
                'approval_limit' => 100.00,
                'is_billable_default' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Travel',
                'description' => 'Business travel expenses',
                'code' => 'TRAVEL',
                'color' => '#10B981',
                'requires_approval' => true,
                'approval_limit' => 500.00,
                'is_billable_default' => true,
                'markup_percentage_default' => 10.00,
                'sort_order' => 2,
            ],
            [
                'name' => 'Meals & Entertainment',
                'description' => 'Business meals and entertainment',
                'code' => 'MEALS',
                'color' => '#F59E0B',
                'requires_approval' => true,
                'approval_limit' => 200.00,
                'is_billable_default' => true,
                'markup_percentage_default' => 15.00,
                'sort_order' => 3,
            ],
            [
                'name' => 'Software & Subscriptions',
                'description' => 'Software licenses and subscriptions',
                'code' => 'SOFTWARE',
                'color' => '#8B5CF6',
                'requires_approval' => true,
                'approval_limit' => 300.00,
                'is_billable_default' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Equipment',
                'description' => 'Hardware and equipment purchases',
                'code' => 'EQUIPMENT',
                'color' => '#EF4444',
                'requires_approval' => true,
                'approval_limit' => 1000.00,
                'is_billable_default' => true,
                'markup_percentage_default' => 20.00,
                'sort_order' => 5,
            ],
            [
                'name' => 'Professional Services',
                'description' => 'External professional services',
                'code' => 'SERVICES',
                'color' => '#06B6D4',
                'requires_approval' => true,
                'approval_limit' => 2000.00,
                'is_billable_default' => true,
                'markup_percentage_default' => 25.00,
                'sort_order' => 6,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing and advertising expenses',
                'code' => 'MARKETING',
                'color' => '#EC4899',
                'requires_approval' => true,
                'approval_limit' => 500.00,
                'is_billable_default' => false,
                'sort_order' => 7,
            ],
            [
                'name' => 'Miscellaneous',
                'description' => 'Other business expenses',
                'code' => 'MISC',
                'color' => '#6B7280',
                'requires_approval' => true,
                'approval_limit' => 100.00,
                'is_billable_default' => false,
                'sort_order' => 8,
            ],
        ];
    }

    /**
     * Check if expense amount requires approval for this category
     */
    public function requiresApprovalForAmount(float $amount): bool
    {
        if (!$this->requires_approval) {
            return false;
        }

        return $amount > $this->approval_limit;
    }
}