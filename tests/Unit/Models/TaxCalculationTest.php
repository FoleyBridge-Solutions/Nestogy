<?php

namespace Tests\Unit\Models;

use App\Domains\Tax\Models\TaxCalculation;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TaxCalculationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_tax_calculation_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxCalculationFactory')) {
            $this->markTestSkipped('TaxCalculationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxCalculation::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxCalculation::class, $model);
    }

    public function test_tax_calculation_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxCalculationFactory')) {
            $this->markTestSkipped('TaxCalculationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxCalculation::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_calculation_has_fillable_attributes(): void
    {
        $model = new TaxCalculation();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
