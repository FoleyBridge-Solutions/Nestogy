<?php

namespace App\Domains\HR\Models;
use App\Domains\Company\Models\Company;

use App\Domains\Core\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Silber\Bouncer\Database\Role;

class HRSettingsOverride extends Model
{
    use BelongsToCompany;

    protected $table = 'hr_settings_overrides';

    protected $fillable = [
        'company_id',
        'overridable_type',
        'overridable_id',
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'setting_value' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function overridable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getForRole(int $companyId, int $roleId): array
    {
        return static::where('company_id', $companyId)
            ->where('overridable_type', Role::class)
            ->where('overridable_id', $roleId)
            ->get()
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    public static function getForUser(int $companyId, int $userId): array
    {
        return static::where('company_id', $companyId)
            ->where('overridable_type', User::class)
            ->where('overridable_id', $userId)
            ->get()
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    public static function setOverride(int $companyId, string $type, int $id, string $key, mixed $value): void
    {
        static::updateOrCreate(
            [
                'company_id' => $companyId,
                'overridable_type' => $type,
                'overridable_id' => $id,
                'setting_key' => $key,
            ],
            ['setting_value' => $value]
        );
    }

    public static function removeOverride(int $companyId, string $type, int $id, ?string $key = null): void
    {
        $query = static::where('company_id', $companyId)
            ->where('overridable_type', $type)
            ->where('overridable_id', $id);

        if ($key) {
            $query->where('setting_key', $key);
        }

        $query->delete();
    }

    public static function hasOverrides(int $companyId, string $type, int $id): bool
    {
        return static::where('company_id', $companyId)
            ->where('overridable_type', $type)
            ->where('overridable_id', $id)
            ->exists();
    }
}
