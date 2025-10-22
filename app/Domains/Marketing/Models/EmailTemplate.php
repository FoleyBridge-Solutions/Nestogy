<?php

namespace App\Domains\Marketing\Models;

use App\Domains\Core\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'subject',
        'body_html',
        'body_text',
        'category',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_TRANSACTIONAL = 'transactional';
    const CATEGORY_FOLLOW_UP = 'follow_up';
    const CATEGORY_ONBOARDING = 'onboarding';
    const CATEGORY_NOTIFICATION = 'notification';

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_TRANSACTIONAL => 'Transactional',
            self::CATEGORY_FOLLOW_UP => 'Follow Up',
            self::CATEGORY_ONBOARDING => 'Onboarding',
            self::CATEGORY_NOTIFICATION => 'Notification',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns()
    {
        return $this->hasMany(MarketingCampaign::class, 'template_id');
    }

    public function getUsageCountAttribute(): int
    {
        return $this->campaigns()->count();
    }
}
