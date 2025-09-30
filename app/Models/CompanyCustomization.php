<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyCustomization Model
 *
 * Stores company-specific customizations including colors, fonts, and branding.
 *
 * @property int $id
 * @property int $company_id
 * @property array $customizations
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompanyCustomization extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_customizations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'customizations',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'customizations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default color palette (blue theme)
     */
    const DEFAULT_COLORS = [
        'primary' => [
            '50' => '#eff6ff',
            '100' => '#dbeafe',
            '200' => '#bfdbfe',
            '300' => '#93c5fd',
            '400' => '#60a5fa',
            '500' => '#3b82f6',
            '600' => '#2563eb',
            '700' => '#1d4ed8',
            '800' => '#1e40af',
            '900' => '#1e3a8a',
        ],
        'secondary' => [
            '50' => '#f9fafb',
            '100' => '#f3f4f6',
            '200' => '#e5e7eb',
            '300' => '#d1d5db',
            '400' => '#9ca3af',
            '500' => '#6b7280',
            '600' => '#4b5563',
            '700' => '#374151',
            '800' => '#1f2937',
            '900' => '#111827',
        ],
    ];

    /**
     * Get the company that owns the customization.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get a color value with fallback to default.
     */
    public function getColor(string $path, ?string $fallback = null): string
    {
        $colors = $this->customizations['colors'] ?? [];
        $keys = explode('.', $path);

        $value = $colors;
        foreach ($keys as $key) {
            if (! isset($value[$key])) {
                return $fallback ?: data_get(self::DEFAULT_COLORS, $path, '#3b82f6');
            }
            $value = $value[$key];
        }

        return $value ?: ($fallback ?: data_get(self::DEFAULT_COLORS, $path, '#3b82f6'));
    }

    /**
     * Set colors in the customizations.
     */
    public function setColors(array $colors): void
    {
        $customizations = $this->customizations ?: [];
        $customizations['colors'] = array_merge($customizations['colors'] ?? [], $colors);
        $this->customizations = $customizations;
    }

    /**
     * Get all colors with defaults merged.
     */
    public function getColors(): array
    {
        $colors = $this->customizations['colors'] ?? [];

        return array_merge_recursive(self::DEFAULT_COLORS, $colors);
    }

    /**
     * Get CSS custom properties string.
     */
    public function getCssCustomProperties(): string
    {
        $colors = $this->getColors();
        $properties = [];

        foreach ($colors as $colorName => $shades) {
            foreach ($shades as $shade => $value) {
                $properties[] = "--{$colorName}-{$shade}: {$value}";
            }
        }

        return implode(";\n  ", $properties);
    }

    /**
     * Reset to default colors.
     */
    public function resetColors(): void
    {
        $customizations = $this->customizations ?: [];
        $customizations['colors'] = self::DEFAULT_COLORS;
        $this->customizations = $customizations;
    }

    /**
     * Apply a color preset.
     */
    public function applyColorPreset(string $preset): void
    {
        $presets = self::getColorPresets();

        if (isset($presets[$preset])) {
            $this->setColors($presets[$preset]);
        }
    }

    /**
     * Get available color presets.
     */
    public static function getColorPresets(): array
    {
        return [
            'blue' => self::DEFAULT_COLORS,
            'green' => [
                'primary' => [
                    '50' => '#ecfdf5',
                    '100' => '#d1fae5',
                    '200' => '#a7f3d0',
                    '300' => '#6ee7b7',
                    '400' => '#34d399',
                    '500' => '#10b981',
                    '600' => '#059669',
                    '700' => '#047857',
                    '800' => '#065f46',
                    '900' => '#064e3b',
                ],
                'secondary' => self::DEFAULT_COLORS['secondary'],
            ],
            'purple' => [
                'primary' => [
                    '50' => '#faf5ff',
                    '100' => '#f3e8ff',
                    '200' => '#e9d5ff',
                    '300' => '#d8b4fe',
                    '400' => '#c084fc',
                    '500' => '#a855f7',
                    '600' => '#9333ea',
                    '700' => '#7c3aed',
                    '800' => '#6b21a8',
                    '900' => '#581c87',
                ],
                'secondary' => self::DEFAULT_COLORS['secondary'],
            ],
            'red' => [
                'primary' => [
                    '50' => '#fef2f2',
                    '100' => '#fee2e2',
                    '200' => '#fecaca',
                    '300' => '#fca5a5',
                    '400' => '#f87171',
                    '500' => '#ef4444',
                    '600' => '#dc2626',
                    '700' => '#b91c1c',
                    '800' => '#991b1b',
                    '900' => '#7f1d1d',
                ],
                'secondary' => self::DEFAULT_COLORS['secondary'],
            ],
            'orange' => [
                'primary' => [
                    '50' => '#fff7ed',
                    '100' => '#ffedd5',
                    '200' => '#fed7aa',
                    '300' => '#fdba74',
                    '400' => '#fb923c',
                    '500' => '#f97316',
                    '600' => '#ea580c',
                    '700' => '#c2410c',
                    '800' => '#9a3412',
                    '900' => '#7c2d12',
                ],
                'secondary' => self::DEFAULT_COLORS['secondary'],
            ],
        ];
    }

    /**
     * Get validation rules for customizations.
     */
    public static function getValidationRules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'customizations' => 'required|array',
            'customizations.colors' => 'nullable|array',
            'customizations.colors.primary' => 'nullable|array',
            'customizations.colors.secondary' => 'nullable|array',
            'customizations.colors.primary.*' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'customizations.colors.secondary.*' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
        ];
    }
}
