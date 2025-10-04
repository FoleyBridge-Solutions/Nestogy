<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Category Model
 *
 * Represents hierarchical categories for organizing various entities
 * like expenses, income, tickets, products, etc.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string|null $color
 * @property string|null $icon
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'color',
        'icon',
        'parent_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'parent_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Category types enumeration
     */
    const TYPE_EXPENSE = 'expense';

    const TYPE_INCOME = 'income';

    const TYPE_TICKET = 'ticket';

    const TYPE_PRODUCT = 'product';

    const TYPE_INVOICE = 'invoice';

    const TYPE_QUOTE = 'quote';

    const TYPE_RECURRING = 'recurring';

    const TYPE_ASSET = 'asset';

    /**
     * Type labels mapping
     */
    const TYPE_LABELS = [
        self::TYPE_EXPENSE => 'Expense',
        self::TYPE_INCOME => 'Income',
        self::TYPE_TICKET => 'Ticket',
        self::TYPE_PRODUCT => 'Product',
        self::TYPE_INVOICE => 'Invoice',
        self::TYPE_QUOTE => 'Quote',
        self::TYPE_RECURRING => 'Recurring',
        self::TYPE_ASSET => 'Asset',
    ];

    /**
     * Default colors for different types
     */
    const DEFAULT_COLORS = [
        self::TYPE_EXPENSE => '#dc3545',
        self::TYPE_INCOME => '#28a745',
        self::TYPE_TICKET => '#007bff',
        self::TYPE_PRODUCT => '#6f42c1',
        self::TYPE_INVOICE => '#fd7e14',
        self::TYPE_QUOTE => '#20c997',
        self::TYPE_RECURRING => '#6c757d',
        self::TYPE_ASSET => '#17a2b8',
    ];

    /**
     * Get the company that owns the category.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get expenses in this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get products in this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get invoices in this category.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get quotes in this category.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get recurring invoices in this category.
     */
    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(Recurring::class);
    }

    /**
     * Get invoice items in this category.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Unknown';
    }

    /**
     * Get the category color or default.
     */
    public function getColor(): string
    {
        return $this->color ?: (self::DEFAULT_COLORS[$this->type] ?? '#6c757d');
    }

    /**
     * Get the category icon or default.
     */
    public function getIcon(): string
    {
        return $this->icon ?: 'fas fa-folder';
    }

    /**
     * Check if category is a root category.
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if category has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if category is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Get the full category path.
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get category depth level.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Get all ancestor categories.
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;

        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent;
        }

        return array_reverse($ancestors);
    }

    /**
     * Get category tree starting from this category.
     */
    public function getTree(): array
    {
        return $this->buildTree($this);
    }

    /**
     * Build category tree recursively.
     */
    private function buildTree($category): array
    {
        $tree = [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
            'color' => $category->getColor(),
            'icon' => $category->getIcon(),
            'children' => [],
        ];

        foreach ($category->children as $child) {
            $tree['children'][] = $this->buildTree($child);
        }

        return $tree;
    }

    /**
     * Scope to get only root categories.
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get categories by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get categories with children.
     */
    public function scopeWithChildren($query)
    {
        return $query->has('children');
    }

    /**
     * Scope to get leaf categories (no children).
     */
    public function scopeLeaves($query)
    {
        return $query->doesntHave('children');
    }

    /**
     * Scope to search categories.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', '%'.$search.'%');
    }

    /**
     * Scope to get categories ordered by hierarchy.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('parent_id')->orderBy('name');
    }

    /**
     * Get validation rules for category creation.
     */
    public static function getValidationRules(): array
    {
        $types = implode(',', array_keys(self::TYPE_LABELS));

        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:'.$types,
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ];
    }

    /**
     * Get validation rules for category update.
     */
    public static function getUpdateValidationRules(int $categoryId): array
    {
        $rules = self::getValidationRules();

        // Prevent circular references
        $rules['parent_id'] = 'nullable|integer|exists:categories,id|not_in:'.$categoryId;

        return $rules;
    }

    /**
     * Get available types for selection.
     */
    public static function getAvailableTypes(): array
    {
        return self::TYPE_LABELS;
    }

    /**
     * Get category tree for a specific type.
     */
    public static function getTreeByType(string $type): array
    {
        $roots = static::byType($type)->roots()->with('descendants')->get();
        $tree = [];

        foreach ($roots as $root) {
            $tree[] = $root->getTree();
        }

        return $tree;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default color if not provided
        static::creating(function ($category) {
            if (empty($category->color)) {
                $category->color = self::DEFAULT_COLORS[$category->type] ?? '#6c757d';
            }
        });

        // Prevent deletion of categories with children or associated records
        static::deleting(function ($category) {
            if ($category->hasChildren()) {
                throw new \Exception('Cannot delete category that has child categories');
            }

            // Check for associated records based on type
            $hasRecords = false;
            switch ($category->type) {
                case self::TYPE_EXPENSE:
                    $hasRecords = $category->expenses()->exists();
                    break;
                case self::TYPE_PRODUCT:
                    $hasRecords = $category->products()->exists();
                    break;
                case self::TYPE_INVOICE:
                    $hasRecords = $category->invoices()->exists();
                    break;
                case self::TYPE_QUOTE:
                    $hasRecords = $category->quotes()->exists();
                    break;
                case self::TYPE_RECURRING:
                    $hasRecords = $category->recurringInvoices()->exists();
                    break;
            }

            if ($hasRecords) {
                throw new \Exception('Cannot delete category that has associated records');
            }
        });
    }
}
