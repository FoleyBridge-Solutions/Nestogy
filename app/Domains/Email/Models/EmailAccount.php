<?php

namespace App\Domains\Email\Models;

use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'email_address',
        'provider',
        'connection_type',
        'oauth_provider',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'imap_validate_cert',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_expires_at',
        'oauth_token_expires_at',
        'oauth_scopes',
        'is_default',
        'is_active',
        'sync_interval_minutes',
        'last_synced_at',
        'sync_error',
        'auto_create_tickets',
        'auto_log_communications',
        'filters',
    ];

    protected $casts = [
        'imap_validate_cert' => 'boolean',
        'oauth_expires_at' => 'datetime',
        'oauth_token_expires_at' => 'datetime',
        'oauth_scopes' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'auto_create_tickets' => 'boolean',
        'auto_log_communications' => 'boolean',
        'filters' => 'array',
    ];

    protected $hidden = [
        'imap_password',
        'smtp_password',
        'oauth_access_token',
        'oauth_refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(EmailFolder::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(EmailSignature::class);
    }

    /**
     * Check if this account uses OAuth authentication
     */
    public function isOAuthProvider(): bool
    {
        return $this->connection_type === 'oauth' &&
               in_array($this->oauth_provider, ['microsoft365', 'google_workspace']);
    }

    /**
     * Check if OAuth tokens are valid
     */
    public function hasValidOAuthTokens(): bool
    {
        return $this->oauth_access_token &&
               $this->oauth_expires_at &&
               $this->oauth_expires_at->isFuture();
    }

    // Mutators for password encryption
    public function setImapPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['imap_password'] = Crypt::encryptString($value);
        }
    }

    public function getImapPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setSmtpPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['smtp_password'] = Crypt::encryptString($value);
        }
    }

    public function getSmtpPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    // OAuth token encryption
    public function setOauthAccessTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['oauth_access_token'] = Crypt::encryptString($value);
        }
    }

    public function getOauthAccessTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setOauthRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['oauth_refresh_token'] = Crypt::encryptString($value);
        }
    }

    public function getOauthRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    // Helper methods

    public function needsTokenRefresh(): bool
    {
        return $this->isOAuthProvider() &&
               $this->oauth_expires_at &&
               $this->oauth_expires_at->isPast();
    }

    public function getConnectionConfig(): array
    {
        return [
            'host' => $this->imap_host,
            'port' => $this->imap_port,
            'encryption' => $this->imap_encryption,
            'validate_cert' => $this->imap_validate_cert,
            'username' => $this->imap_username,
            'password' => $this->imap_password,
            'authentication' => $this->isOAuthProvider() ? 'oauth' : null,
        ];
    }

    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->smtp_host,
            'port' => $this->smtp_port,
            'encryption' => $this->smtp_encryption,
            'username' => $this->smtp_username,
            'password' => $this->smtp_password,
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNeedingSync($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_synced_at, NOW()) >= sync_interval_minutes');
            });
    }
}
