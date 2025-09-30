<?php

namespace App\Domains\Security\Services;

use App\Domains\Core\Services\BaseService;
use App\Domains\Security\Models\IpLookupLog;
use App\Models\AuditLog;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpLookupService extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = IpLookupLog::class;
        $this->defaultEagerLoad = ['company'];
        $this->searchableFields = ['ip_address', 'country', 'city', 'isp'];
    }

    public function lookupIp(string $ipAddress, bool $forceRefresh = false): ?IpLookupLog
    {
        if (! $this->isValidIp($ipAddress)) {
            return null;
        }

        $companyId = auth()->user()->company_id ?? 1;

        $existingRecord = IpLookupLog::where('ip_address', $ipAddress)
            ->where('company_id', $companyId)
            ->first();

        if ($existingRecord && ! $existingRecord->isExpired() && ! $forceRefresh) {
            $existingRecord->increment('lookup_count');
            $existingRecord->update(['last_lookup_at' => now()]);

            return $existingRecord;
        }

        try {
            $lookupData = $this->performLookup($ipAddress);

            if (! $lookupData) {
                return null;
            }

            if ($existingRecord) {
                return $this->updateExistingRecord($existingRecord, $lookupData);
            } else {
                return $this->createNewRecord($ipAddress, $companyId, $lookupData);
            }
        } catch (Exception $e) {
            Log::error('IP lookup failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
                'company_id' => $companyId,
            ]);

            AuditLog::logSecurity('IP Lookup Failed', [
                'ip_address' => $ipAddress,
                'error' => $e->getMessage(),
            ], AuditLog::SEVERITY_ERROR);

            return $existingRecord;
        }
    }

    protected function performLookup(string $ipAddress): ?array
    {
        $apiKey = config('services.api_ninjas.key');

        if (! $apiKey) {
            Log::warning('API Ninjas key not configured, falling back to other services');

            return $this->fallbackLookup($ipAddress);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                ])
                ->get('https://api.api-ninjas.com/v1/iplookup', [
                    'address' => $ipAddress,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['is_valid']) && $data['is_valid']) {
                    return $this->normalizeApiNinjasResponse($data);
                }
            }

            Log::warning('API Ninjas lookup failed', [
                'ip' => $ipAddress,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

        } catch (Exception $e) {
            Log::error('API Ninjas request failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->fallbackLookup($ipAddress);
    }

    protected function fallbackLookup(string $ipAddress): ?array
    {
        $services = [
            'ipapi' => fn () => $this->lookupViaIpApi($ipAddress),
            'ipgeolocation' => fn () => $this->lookupViaIpGeolocation($ipAddress),
        ];

        foreach ($services as $serviceName => $lookup) {
            try {
                $result = $lookup();
                if ($result) {
                    Log::info("IP lookup successful via {$serviceName}", ['ip' => $ipAddress]);

                    return $result;
                }
            } catch (Exception $e) {
                Log::warning("Fallback service {$serviceName} failed", [
                    'ip' => $ipAddress,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected function lookupViaIpApi(string $ipAddress): ?array
    {
        $response = Http::timeout(5)->get("http://ip-api.com/json/{$ipAddress}", [
            'fields' => 'status,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,proxy',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if ($data['status'] === 'success') {
                return [
                    'is_valid' => true,
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'region_code' => $data['region'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'is_proxy' => $data['proxy'] ?? false,
                    'is_vpn' => false,
                    'is_tor' => false,
                    'threat_level' => $this->calculateThreatLevel($data),
                    'lookup_source' => IpLookupLog::LOOKUP_SOURCE_IPAPI,
                    'api_response' => $data,
                ];
            }
        }

        return null;
    }

    protected function lookupViaIpGeolocation(string $ipAddress): ?array
    {
        $apiKey = config('services.ipgeolocation.key');

        if (! $apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://api.ipgeolocation.io/ipgeo', [
            'apiKey' => $apiKey,
            'ip' => $ipAddress,
            'fields' => 'country_name,country_code2,state_prov,district,city,zipcode,latitude,longitude,time_zone,isp',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'is_valid' => true,
                'country' => $data['country_name'] ?? null,
                'country_code' => $data['country_code2'] ?? null,
                'region' => $data['state_prov'] ?? null,
                'region_code' => null,
                'city' => $data['city'] ?? null,
                'zip' => $data['zipcode'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'timezone' => $data['time_zone']['name'] ?? null,
                'isp' => $data['isp'] ?? null,
                'is_proxy' => false,
                'is_vpn' => false,
                'is_tor' => false,
                'threat_level' => IpLookupLog::THREAT_LEVEL_LOW,
                'lookup_source' => 'ipgeolocation',
                'api_response' => $data,
            ];
        }

        return null;
    }

    protected function normalizeApiNinjasResponse(array $data): array
    {
        return [
            'is_valid' => $data['is_valid'] ?? true,
            'country' => $data['country'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'region' => $data['region'] ?? null,
            'region_code' => $data['region_code'] ?? null,
            'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null,
            'latitude' => $data['lat'] ?? null,
            'longitude' => $data['lon'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'isp' => $data['isp'] ?? null,
            'is_proxy' => false,
            'is_vpn' => false,
            'is_tor' => false,
            'threat_level' => $this->calculateThreatLevel($data),
            'lookup_source' => IpLookupLog::LOOKUP_SOURCE_API_NINJAS,
            'api_response' => $data,
        ];
    }

    protected function calculateThreatLevel(array $data): string
    {
        $threatScore = 0;

        $riskyCountries = config('security.geo_blocking.high_risk_countries', []);
        $countryCode = $data['country_code'] ?? $data['countryCode'] ?? '';

        if (in_array(strtoupper($countryCode), $riskyCountries)) {
            $threatScore += 30;
        }

        $riskyIsps = [
            'tor',
            'proxy',
            'vpn',
            'hosting',
            'datacenter',
            'cloud',
        ];

        $isp = strtolower($data['isp'] ?? '');
        foreach ($riskyIsps as $riskyIsp) {
            if (str_contains($isp, $riskyIsp)) {
                $threatScore += 40;
                break;
            }
        }

        if ($data['proxy'] ?? false) {
            $threatScore += 50;
        }

        if ($threatScore >= 70) {
            return IpLookupLog::THREAT_LEVEL_CRITICAL;
        } elseif ($threatScore >= 50) {
            return IpLookupLog::THREAT_LEVEL_HIGH;
        } elseif ($threatScore >= 30) {
            return IpLookupLog::THREAT_LEVEL_MEDIUM;
        } else {
            return IpLookupLog::THREAT_LEVEL_LOW;
        }
    }

    protected function updateExistingRecord(IpLookupLog $record, array $lookupData): IpLookupLog
    {
        $record->update([
            'country' => $lookupData['country'],
            'country_code' => $lookupData['country_code'],
            'region' => $lookupData['region'],
            'region_code' => $lookupData['region_code'],
            'city' => $lookupData['city'],
            'zip' => $lookupData['zip'],
            'latitude' => $lookupData['latitude'],
            'longitude' => $lookupData['longitude'],
            'timezone' => $lookupData['timezone'],
            'isp' => $lookupData['isp'],
            'is_valid' => $lookupData['is_valid'],
            'is_vpn' => $lookupData['is_vpn'],
            'is_proxy' => $lookupData['is_proxy'],
            'is_tor' => $lookupData['is_tor'],
            'threat_level' => $lookupData['threat_level'],
            'lookup_source' => $lookupData['lookup_source'],
            'api_response' => $lookupData['api_response'],
            'cached_until' => now()->addHours(config('security.ip_lookup.cache_hours', 24)),
            'lookup_count' => $record->lookup_count + 1,
            'last_lookup_at' => now(),
        ]);

        return $record;
    }

    protected function createNewRecord(string $ipAddress, int $companyId, array $lookupData): IpLookupLog
    {
        return IpLookupLog::create([
            'company_id' => $companyId,
            'ip_address' => $ipAddress,
            'country' => $lookupData['country'],
            'country_code' => $lookupData['country_code'],
            'region' => $lookupData['region'],
            'region_code' => $lookupData['region_code'],
            'city' => $lookupData['city'],
            'zip' => $lookupData['zip'],
            'latitude' => $lookupData['latitude'],
            'longitude' => $lookupData['longitude'],
            'timezone' => $lookupData['timezone'],
            'isp' => $lookupData['isp'],
            'is_valid' => $lookupData['is_valid'],
            'is_vpn' => $lookupData['is_vpn'],
            'is_proxy' => $lookupData['is_proxy'],
            'is_tor' => $lookupData['is_tor'],
            'threat_level' => $lookupData['threat_level'],
            'lookup_source' => $lookupData['lookup_source'],
            'api_response' => $lookupData['api_response'],
            'cached_until' => now()->addHours(config('security.ip_lookup.cache_hours', 24)),
            'lookup_count' => 1,
            'last_lookup_at' => now(),
        ]);
    }

    public function enrichAuditLogWithIpData(string $ipAddress): array
    {
        $lookup = $this->lookupIp($ipAddress);

        if (! $lookup) {
            return ['ip_enrichment' => 'failed'];
        }

        return [
            'ip_location' => $lookup->getLocationString(),
            'ip_country_code' => $lookup->country_code,
            'ip_isp' => $lookup->isp,
            'ip_threat_level' => $lookup->threat_level,
            'ip_threat_score' => $lookup->getThreatScore(),
            'ip_is_suspicious' => $lookup->isSuspicious(),
            'ip_coordinates' => $lookup->latitude && $lookup->longitude
                ? "{$lookup->latitude},{$lookup->longitude}"
                : null,
        ];
    }

    public function getSuspiciousIps(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return IpLookupLog::where('company_id', auth()->user()->company_id)
            ->bySuspicious(true)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('threat_level', 'desc')
            ->orderBy('lookup_count', 'desc')
            ->get();
    }

    public function getCountryStats(): array
    {
        return IpLookupLog::where('company_id', auth()->user()->company_id)
            ->selectRaw('country_code, country, COUNT(*) as lookup_count, MAX(threat_level) as max_threat')
            ->whereNotNull('country_code')
            ->groupBy(['country_code', 'country'])
            ->orderBy('lookup_count', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function cleanupOldRecords(int $days = 90): int
    {
        return IpLookupLog::where('company_id', auth()->user()->company_id)
            ->where('created_at', '<', now()->subDays($days))
            ->where('lookup_count', 1)
            ->delete();
    }

    protected function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    public function isIpBlacklisted(string $ipAddress): bool
    {
        $lookup = $this->lookupIp($ipAddress);

        if (! $lookup) {
            return false;
        }

        if ($lookup->threat_level === IpLookupLog::THREAT_LEVEL_CRITICAL) {
            return true;
        }

        if ($lookup->is_tor || ($lookup->is_vpn && config('security.block_vpn', false))) {
            return true;
        }

        $blockedCountries = config('security.geo_blocking.blocked_countries', []);
        if (! empty($blockedCountries) && in_array($lookup->country_code, $blockedCountries)) {
            return true;
        }

        return false;
    }
}
