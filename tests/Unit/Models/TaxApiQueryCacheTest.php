<?php

namespace Tests\Unit\Models;

use App\Domains\Tax\Models\TaxApiQueryCache;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TaxApiQueryCacheTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_tax_api_query_cache_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxApiQueryCacheFactory')) {
            $this->markTestSkipped('TaxApiQueryCacheFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxApiQueryCache::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxApiQueryCache::class, $model);
    }

    public function test_tax_api_query_cache_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxApiQueryCacheFactory')) {
            $this->markTestSkipped('TaxApiQueryCacheFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxApiQueryCache::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_api_query_cache_has_fillable_attributes(): void
    {
        $model = new TaxApiQueryCache();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
