<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientNetwork extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'name',
        'description',
        'network_type',
        'ip_range',
        'subnet_mask',
        'gateway',
        'dns_servers',
        'dhcp_range_start',
        'dhcp_range_end',
        'vlan_id',
        'ssid',
        'wifi_password',
        'security_type',
        'bandwidth',
        'provider',
        'circuit_id',
        'static_routes',
        'firewall_rules',
        'vpn_config',
        'monitoring_enabled',
        'backup_config',
        'is_active',
        'location',
        'equipment',
        'notes',
        'diagram_file',
        'last_audit_date',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'client_id' => 'integer',
        'vlan_id' => 'integer',
        'dns_servers' => 'array',
        'static_routes' => 'array',
        'firewall_rules' => 'array',
        'vpn_config' => 'array',
        'equipment' => 'array',
        'monitoring_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_audit_date' => 'date',
    ];

    protected $dates = [
        'last_audit_date',
        'deleted_at',
    ];

    /**
     * Get the client that owns the network.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include networks of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('network_type', $type);
    }

    /**
     * Scope a query to only include active networks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive networks.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include monitored networks.
     */
    public function scopeMonitored($query)
    {
        return $query->where('monitoring_enabled', true);
    }

    /**
     * Scope a query to only include networks with VLANs.
     */
    public function scopeWithVlan($query)
    {
        return $query->whereNotNull('vlan_id');
    }

    /**
     * Scope a query to only include wireless networks.
     */
    public function scopeWireless($query)
    {
        return $query->whereNotNull('ssid');
    }

    /**
     * Get the network status.
     */
    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->monitoring_enabled) {
            return 'monitored';
        }
        
        return 'active';
    }

    /**
     * Get the network status color for UI.
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'monitored':
                return 'success';
            case 'active':
                return 'info';
            case 'inactive':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Get the network status label for UI.
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'monitored':
                return 'Active & Monitored';
            case 'active':
                return 'Active';
            case 'inactive':
                return 'Inactive';
            default:
                return 'Unknown';
        }
    }

    /**
     * Check if network needs audit (older than 6 months).
     */
    public function needsAudit($months = 6)
    {
        if (!$this->last_audit_date) {
            return true;
        }
        
        return $this->last_audit_date->addMonths($months)->isPast();
    }

    /**
     * Get masked WiFi password.
     */
    public function getMaskedWifiPasswordAttribute()
    {
        return $this->wifi_password ? str_repeat('*', 8) : null;
    }

    /**
     * Calculate network size based on subnet mask.
     */
    public function getNetworkSizeAttribute()
    {
        if (!$this->subnet_mask) {
            return null;
        }
        
        // Convert subnet mask to CIDR and calculate host count
        $cidr = $this->subnetMaskToCidr($this->subnet_mask);
        if ($cidr) {
            return pow(2, 32 - $cidr) - 2; // -2 for network and broadcast
        }
        
        return null;
    }

    /**
     * Convert subnet mask to CIDR notation.
     */
    private function subnetMaskToCidr($mask)
    {
        $cidr = 0;
        $octets = explode('.', $mask);
        
        foreach ($octets as $octet) {
            $cidr += substr_count(decbin($octet), '1');
        }
        
        return $cidr;
    }

    /**
     * Get the network type icon.
     */
    public function getTypeIconAttribute()
    {
        $icons = [
            'lan' => '🏢',
            'wan' => '🌐',
            'wifi' => '📶',
            'vpn' => '🔒',
            'dmz' => '🛡️',
            'guest' => '👥',
            'management' => '⚙️',
            'storage' => '💾',
            'backup' => '🔄',
            'other' => '🔗',
        ];

        return $icons[$this->network_type] ?? '🔗';
    }

    /**
     * Get available network types.
     */
    public static function getNetworkTypes()
    {
        return [
            'lan' => 'Local Area Network (LAN)',
            'wan' => 'Wide Area Network (WAN)',
            'wifi' => 'Wireless Network',
            'vpn' => 'Virtual Private Network',
            'dmz' => 'Demilitarized Zone',
            'guest' => 'Guest Network',
            'management' => 'Management Network',
            'storage' => 'Storage Network',
            'backup' => 'Backup Network',
            'other' => 'Other',
        ];
    }

    /**
     * Get available security types for wireless networks.
     */
    public static function getSecurityTypes()
    {
        return [
            'open' => 'Open (No Security)',
            'wep' => 'WEP',
            'wpa' => 'WPA',
            'wpa2' => 'WPA2',
            'wpa3' => 'WPA3',
            'wpa2_enterprise' => 'WPA2 Enterprise',
            'wpa3_enterprise' => 'WPA3 Enterprise',
        ];
    }

    /**
     * Get available bandwidth options.
     */
    public static function getBandwidthOptions()
    {
        return [
            '10Mbps' => '10 Mbps',
            '25Mbps' => '25 Mbps',
            '50Mbps' => '50 Mbps',
            '100Mbps' => '100 Mbps',
            '250Mbps' => '250 Mbps',
            '500Mbps' => '500 Mbps',
            '1Gbps' => '1 Gbps',
            '2Gbps' => '2 Gbps',
            '5Gbps' => '5 Gbps',
            '10Gbps' => '10 Gbps',
            'other' => 'Other',
        ];
    }
}