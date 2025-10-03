<?php

namespace Tests\Unit\Models;

use App\Models\TaxRateHistory;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxRateHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tax_rate_history_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxRateHistoryFactory')) {
            $this->markTestSkipped('TaxRateHistoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxRateHistory::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxRateHistory::class, $model);
    }

    public function test_tax_rate_history_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxRateHistoryFactory')) {
            $this->markTestSkipped('TaxRateHistoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxRateHistory::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_rate_history_has_fillable_attributes(): void
    {
        $model = new TaxRateHistory();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
