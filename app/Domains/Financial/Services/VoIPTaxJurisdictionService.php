<?php

namespace App\Domains\Financial\Services;

use App\Models\TaxCategory;
use App\Models\TaxJurisdiction;
use Illuminate\Support\Facades\Cache;

class VoIPTaxJurisdictionService
{
    protected ?int $companyId = null;

    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_ttl' => 3600,
            'enable_caching' => true,
        ], $config);
    }

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function detectJurisdictions(array $address): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($address)) {
            return TaxJurisdiction::where('company_id', $this->companyId)
                ->federal()
                ->active()
                ->get();
        }

        $cacheKey = 'jurisdictions:'.md5(json_encode($address)).':'.$this->companyId;

        if ($this->config['enable_caching'] && Cache::has($cacheKey)) {
            $jurisdictionIds = Cache::get($cacheKey);

            return TaxJurisdiction::whereIn('id', $jurisdictionIds)->get();
        }

        $jurisdictions = TaxJurisdiction::findByAddress($address)
            ->where('company_id', $this->companyId);

        if ($this->config['enable_caching']) {
            Cache::put($cacheKey, $jurisdictions->pluck('id')->toArray(), $this->config['cache_ttl']);
        }

        return $jurisdictions;
    }

    public function findTaxCategory(string $serviceType): ?TaxCategory
    {
        return TaxCategory::where('company_id', $this->companyId)
            ->active()
            ->where(function ($query) use ($serviceType) {
                $query->whereNull('service_types')
                    ->orWhereJsonContains('service_types', $serviceType);
            })
            ->orderBy('priority')
            ->first();
    }
}
