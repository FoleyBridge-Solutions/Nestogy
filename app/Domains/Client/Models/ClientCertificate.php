<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientCertificate extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'type',
        'issuer',
        'subject',
        'serial_number',
        'key_size',
        'algorithm',
        'fingerprint_sha1',
        'fingerprint_sha256',
        'is_wildcard',
        'domain_names',
        'certificate_path',
        'private_key_path',
        'intermediate_path',
        'root_ca_path',
        'issued_at',
        'expires_at',
        'renewal_date',
        'auto_renewal',
        'days_before_expiry_alert',
        'status',
        'vendor',
        'purchase_cost',
        'renewal_cost',
        'notes',
        'custom_fields',
        'accessed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'key_size' => 'integer',
        'is_wildcard' => 'boolean',
        'domain_names' => 'array',
        'issued_at' => 'date',
        'expires_at' => 'date',
        'renewal_date' => 'date',
        'auto_renewal' => 'boolean',
        'days_before_expiry_alert' => 'integer',
        'purchase_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
        'custom_fields' => 'array',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'issued_at',
        'expires_at',
        'renewal_date',
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the certificate.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include active certificates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include expired certificates.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include certificates expiring soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope a query to only include certificates by type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include wildcard certificates.
     */
    public function scopeWildcard($query)
    {
        return $query->where('is_wildcard', true);
    }

    /**
     * Check if the certificate is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the certificate is expiring soon.
     */
    public function isExpiringSoon($days = null)
    {
        if (!$this->expires_at) {
            return false;
        }

        $alertDays = $days ?: $this->days_before_expiry_alert ?: 30;
        
        return !$this->isExpired() && 
               $this->expires_at->diffInDays(now()) <= $alertDays;
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return -$this->expires_at->diffInDays(now());
        }

        return $this->expires_at->diffInDays(now());
    }

    /**
     * Get the certificate's status color for display.
     */
    public function getStatusColorAttribute()
    {
        if ($this->isExpired()) {
            return 'red';
        }

        if ($this->isExpiringSoon()) {
            return 'yellow';
        }

        $colors = [
            'active' => 'green',
            'pending' => 'blue',
            'revoked' => 'red',
            'inactive' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get the certificate's expiry status.
     */
    public function getExpiryStatusAttribute()
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * Get the primary domain name.
     */
    public function getPrimaryDomainAttribute()
    {
        if (!$this->domain_names || empty($this->domain_names)) {
            return null;
        }

        return $this->domain_names[0];
    }

    /**
     * Get additional domain names (excluding primary).
     */
    public function getAdditionalDomainsAttribute()
    {
        if (!$this->domain_names || count($this->domain_names) <= 1) {
            return [];
        }

        return array_slice($this->domain_names, 1);
    }

    /**
     * Get domain names as a formatted string.
     */
    public function getFormattedDomainsAttribute()
    {
        if (!$this->domain_names || empty($this->domain_names)) {
            return 'No domains specified';
        }

        if (count($this->domain_names) === 1) {
            return $this->domain_names[0];
        }

        return $this->domain_names[0] . ' +' . (count($this->domain_names) - 1) . ' more';
    }

    /**
     * Get the certificate's security level based on key size.
     */
    public function getSecurityLevelAttribute()
    {
        if (!$this->key_size) {
            return 'unknown';
        }

        if ($this->key_size >= 4096) {
            return 'high';
        } elseif ($this->key_size >= 2048) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get the certificate's algorithm display name.
     */
    public function getAlgorithmDisplayAttribute()
    {
        $algorithms = [
            'rsa' => 'RSA',
            'ecdsa' => 'ECDSA',
            'dsa' => 'DSA',
            'ed25519' => 'Ed25519',
        ];

        $algorithm = strtolower($this->algorithm ?? '');
        return $algorithms[$algorithm] ?? strtoupper($algorithm);
    }

    /**
     * Check if certificate files exist.
     */
    public function hasCertificateFiles()
    {
        return !empty($this->certificate_path) || !empty($this->private_key_path);
    }

    /**
     * Get renewal urgency level.
     */
    public function getRenewalUrgencyAttribute()
    {
        if ($this->isExpired()) {
            return 'critical';
        }

        if (!$this->expires_at) {
            return 'none';
        }

        $daysUntilExpiry = $this->days_until_expiry;

        if ($daysUntilExpiry <= 7) {
            return 'critical';
        } elseif ($daysUntilExpiry <= 30) {
            return 'high';
        } elseif ($daysUntilExpiry <= 90) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get available certificate types.
     */
    public static function getTypes()
    {
        return [
            'ssl_tls' => 'SSL/TLS Certificate',
            'code_signing' => 'Code Signing Certificate',
            'client_auth' => 'Client Authentication',
            'email_smime' => 'Email (S/MIME)',
            'document_signing' => 'Document Signing',
            'timestamp' => 'Timestamp Certificate',
            'ca_intermediate' => 'CA Intermediate',
            'other' => 'Other',
        ];
    }

    /**
     * Get available certificate statuses.
     */
    public static function getStatuses()
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending Issuance',
            'revoked' => 'Revoked',
            'inactive' => 'Inactive',
        ];
    }

    /**
     * Get common key sizes.
     */
    public static function getKeySizes()
    {
        return [
            1024 => '1024 bits (Deprecated)',
            2048 => '2048 bits',
            3072 => '3072 bits',
            4096 => '4096 bits',
            8192 => '8192 bits',
        ];
    }

    /**
     * Get supported algorithms.
     */
    public static function getAlgorithms()
    {
        return [
            'rsa' => 'RSA',
            'ecdsa' => 'ECDSA',
            'dsa' => 'DSA',
            'ed25519' => 'Ed25519',
        ];
    }

    /**
     * Get popular certificate vendors.
     */
    public static function getVendors()
    {
        return [
            'letsencrypt' => "Let's Encrypt",
            'digicert' => 'DigiCert',
            'sectigo' => 'Sectigo (Comodo)',
            'globalsign' => 'GlobalSign',
            'godaddy' => 'GoDaddy',
            'namecheap' => 'Namecheap',
            'ssl_com' => 'SSL.com',
            'entrust' => 'Entrust',
            'thawte' => 'Thawte',
            'verisign' => 'VeriSign',
            'rapidssl' => 'RapidSSL',
            'other' => 'Other',
        ];
    }
}