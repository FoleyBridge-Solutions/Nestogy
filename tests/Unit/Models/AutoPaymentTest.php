<?php

namespace Tests\Unit\Models;

use App\Models\AutoPayment;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_auto_payment_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\AutoPaymentFactory')) {
            $this->markTestSkipped('AutoPaymentFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AutoPayment::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(AutoPayment::class, $model);
    }

    public function test_auto_payment_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\AutoPaymentFactory')) {
            $this->markTestSkipped('AutoPaymentFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AutoPayment::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_auto_payment_has_fillable_attributes(): void
    {
        $model = new AutoPayment();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
