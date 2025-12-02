<?php

namespace App\Domains\Financial\Models;

use App\Domains\Company\Models\Account;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * PlaidItem Model
 *
 * Represents a bank connection via Plaid.
 * Stores encrypted access tokens and manages bank account sync.
 */
class PlaidItem extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'plaid_item_id',
        'plaid_access_token',
        'institution_id',
        'institution_name',
        'status',
        'error_code',
        'error_message',
        'consent_expiration_time',
        'products',
        'available_products',
        'billed_products',
        'webhook_url',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'products' => 'array',
        'available_products' => 'array',
        'billed_products' => 'array',
        'metadata' => 'array',
        'consent_expiration_time' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ERROR = 'error';
    const STATUS_REAUTH_REQUIRED = 'reauth_required';

    /**
     * Get encrypted access token.
     */
    protected function plaidAccessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Get linked accounts.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'plaid_item_id');
    }

    /**
     * Get bank transactions.
     */
    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Check if item is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if item has error.
     */
    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    /**
     * Check if item needs reauthorization.
     */
    public function needsReauth(): bool
    {
        return $this->status === self::STATUS_REAUTH_REQUIRED;
    }

    /**
     * Check if consent is expiring soon (within 7 days).
     */
    public function consentExpiringSoon(): bool
    {
        if (!$this->consent_expiration_time) {
            return false;
        }

        return $this->consent_expiration_time->lte(now()->addDays(7));
    }

    /**
     * Check if consent is expired.
     */
    public function consentExpired(): bool
    {
        if (!$this->consent_expiration_time) {
            return false;
        }

        return $this->consent_expiration_time->isPast();
    }

    /**
     * Mark as error.
     */
    public function markAsError(string $errorCode, string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark as needing reauthorization.
     */
    public function markAsNeedingReauth(): void
    {
        $this->update([
            'status' => self::STATUS_REAUTH_REQUIRED,
        ]);
    }

    /**
     * Mark as active and clear errors.
     */
    public function markAsActive(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Update last synced timestamp.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Get linked account count.
     */
    public function getLinkedAccountCount(): int
    {
        return $this->accounts()->count();
    }

    /**
     * Get transaction count.
     */
    public function getTransactionCount(): int
    {
        return $this->bankTransactions()->count();
    }

    /**
     * Get unreconciled transaction count.
     */
    public function getUnreconciledTransactionCount(): int
    {
        return $this->bankTransactions()->unreconciled()->count();
    }

    /**
     * Scope: Active items.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Items with errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->whereIn('status', [self::STATUS_ERROR, self::STATUS_REAUTH_REQUIRED]);
    }

    /**
     * Scope: Items needing sync (not synced in last hour).
     */
    public function scopeNeedingSync($query)
    {
        return $query->where('status', self::STATUS_ACTIVE')
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subHour());
            });
    }

    /**
     * Get display status.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Connected',
            self::STATUS_INACTIVE => 'Disconnected',
            self::STATUS_ERROR => 'Error',
            self::STATUS_REAUTH_REQUIRED => 'Reauth Required',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_ERROR => 'red',
            self::STATUS_REAUTH_REQUIRED => 'yellow',
            default => 'gray',
        };
    }
}
