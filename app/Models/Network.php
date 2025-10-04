<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Network Model
 *
 * Represents network configurations for client locations.
 * Manages VLAN, IP ranges, gateways, and DHCP configurations.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $vlan
 * @property string $network
 * @property string $gateway
 * @property string|null $dhcp_range
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $accessed_at
 * @property int|null $location_id
 * @property int $client_id
 */
class Network extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'networks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'vlan',
        'network',
        'gateway',
        'dhcp_range',
        'notes',
        'location_id',
        'client_id',
        'accessed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'vlan' => 'integer',
        'location_id' => 'integer',
        'client_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'accessed_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get the client that owns the network.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the location this network is associated with.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get assets on this network.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Check if network is archived.
     */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Check if network has VLAN configured.
     */
    public function hasVlan(): bool
    {
        return ! is_null($this->vlan);
    }

    /**
     * Check if network has DHCP range configured.
     */
    public function hasDhcpRange(): bool
    {
        return ! empty($this->dhcp_range);
    }

    /**
     * Get network information as array.
     */
    public function getNetworkInfo(): array
    {
        return [
            'network' => $this->network,
            'gateway' => $this->gateway,
            'vlan' => $this->vlan,
            'dhcp_range' => $this->dhcp_range,
        ];
    }

    /**
     * Get formatted network display.
     */
    public function getFormattedNetwork(): string
    {
        $info = $this->network;

        if ($this->vlan) {
            $info .= ' (VLAN '.$this->vlan.')';
        }

        return $info;
    }

    /**
     * Parse CIDR network to get network details.
     */
    public function getNetworkDetails(): array
    {
        if (! $this->network || ! str_contains($this->network, '/')) {
            return [];
        }

        [$network, $prefix] = explode('/', $this->network);
        $prefix = (int) $prefix;

        // Calculate subnet mask
        $mask = str_repeat('1', $prefix).str_repeat('0', 32 - $prefix);
        $mask = chunk_split($mask, 8, '.');
        $mask = rtrim($mask, '.');
        $subnetMask = implode('.', array_map(function ($octet) {
            return bindec($octet);
        }, explode('.', $mask)));

        // Calculate network and broadcast addresses
        $networkLong = ip2long($network);
        $maskLong = ip2long($subnetMask);
        $broadcastLong = $networkLong | (~$maskLong);

        return [
            'network_address' => $network,
            'subnet_mask' => $subnetMask,
            'prefix_length' => $prefix,
            'broadcast_address' => long2ip($broadcastLong),
            'first_host' => long2ip($networkLong + 1),
            'last_host' => long2ip($broadcastLong - 1),
            'total_hosts' => pow(2, 32 - $prefix) - 2,
        ];
    }

    /**
     * Check if an IP address is within this network.
     */
    public function containsIp(string $ip): bool
    {
        if (! $this->network || ! str_contains($this->network, '/')) {
            return false;
        }

        [$network, $prefix] = explode('/', $this->network);
        $prefix = (int) $prefix;

        $networkLong = ip2long($network);
        $ipLong = ip2long($ip);
        $mask = -1 << (32 - $prefix);

        return ($networkLong & $mask) === ($ipLong & $mask);
    }

    /**
     * Get available IP addresses in this network.
     */
    public function getAvailableIps(): array
    {
        $details = $this->getNetworkDetails();
        if (empty($details)) {
            return [];
        }

        $usedIps = $this->assets()->whereNotNull('ip')->pluck('ip')->toArray();
        $usedIps[] = $this->gateway;

        $available = [];
        $start = ip2long($details['first_host']);
        $end = ip2long($details['last_host']);

        for ($i = $start; $i <= $end; $i++) {
            $ip = long2ip($i);
            if (! in_array($ip, $usedIps)) {
                $available[] = $ip;
            }
        }

        return $available;
    }

    /**
     * Get next available IP address.
     */
    public function getNextAvailableIp(): ?string
    {
        $available = $this->getAvailableIps();

        return $available[0] ?? null;
    }

    /**
     * Update last accessed timestamp.
     */
    public function updateAccessedAt(): void
    {
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Get asset count on this network.
     */
    public function getAssetCount(): int
    {
        return $this->assets()->count();
    }

    /**
     * Get used IP count.
     */
    public function getUsedIpCount(): int
    {
        return $this->assets()->whereNotNull('ip')->count();
    }

    /**
     * Get network utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        $details = $this->getNetworkDetails();
        if (empty($details) || $details['total_hosts'] <= 0) {
            return 0;
        }

        $usedCount = $this->getUsedIpCount() + 1; // +1 for gateway

        return round(($usedCount / $details['total_hosts']) * 100, 2);
    }

    /**
     * Scope to search networks.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('network', 'like', '%'.$search.'%')
                ->orWhere('gateway', 'like', '%'.$search.'%')
                ->orWhere('vlan', $search);
        });
    }

    /**
     * Scope to get networks by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get networks by location.
     */
    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope to get networks by VLAN.
     */
    public function scopeByVlan($query, int $vlan)
    {
        return $query->where('vlan', $vlan);
    }

    /**
     * Scope to get networks with VLAN configured.
     */
    public function scopeWithVlan($query)
    {
        return $query->whereNotNull('vlan');
    }

    /**
     * Scope to get networks with DHCP configured.
     */
    public function scopeWithDhcp($query)
    {
        return $query->whereNotNull('dhcp_range');
    }

    /**
     * Scope to get recently accessed networks.
     */
    public function scopeRecentlyAccessed($query, int $days = 30)
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Get validation rules for network creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vlan' => 'nullable|integer|min:1|max:4094',
            'network' => 'required|string|regex:/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}\/[0-9]{1,2}$/',
            'gateway' => 'required|ip',
            'dhcp_range' => 'nullable|string',
            'notes' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:locations,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get validation rules for network update.
     */
    public static function getUpdateValidationRules(int $networkId): array
    {
        return self::getValidationRules();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update accessed_at when network is retrieved
        static::retrieved(function ($network) {
            if (! $network->wasRecentlyCreated) {
                $network->updateAccessedAt();
            }
        });

        // Validate network configuration before saving
        static::saving(function ($network) {
            // Validate that gateway is within the network range
            if ($network->gateway && ! $network->containsIp($network->gateway)) {
                throw new \InvalidArgumentException('Gateway IP must be within the network range');
            }
        });
    }
}
