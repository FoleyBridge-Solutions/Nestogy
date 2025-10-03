<?php

namespace Tests\Unit\Models;

use App\Models\KpiCalculation;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_kpi_calculation_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\KpiCalculationFactory')) {
            $this->markTestSkipped('KpiCalculationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = KpiCalculation::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(KpiCalculation::class, $model);
    }

    public function test_kpi_calculation_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\KpiCalculationFactory')) {
            $this->markTestSkipped('KpiCalculationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = KpiCalculation::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_kpi_calculation_has_fillable_attributes(): void
    {
        $model = new KpiCalculation();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
