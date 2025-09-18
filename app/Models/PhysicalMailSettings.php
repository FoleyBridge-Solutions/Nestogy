<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PhysicalMailSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'test_key',
        'live_key',
        'webhook_secret',
        'force_test_mode',
        'from_company_name',
        'from_contact_name',
        'from_address_line1',
        'from_address_line2',
        'from_city',
        'from_state',
        'from_zip',
        'from_country',
        'default_mailing_class',
        'default_color_printing',
        'default_double_sided',
        'default_address_placement',
        'default_size',
        'track_costs',
        'markup_percentage',
        'include_tax',
        'enable_ncoa',
        'enable_address_verification',
        'enable_return_envelopes',
        'enable_bulk_mail',
        'is_active',
        'last_connection_test',
        'last_connection_status',
    ];

    protected $casts = [
        'force_test_mode' => 'boolean',
        'default_color_printing' => 'boolean',
        'default_double_sided' => 'boolean',
        'track_costs' => 'boolean',
        'include_tax' => 'boolean',
        'enable_ncoa' => 'boolean',
        'enable_address_verification' => 'boolean',
        'enable_return_envelopes' => 'boolean',
        'enable_bulk_mail' => 'boolean',
        'is_active' => 'boolean',
        'markup_percentage' => 'decimal:2',
        'last_connection_test' => 'datetime',
    ];

    protected $hidden = [
        'test_key',
        'live_key',
        'webhook_secret',
    ];

    /**
     * The attributes that should be encrypted.
     */
    protected $encrypted = [
        'test_key',
        'live_key',
        'webhook_secret',
    ];

    /**
     * Get the company that owns the settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get settings for the current company.
     */
    public static function forCompany(?int $companyId = null): ?self
    {
        $companyId = $companyId ?: auth()->user()?->company_id;
        
        if (!$companyId) {
            return null;
        }

        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'from_company_name' => Company::find($companyId)?->name,
                'default_mailing_class' => 'first_class',
                'default_color_printing' => true,
                'default_double_sided' => false,
            ]
        );
    }

    /**
     * Check if API is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->test_key) || !empty($this->live_key);
    }

    /**
     * Check if should use test mode.
     */
    public function shouldUseTestMode(): bool
    {
        // If not in production, always use test mode
        if (app()->environment() !== 'production') {
            return true;
        }

        // In production, check force flag or if no live key
        return $this->force_test_mode || empty($this->live_key);
    }

    /**
     * Get the active API key.
     */
    public function getActiveApiKey(): ?string
    {
        return $this->shouldUseTestMode() ? $this->test_key : $this->live_key;
    }

    /**
     * Get from address as array.
     */
    public function getFromAddress(): array
    {
        return [
            'firstName' => explode(' ', $this->from_contact_name ?: '')[0] ?? '',
            'lastName' => explode(' ', $this->from_contact_name ?: '', 2)[1] ?? '',
            'companyName' => $this->from_company_name,
            'addressLine1' => $this->from_address_line1,
            'addressLine2' => $this->from_address_line2,
            'city' => $this->from_city,
            'provinceOrState' => $this->from_state,
            'postalOrZip' => $this->from_zip,
            'country' => $this->from_country ?: 'US',
        ];
    }

    /**
     * Update last connection test.
     */
    public function updateConnectionTest(bool $success, ?string $message = null): void
    {
        $this->update([
            'last_connection_test' => now(),
            'last_connection_status' => $success ? 'success' : ($message ?: 'failed'),
        ]);
    }
}