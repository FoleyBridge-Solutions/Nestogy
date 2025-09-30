<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment Method Model
 *
 * Manages stored payment methods for clients including credit cards,
 * bank accounts, digital wallets, and cryptocurrency options.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property string $type
 * @property string $provider
 * @property string|null $provider_payment_method_id
 * @property string|null $provider_customer_id
 * @property string|null $token
 * @property string|null $fingerprint
 * @property string|null $name
 * @property string|null $description
 * @property bool $is_default
 * @property bool $is_active
 * @property bool $verified
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $card_brand
 * @property string|null $card_last_four
 * @property string|null $card_exp_month
 * @property string|null $card_exp_year
 * @property string|null $card_holder_name
 * @property string|null $card_country
 * @property string|null $card_funding
 * @property bool|null $card_checks_cvc_check
 * @property bool|null $card_checks_address_line1_check
 * @property bool|null $card_checks_address_postal_code_check
 * @property string|null $bank_name
 * @property string|null $bank_account_type
 * @property string|null $bank_account_last_four
 * @property string|null $bank_routing_number_last_four
 * @property string|null $bank_account_holder_type
 * @property string|null $bank_account_holder_name
 * @property string|null $bank_country
 * @property string|null $bank_currency
 * @property string|null $wallet_type
 * @property string|null $wallet_email
 * @property string|null $wallet_phone
 * @property string|null $crypto_type
 * @property string|null $crypto_address
 * @property string|null $crypto_network
 * @property string|null $billing_name
 * @property string|null $billing_email
 * @property string|null $billing_phone
 * @property string|null $billing_address_line1
 * @property string|null $billing_address_line2
 * @property string|null $billing_city
 * @property string|null $billing_state
 * @property string|null $billing_postal_code
 * @property string|null $billing_country
 * @property array|null $security_checks
 * @property array|null $compliance_data
 * @property bool $requires_3d_secure
 * @property array|null $risk_assessment
 * @property int $successful_payments_count
 * @property int $failed_payments_count
 * @property float $total_payment_amount
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $last_failed_at
 * @property string|null $last_failure_reason
 * @property array|null $metadata
 * @property array|null $preferences
 * @property array|null $restrictions
 * @property float|null $daily_limit
 * @property float|null $monthly_limit
 * @property array|null $allowed_currencies
 * @property array|null $blocked_countries
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $deactivated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deactivation_reason
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PaymentMethod extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'payment_methods';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'type',
        'provider',
        'provider_payment_method_id',
        'provider_customer_id',
        'token',
        'fingerprint',
        'name',
        'description',
        'is_default',
        'is_active',
        'verified',
        'verified_at',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'card_holder_name',
        'card_country',
        'card_funding',
        'card_checks_cvc_check',
        'card_checks_address_line1_check',
        'card_checks_address_postal_code_check',
        'bank_name',
        'bank_account_type',
        'bank_account_last_four',
        'bank_routing_number_last_four',
        'bank_account_holder_type',
        'bank_account_holder_name',
        'bank_country',
        'bank_currency',
        'wallet_type',
        'wallet_email',
        'wallet_phone',
        'crypto_type',
        'crypto_address',
        'crypto_network',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'security_checks',
        'compliance_data',
        'requires_3d_secure',
        'risk_assessment',
        'successful_payments_count',
        'failed_payments_count',
        'total_payment_amount',
        'last_used_at',
        'last_failed_at',
        'last_failure_reason',
        'metadata',
        'preferences',
        'restrictions',
        'daily_limit',
        'monthly_limit',
        'allowed_currencies',
        'blocked_countries',
        'expires_at',
        'deactivated_at',
        'deactivation_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'card_checks_cvc_check' => 'boolean',
        'card_checks_address_line1_check' => 'boolean',
        'card_checks_address_postal_code_check' => 'boolean',
        'requires_3d_secure' => 'boolean',
        'security_checks' => 'array',
        'compliance_data' => 'array',
        'risk_assessment' => 'array',
        'successful_payments_count' => 'integer',
        'failed_payments_count' => 'integer',
        'total_payment_amount' => 'decimal:2',
        'last_used_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'metadata' => 'array',
        'preferences' => 'array',
        'restrictions' => 'array',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'allowed_currencies' => 'array',
        'blocked_countries' => 'array',
        'expires_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'token',
        'provider_payment_method_id',
        'provider_customer_id',
    ];

    /**
     * Payment method type constants
     */
    const TYPE_CREDIT_CARD = 'credit_card';

    const TYPE_DEBIT_CARD = 'debit_card';

    const TYPE_BANK_ACCOUNT = 'bank_account';

    const TYPE_PAYPAL = 'paypal';

    const TYPE_APPLE_PAY = 'apple_pay';

    const TYPE_GOOGLE_PAY = 'google_pay';

    const TYPE_CRYPTOCURRENCY = 'cryptocurrency';

    /**
     * Payment provider constants
     */
    const PROVIDER_STRIPE = 'stripe';

    const PROVIDER_PAYPAL = 'paypal';

    const PROVIDER_AUTHORIZE_NET = 'authorize_net';

    const PROVIDER_SQUARE = 'square';

    const PROVIDER_INTERNAL = 'internal';

    /**
     * Card brand constants
     */
    const BRAND_VISA = 'visa';

    const BRAND_MASTERCARD = 'mastercard';

    const BRAND_AMEX = 'amex';

    const BRAND_DISCOVER = 'discover';

    const BRAND_DINERS = 'diners';

    const BRAND_JCB = 'jcb';

    const BRAND_UNIONPAY = 'unionpay';

    /**
     * Bank account type constants
     */
    const BANK_CHECKING = 'checking';

    const BANK_SAVINGS = 'savings';

    /**
     * Get the client that owns this payment method.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the company that owns this payment method.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this payment method.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this payment method.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the auto payment configurations using this method.
     */
    public function autoPayments(): HasMany
    {
        return $this->hasMany(AutoPayment::class);
    }

    /**
     * Get the payments made with this method.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if payment method is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true &&
               $this->deactivated_at === null &&
               ! $this->isExpired();
    }

    /**
     * Check if payment method is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at) {
            return Carbon::now()->gt($this->expires_at);
        }

        // Check card expiration
        if ($this->isCard() && $this->card_exp_month && $this->card_exp_year) {
            $expiry = Carbon::createFromDate($this->card_exp_year, $this->card_exp_month, 1)->endOfMonth();

            return Carbon::now()->gt($expiry);
        }

        return false;
    }

    /**
     * Check if this is the default payment method.
     */
    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    /**
     * Check if payment method is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified === true;
    }

    /**
     * Check if this is a card payment method.
     */
    public function isCard(): bool
    {
        return in_array($this->type, [self::TYPE_CREDIT_CARD, self::TYPE_DEBIT_CARD]);
    }

    /**
     * Check if this is a bank account payment method.
     */
    public function isBankAccount(): bool
    {
        return $this->type === self::TYPE_BANK_ACCOUNT;
    }

    /**
     * Check if this is a digital wallet.
     */
    public function isWallet(): bool
    {
        return in_array($this->type, [
            self::TYPE_PAYPAL,
            self::TYPE_APPLE_PAY,
            self::TYPE_GOOGLE_PAY,
        ]);
    }

    /**
     * Check if this is a cryptocurrency payment method.
     */
    public function isCrypto(): bool
    {
        return $this->type === self::TYPE_CRYPTOCURRENCY;
    }

    /**
     * Get display name for the payment method.
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->isCard()) {
            return $this->getCardDisplayName();
        }

        if ($this->isBankAccount()) {
            return $this->getBankDisplayName();
        }

        if ($this->isWallet()) {
            return $this->getWalletDisplayName();
        }

        if ($this->isCrypto()) {
            return $this->getCryptoDisplayName();
        }

        return ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Get formatted display text for cards.
     */
    public function getCardDisplayName(): string
    {
        $brand = $this->card_brand ? ucfirst($this->card_brand) : 'Card';
        $lastFour = $this->card_last_four ? '****'.$this->card_last_four : '';

        if ($this->card_exp_month && $this->card_exp_year) {
            $expiry = sprintf('%02d/%s', $this->card_exp_month, substr($this->card_exp_year, -2));

            return "{$brand} {$lastFour} ({$expiry})";
        }

        return "{$brand} {$lastFour}";
    }

    /**
     * Get formatted display text for bank accounts.
     */
    public function getBankDisplayName(): string
    {
        $type = $this->bank_account_type ? ucfirst($this->bank_account_type) : 'Account';
        $lastFour = $this->bank_account_last_four ? '****'.$this->bank_account_last_four : '';
        $bank = $this->bank_name ? " ({$this->bank_name})" : '';

        return "{$type} {$lastFour}{$bank}";
    }

    /**
     * Get formatted display text for wallets.
     */
    public function getWalletDisplayName(): string
    {
        $type = ucfirst(str_replace('_', ' ', $this->type));

        if ($this->wallet_email) {
            return "{$type} ({$this->wallet_email})";
        }

        return $type;
    }

    /**
     * Get formatted display text for crypto.
     */
    public function getCryptoDisplayName(): string
    {
        $type = $this->crypto_type ? ucfirst($this->crypto_type) : 'Cryptocurrency';

        if ($this->crypto_address) {
            $shortAddress = substr($this->crypto_address, 0, 6).'...'.substr($this->crypto_address, -4);

            return "{$type} ({$shortAddress})";
        }

        return $type;
    }

    /**
     * Get payment method icon.
     */
    public function getIcon(): string
    {
        if ($this->isCard()) {
            return $this->getCardIcon();
        }

        $iconMap = [
            self::TYPE_BANK_ACCOUNT => 'bank',
            self::TYPE_PAYPAL => 'paypal',
            self::TYPE_APPLE_PAY => 'apple-pay',
            self::TYPE_GOOGLE_PAY => 'google-pay',
            self::TYPE_CRYPTOCURRENCY => 'bitcoin',
        ];

        return $iconMap[$this->type] ?? 'credit-card';
    }

    /**
     * Get card brand icon.
     */
    public function getCardIcon(): string
    {
        $iconMap = [
            self::BRAND_VISA => 'visa',
            self::BRAND_MASTERCARD => 'mastercard',
            self::BRAND_AMEX => 'amex',
            self::BRAND_DISCOVER => 'discover',
            self::BRAND_DINERS => 'diners',
            self::BRAND_JCB => 'jcb',
            self::BRAND_UNIONPAY => 'unionpay',
        ];

        return $iconMap[$this->card_brand] ?? 'credit-card';
    }

    /**
     * Set this as the default payment method.
     */
    public function setAsDefault(): bool
    {
        // Remove default from other payment methods for this client
        self::where('client_id', $this->client_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        return $this->update(['is_default' => true]);
    }

    /**
     * Activate the payment method.
     */
    public function activate(): bool
    {
        return $this->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ]);
    }

    /**
     * Deactivate the payment method.
     */
    public function deactivate(?string $reason = null): bool
    {
        return $this->update([
            'is_active' => false,
            'deactivated_at' => Carbon::now(),
            'deactivation_reason' => $reason,
            'is_default' => false, // Can't be default if deactivated
        ]);
    }

    /**
     * Mark payment method as verified.
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'verified' => true,
            'verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Record successful payment.
     */
    public function recordSuccessfulPayment(float $amount): bool
    {
        return $this->update([
            'successful_payments_count' => $this->successful_payments_count + 1,
            'total_payment_amount' => $this->total_payment_amount + $amount,
            'last_used_at' => Carbon::now(),
        ]);
    }

    /**
     * Record failed payment.
     */
    public function recordFailedPayment(?string $reason = null): bool
    {
        return $this->update([
            'failed_payments_count' => $this->failed_payments_count + 1,
            'last_failed_at' => Carbon::now(),
            'last_failure_reason' => $reason,
        ]);
    }

    /**
     * Check if payment method can process amount.
     */
    public function canProcessAmount(float $amount, string $currency = 'USD'): bool
    {
        // Check daily limit
        if ($this->daily_limit && $this->getDailyUsage() + $amount > $this->daily_limit) {
            return false;
        }

        // Check monthly limit
        if ($this->monthly_limit && $this->getMonthlyUsage() + $amount > $this->monthly_limit) {
            return false;
        }

        // Check currency restrictions
        if ($this->allowed_currencies && ! in_array($currency, $this->allowed_currencies)) {
            return false;
        }

        return true;
    }

    /**
     * Get daily usage amount.
     */
    public function getDailyUsage(): float
    {
        return $this->payments()
            ->whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get monthly usage amount.
     */
    public function getMonthlyUsage(): float
    {
        return $this->payments()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRate(): float
    {
        $total = $this->successful_payments_count + $this->failed_payments_count;

        if ($total === 0) {
            return 100.0;
        }

        return ($this->successful_payments_count / $total) * 100;
    }

    /**
     * Check if payment method has good health.
     */
    public function hasGoodHealth(): bool
    {
        // Check success rate
        if ($this->getSuccessRate() < 85) {
            return false;
        }

        // Check if recently failed
        if ($this->last_failed_at && $this->last_failed_at->gt(Carbon::now()->subHours(24))) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique fingerprint for duplicate detection.
     */
    public function generateFingerprint(): string
    {
        $data = [];

        if ($this->isCard()) {
            $data = [
                $this->card_brand,
                $this->card_last_four,
                $this->card_exp_month,
                $this->card_exp_year,
            ];
        } elseif ($this->isBankAccount()) {
            $data = [
                $this->bank_account_last_four,
                $this->bank_routing_number_last_four,
                $this->bank_account_type,
            ];
        } elseif ($this->isWallet()) {
            $data = [$this->wallet_email, $this->wallet_phone];
        } elseif ($this->isCrypto()) {
            $data = [$this->crypto_address, $this->crypto_type];
        }

        return hash('sha256', implode('|', array_filter($data)).$this->client_id);
    }

    /**
     * Scope to get active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('deactivated_at');
    }

    /**
     * Scope to get verified payment methods.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope to get default payment method.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get payment methods by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get card payment methods.
     */
    public function scopeCards($query)
    {
        return $query->whereIn('type', [self::TYPE_CREDIT_CARD, self::TYPE_DEBIT_CARD]);
    }

    /**
     * Scope to get non-expired payment methods.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($paymentMethod) {
            if (! $paymentMethod->fingerprint) {
                $paymentMethod->fingerprint = $paymentMethod->generateFingerprint();
            }

            // Set as default if this is the first payment method for the client
            if (! $paymentMethod->is_default) {
                $hasDefault = self::where('client_id', $paymentMethod->client_id)
                    ->where('is_default', true)
                    ->exists();

                if (! $hasDefault) {
                    $paymentMethod->is_default = true;
                }
            }
        });

        static::updating(function ($paymentMethod) {
            if ($paymentMethod->isDirty(['card_last_four', 'card_exp_month', 'card_exp_year', 'bank_account_last_four'])) {
                $paymentMethod->fingerprint = $paymentMethod->generateFingerprint();
            }
        });
    }
}
