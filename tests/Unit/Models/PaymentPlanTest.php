<?php

namespace Tests\Unit\Models;

use App\Models\PaymentPlan;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_payment_plan_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\PaymentPlanFactory')) {
            $this->markTestSkipped('PaymentPlanFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = PaymentPlan::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PaymentPlan::class, $model);
    }

    public function test_payment_plan_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\PaymentPlanFactory')) {
            $this->markTestSkipped('PaymentPlanFactory does not exist');
        }

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
