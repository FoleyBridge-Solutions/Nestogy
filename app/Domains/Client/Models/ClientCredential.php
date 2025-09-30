<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class ClientCredential extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'credential_type',
        'service_name',
        'username',
        'password',
        'email',
        'url',
        'port',
        'database_name',
        'connection_string',
        'api_key',
        'secret_key',
        'certificate',
        'private_key',
        'public_key',
        'token',
        'expires_at',
        'is_active',
        'is_shared',
        'environment',
        'access_level',
        'notes',
        'created_by',
        'last_accessed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'created_by' => 'integer',
        'port' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_shared' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    protected $dates = [
        'expires_at',
        'last_accessed_at',
        'deleted_at',
    ];

    protected $hidden = [
        'password',
        'api_key',
        'secret_key',
        'certificate',
        'private_key',
        'token',
    ];

    /**
     * Get the client that owns the credential.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the credential.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope a query to only include credentials of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('credential_type', $type);
    }

    /**
     * Scope a query to only include active credentials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive credentials.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include non-expired credentials.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include expired credentials.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include credentials expiring soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope a query to only include shared credentials.
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Check if the credential is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the credential is expiring soon.
     */
    public function isExpiringSoon($days = 30)
    {
        return $this->expires_at &&
               $this->expires_at->isFuture() &&
               $this->expires_at->diffInDays(now()) <= $days;
    }

    /**
     * Get the credential status.
     */
    public function getStatusAttribute()
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Get the credential status color for UI.
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'success';
            case 'expiring_soon':
                return 'warning';
            case 'expired':
                return 'danger';
            case 'inactive':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Get the credential status label for UI.
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'active':
                return 'Active';
            case 'expiring_soon':
                return 'Expiring Soon';
            case 'expired':
                return 'Expired';
            case 'inactive':
                return 'Inactive';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the masked password for display.
     */
    public function getMaskedPasswordAttribute()
    {
        return $this->password ? str_repeat('*', 8) : null;
    }

    /**
     * Get the decrypted password.
     */
    public function getDecryptedPasswordAttribute()
    {
        try {
            return $this->password ? Crypt::decryptString($this->password) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set and encrypt the password.
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['password'] = null;
        }
    }

    /**
     * Get the decrypted API key.
     */
    public function getDecryptedApiKeyAttribute()
    {
        try {
            return $this->api_key ? Crypt::decryptString($this->api_key) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set and encrypt the API key.
     */
    public function setApiKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_key'] = null;
        }
    }

    /**
     * Get the decrypted secret key.
     */
    public function getDecryptedSecretKeyAttribute()
    {
        try {
            return $this->secret_key ? Crypt::decryptString($this->secret_key) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set and encrypt the secret key.
     */
    public function setSecretKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['secret_key'] = Crypt::encryptString($value);
        } else {
            $this->attributes['secret_key'] = null;
        }
    }

    /**
     * Update last accessed timestamp.
     */
    public function updateLastAccessed()
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get available credential types.
     */
    public static function getCredentialTypes()
    {
        return [
            'database' => 'Database Connection',
            'ftp' => 'FTP/SFTP',
            'ssh' => 'SSH Access',
            'rdp' => 'Remote Desktop',
            'web_admin' => 'Web Admin Panel',
            'email' => 'Email Account',
            'cloud_service' => 'Cloud Service',
            'api' => 'API Access',
            'vpn' => 'VPN Connection',
            'software' => 'Software License',
            'domain' => 'Domain/DNS',
            'ssl_certificate' => 'SSL Certificate',
            'social_media' => 'Social Media',
            'payment' => 'Payment Gateway',
            'other' => 'Other',
        ];
    }

    /**
     * Get available environments.
     */
    public static function getEnvironments()
    {
        return [
            'production' => 'Production',
            'staging' => 'Staging',
            'testing' => 'Testing',
            'development' => 'Development',
            'sandbox' => 'Sandbox',
        ];
    }

    /**
     * Get available access levels.
     */
    public static function getAccessLevels()
    {
        return [
            'read_only' => 'Read Only',
            'read_write' => 'Read/Write',
            'admin' => 'Administrator',
            'super_admin' => 'Super Administrator',
            'limited' => 'Limited Access',
            'full' => 'Full Access',
        ];
    }
}
