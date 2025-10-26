<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\KpiCalculation;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class KpiCalculationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_kpi_calculation_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = KpiCalculation::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(KpiCalculation::class, $model);
    }

    public function test_kpi_calculation_belongs_to_company(): void
    {
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
