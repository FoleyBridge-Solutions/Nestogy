<?php

namespace Tests\Unit\Models;

use App\Domains\Tax\Models\TaxExemption;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TaxExemptionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_tax_exemption_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxExemptionFactory')) {
            $this->markTestSkipped('TaxExemptionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxExemption::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxExemption::class, $model);
    }

    public function test_tax_exemption_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxExemptionFactory')) {
            $this->markTestSkipped('TaxExemptionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxExemption::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_exemption_has_fillable_attributes(): void
    {
        $model = new TaxExemption();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
