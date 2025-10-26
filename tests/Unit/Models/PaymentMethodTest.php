<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\PaymentMethod;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_payment_method_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = PaymentMethod::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PaymentMethod::class, $model);
    }

    public function test_payment_method_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = PaymentMethod::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_payment_method_has_fillable_attributes(): void
    {
        $model = new PaymentMethod();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
