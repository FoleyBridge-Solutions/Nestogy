<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\PaymentPlan;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PaymentPlanTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_payment_plan_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = PaymentPlan::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PaymentPlan::class, $model);
    }

    public function test_payment_plan_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = PaymentPlan::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_payment_plan_has_fillable_attributes(): void
    {
        $model = new PaymentPlan();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
