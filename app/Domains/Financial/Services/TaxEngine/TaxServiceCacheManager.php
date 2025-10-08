<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaxServiceCacheManager
{
    protected ?int $companyId = null;

    protected string $stateCode;

    protected int $cacheTtl;

    protected bool $enableCaching;

    public function __construct(string $stateCode, ?int $companyId = null, array $config = [])
    {
        $this->stateCode = $stateCode;
        $this->companyId = $companyId;
        $this->cacheTtl = $config['cache_ttl'] ?? 3600;
        $this->enableCaching = $config['enable_caching'] ?? true;
    }

    public function generateCacheKey(string $operation, array $params = []): string
    {
        $keyData = array_merge([
            'company_id' => $this->companyId,
            'state' => $this->stateCode,
            'operation' => $operation,
        ], $params);

        return 'tax_'.md5(json_encode($keyData));
    }

    public function getCachedData(string $key, callable $callback)
    {
        if (! $this->enableCaching) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheTtl, $callback);
    }

    public function clearCache(): void
    {
        $pattern = "tax_*{$this->stateCode}*";

        if (config('cache.default') === 'redis') {
            $prefix = config('cache.prefix', '');
            $fullPattern = $prefix.$pattern;
            $keys = Cache::getRedis()->keys($fullPattern);
            if (! empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            Cache::flush();
        }

        Log::info("Cache cleared for {$this->stateCode} tax service", [
            'company_id' => $this->companyId,
            'pattern' => $pattern,
        ]);
    }

    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
    }
}
