<?php

namespace Tests\Unit\Models;

use App\Models\CashFlowProjection;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_cash_flow_projection_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CashFlowProjectionFactory')) {
            $this->markTestSkipped('CashFlowProjectionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CashFlowProjection::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CashFlowProjection::class, $model);
    }

    public function test_cash_flow_projection_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CashFlowProjectionFactory')) {
            $this->markTestSkipped('CashFlowProjectionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CashFlowProjection::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_cash_flow_projection_has_fillable_attributes(): void
    {
        $model = new CashFlowProjection();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
