<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDomain extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'description',
        'domain_name',
        'tld',
        'registrar',
        'registrar_account',
        'registrar_url',
        'nameservers',
        'dns_provider',
        'dns_account',
        'registered_at',
        'expires_at',
        'renewal_date',
        'auto_renewal',
        'days_before_expiry_alert',
        'status',
        'privacy_protection',
        'lock_status',
        'whois_guard',
        'transfer_lock',
        'purchase_cost',
        'renewal_cost',
        'transfer_auth_code',
        'dns_records_count',
        'subdomains_count',
        'email_forwards_count',
        'notes',
        'custom_fields',
        'accessed_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'nameservers' => 'array',
        'registered_at' => 'date',
        'expires_at' => 'date',
        'renewal_date' => 'date',
        'auto_renewal' => 'boolean',
        'days_before_expiry_alert' => 'integer',
        'privacy_protection' => 'boolean',
        'lock_status' => 'boolean',
        'whois_guard' => 'boolean',
        'transfer_lock' => 'boolean',
        'purchase_cost' => 'decimal:2',
        'renewal_cost' => 'decimal:2',
        'dns_records_count' => 'integer',
        'subdomains_count' => 'integer',
        'email_forwards_count' => 'integer',
        'custom_fields' => 'array',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'registered_at',
        'expires_at',
        'renewal_date',
        'accessed_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the domain.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include active domains.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include expired domains.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope a query to only include domains expiring soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope a query to filter by registrar.
     */
    public function scopeRegistrar($query, $registrar)
    {
        return $query->where('registrar', $registrar);
    }

    /**
     * Scope a query to filter by TLD.
     */
    public function scopeTld($query, $tld)
    {
        return $query->where('tld', $tld);
    }

    /**
     * Check if the domain is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the domain is expiring soon.
     */
    public function isExpiringSoon($days = null)
    {
        if (! $this->expires_at) {
            return false;
        }

        $alertDays = $days ?: $this->days_before_expiry_alert ?: 30;

        return ! $this->isExpired() &&
               $this->expires_at->diffInDays(now()) <= $alertDays;
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (! $this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return -$this->expires_at->diffInDays(now());
        }

        return $this->expires_at->diffInDays(now());
    }

    /**
     * Get the domain's status color for display.
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
            'suspended' => 'red',
            'transferred' => 'gray',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get the domain's expiry status.
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
     * Get the full domain name with TLD.
     */
    public function getFullDomainAttribute()
    {
        if ($this->tld) {
            return $this->domain_name.'.'.$this->tld;
        }

        return $this->domain_name;
    }

    /**
     * Get the domain's security status.
     */
    public function getSecurityStatusAttribute()
    {
        $issues = [];

        if (! $this->privacy_protection) {
            $issues[] = 'No privacy protection';
        }

        if (! $this->lock_status) {
            $issues[] = 'Domain not locked';
        }

        if (! $this->transfer_lock) {
            $issues[] = 'Transfer not locked';
        }

        if (empty($issues)) {
            return 'secure';
        } elseif (count($issues) <= 1) {
            return 'warning';
        } else {
            return 'vulnerable';
        }
    }

    /**
     * Get the security issues array.
     */
    public function getSecurityIssuesAttribute()
    {
        $issues = [];

        if (! $this->privacy_protection) {
            $issues[] = 'No privacy protection';
        }

        if (! $this->lock_status) {
            $issues[] = 'Domain not locked';
        }

        if (! $this->transfer_lock) {
            $issues[] = 'Transfer not locked';
        }

        return $issues;
    }

    /**
     * Get renewal urgency level.
     */
    public function getRenewalUrgencyAttribute()
    {
        if ($this->isExpired()) {
            return 'critical';
        }

        if (! $this->expires_at) {
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
     * Get the formatted nameservers list.
     */
    public function getFormattedNameserversAttribute()
    {
        if (! $this->nameservers || empty($this->nameservers)) {
            return 'No nameservers specified';
        }

        return implode(', ', $this->nameservers);
    }

    /**
     * Get years since registration.
     */
    public function getYearsSinceRegistrationAttribute()
    {
        if (! $this->registered_at) {
            return null;
        }

        return $this->registered_at->diffInYears(now());
    }

    /**
     * Check if domain has DNS management.
     */
    public function hasDnsManagement()
    {
        return ! empty($this->dns_provider) || ($this->dns_records_count && $this->dns_records_count > 0);
    }

    /**
     * Get available domain statuses.
     */
    public static function getStatuses()
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending Registration',
            'suspended' => 'Suspended',
            'transferred' => 'Transferred Out',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get popular domain registrars.
     */
    public static function getRegistrars()
    {
        return [
            'godaddy' => 'GoDaddy',
            'namecheap' => 'Namecheap',
            'cloudflare' => 'Cloudflare',
            'google_domains' => 'Google Domains',
            'name_com' => 'Name.com',
            'hover' => 'Hover',
            'gandi' => 'Gandi',
            'dynadot' => 'Dynadot',
            'network_solutions' => 'Network Solutions',
            'register_com' => 'Register.com',
            'enom' => 'eNom',
            '1and1' => '1&1 IONOS',
            'other' => 'Other',
        ];
    }

    /**
     * Get popular DNS providers.
     */
    public static function getDnsProviders()
    {
        return [
            'cloudflare' => 'Cloudflare',
            'aws_route53' => 'AWS Route 53',
            'google_cloud_dns' => 'Google Cloud DNS',
            'azure_dns' => 'Azure DNS',
            'ns1' => 'NS1',
            'dyn' => 'Oracle Dyn',
            'dns_made_easy' => 'DNS Made Easy',
            'registrar' => 'Same as Registrar',
            'other' => 'Other',
        ];
    }

    /**
     * Get common TLDs.
     */
    public static function getCommonTlds()
    {
        return [
            'com' => '.com',
            'net' => '.net',
            'org' => '.org',
            'info' => '.info',
            'biz' => '.biz',
            'co' => '.co',
            'io' => '.io',
            'ai' => '.ai',
            'app' => '.app',
            'dev' => '.dev',
            'tech' => '.tech',
            'online' => '.online',
        ];
    }
}
