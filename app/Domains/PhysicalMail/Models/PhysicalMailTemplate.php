<?php

namespace App\Domains\PhysicalMail\Models;

use App\Models\Traits\HasPostGridIntegration;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalMailTemplate extends Model
{
    use HasFactory, HasPostGridIntegration, HasUuids;

    protected $table = 'physical_mail_templates';

    protected $fillable = [
        'postgrid_id',
        'name',
        'type',
        'content',
        'description',
        'variables',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get PostGrid resource name
     */
    protected function getPostGridResource(): string
    {
        return 'templates';
    }

    /**
     * Convert to PostGrid API format
     */
    public function toPostGridArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'html' => $this->content,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Extract variables from HTML content
     */
    public function extractVariables(): array
    {
        if (! $this->content) {
            return [];
        }

        preg_match_all('/\{\{([^}]+)\}\}/', $this->content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Update variables from content
     */
    public function updateVariables(): void
    {
        $this->variables = $this->extractVariables();
        $this->save();
    }
}
