<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\HasArchive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use BelongsToCompany, HasArchive, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'tags';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'color',
        'icon',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * Tag type constants
     */
    const TYPE_CLIENT = 1;

    const TYPE_TICKET = 2;

    const TYPE_ASSET = 3;

    const TYPE_DOCUMENT = 4;

    /**
     * Tag type labels
     */
    const TYPE_LABELS = [
        self::TYPE_CLIENT => 'Client',
        self::TYPE_TICKET => 'Ticket',
        self::TYPE_ASSET => 'Asset',
        self::TYPE_DOCUMENT => 'Document',
    ];

    /**
     * Get the company that owns the tag.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the clients that have this tag.
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_tags', 'tag_id', 'client_id');
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Unknown';
    }

    /**
     * Scope to get tags by type.
     */
    public function scopeOfType($query, int $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get client tags.
     */
    public function scopeClientTags($query)
    {
        return $query->where('type', self::TYPE_CLIENT);
    }

    /**
     * Get validation rules for tag creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|integer|in:'.implode(',', array_keys(self::TYPE_LABELS)),
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get available tag types.
     */
    public static function getAvailableTypes(): array
    {
        return self::TYPE_LABELS;
    }
}
