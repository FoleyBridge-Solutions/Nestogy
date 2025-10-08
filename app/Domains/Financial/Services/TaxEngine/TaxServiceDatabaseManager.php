<?php

namespace App\Domains\Financial\Services\TaxEngine;

use Illuminate\Support\Facades\DB;

class TaxServiceDatabaseManager
{
    protected ?int $companyId = null;

    protected string $stateCode;

    protected int $batchSize;

    public function __construct(string $stateCode, ?int $companyId = null, array $config = [])
    {
        $this->stateCode = $stateCode;
        $this->companyId = $companyId;
        $this->batchSize = $config['batch_size'] ?? 1000;
    }

    public function bulkInsertTaxRates(array $rates): int
    {
        $inserted = 0;
        $chunks = array_chunk($rates, $this->batchSize);

        foreach ($chunks as $chunk) {
            DB::table('service_tax_rates')->insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    public function createOrGetJurisdiction(array $data): int
    {
        $existing = DB::table('tax_jurisdictions')
            ->where('code', $data['code'])
            ->where('state_code', $this->stateCode)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('tax_jurisdictions')->insertGetId(array_merge($data, [
            'company_id' => $this->companyId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
    }
}
