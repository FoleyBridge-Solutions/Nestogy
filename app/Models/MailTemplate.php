<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    use HasFactory, BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'category',
        'subject',
        'html_template',
        'text_template',
        'available_variables',
        'default_data',
        'is_active',
        'is_system',
        'settings',
    ];
    
    protected $casts = [
        'available_variables' => 'array',
        'default_data' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];
    
    const CATEGORY_INVOICE = 'invoice';
    const CATEGORY_NOTIFICATION = 'notification';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_PORTAL = 'portal';
    const CATEGORY_SUPPORT = 'support';
    const CATEGORY_REPORT = 'report';
    
    /**
     * Get all categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_INVOICE => 'Invoice',
            self::CATEGORY_NOTIFICATION => 'Notification',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_SYSTEM => 'System',
            self::CATEGORY_PORTAL => 'Portal',
            self::CATEGORY_SUPPORT => 'Support',
            self::CATEGORY_REPORT => 'Report',
        ];
    }
    
    /**
     * Scope to active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope to category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}