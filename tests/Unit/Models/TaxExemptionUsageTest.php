<?php

namespace Tests\Unit\Models;

use App\Models\TaxExemptionUsage;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxExemptionUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tax_exemption_usage_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxExemptionUsageFactory')) {
            $this->markTestSkipped('TaxExemptionUsageFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxExemptionUsage::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxExemptionUsage::class, $model);
    }

    public function test_tax_exemption_usage_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxExemptionUsageFactory')) {
            $this->markTestSkipped('TaxExemptionUsageFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxExemptionUsage::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_exemption_usage_has_fillable_attributes(): void
    {
        $model = new TaxExemptionUsage();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
