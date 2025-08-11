<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Company Model
 * 
 * Represents companies in the multi-tenant ERP system.
 * Each company has its own settings and can have multiple users, clients, etc.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $logo
 * @property string|null $locale
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Company extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'email',
        'website',
        'logo',
        'locale',
        'currency',
        'client_record_id',
        'is_active',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'client_record_id' => 'integer',
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default currency codes
     */
    const DEFAULT_CURRENCY = 'USD';
    
    /**
     * Supported currencies
     */
    const SUPPORTED_CURRENCIES = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'CAD' => 'Canadian Dollar',
        'AUD' => 'Australian Dollar',
        'JPY' => 'Japanese Yen',
    ];

    /**
     * Get the company's settings.
     */
    public function setting(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * Get the company's users.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the company's clients.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the client record in Company 1 for billing (for tenant companies).
     */
    public function clientRecord()
    {
        return $this->belongsTo(Client::class, 'client_record_id');
    }

    /**
     * Get the company's full address.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the company's logo URL.
     */
    public function getLogoUrl(): ?string
    {
        if ($this->logo) {
            return asset('storage/companies/' . $this->logo);
        }

        return null;
    }

    /**
     * Get the currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Get the currency name.
     */
    public function getCurrencyName(): string
    {
        return self::SUPPORTED_CURRENCIES[$this->currency] ?? $this->currency;
    }

    /**
     * Format amount with company currency.
     */
    public function formatCurrency(float $amount): string
    {
        return $this->getCurrencySymbol() . number_format($amount, 2);
    }

    /**
     * Check if company has a logo.
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo);
    }

    /**
     * Check if company has complete address information.
     */
    public function hasCompleteAddress(): bool
    {
        return !empty($this->address) && !empty($this->city) && !empty($this->state);
    }

    /**
     * Get the company's locale or default.
     */
    public function getLocale(): string
    {
        return $this->locale ?? 'en_US';
    }

    /**
     * Get the company's timezone from settings.
     */
    public function getTimezone(): string
    {
        return $this->setting?->timezone ?? 'America/New_York';
    }

    /**
     * Scope to search companies by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
    }

    /**
     * Scope to get companies by currency.
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Get validation rules for company creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'locale' => 'nullable|string|max:10',
            'currency' => 'required|string|size:3|in:' . implode(',', array_keys(self::SUPPORTED_CURRENCIES)),
        ];
    }

    /**
     * Get validation rules for company update.
     */
    public static function getUpdateValidationRules(int $companyId): array
    {
        $rules = self::getValidationRules();
        // No unique constraints to modify for company updates
        return $rules;
    }

    /**
     * Get supported currencies for selection.
     */
    public static function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default currency if not provided
        static::creating(function ($company) {
            if (empty($company->currency)) {
                $company->currency = self::DEFAULT_CURRENCY;
            }
        });

        // Create default settings when company is created
        static::created(function ($company) {
            Setting::create([
                'company_id' => $company->id,
                'current_database_version' => '1.0.0',
                'start_page' => 'clients.php',
                'default_net_terms' => 30,
                'default_hourly_rate' => 0.00,
                'invoice_next_number' => 1,
                'quote_next_number' => 1,
                'ticket_next_number' => 1,
                'theme' => 'blue',
                'timezone' => 'America/New_York',
            ]);
        });
    }
}