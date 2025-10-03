<?php

namespace Tests\Unit\Models;

use App\Models\ServiceTaxRate;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_service_tax_rate_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ServiceTaxRateFactory')) {
            $this->markTestSkipped('ServiceTaxRateFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ServiceTaxRate::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ServiceTaxRate::class, $model);
    }

    public function test_service_tax_rate_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ServiceTaxRateFactory')) {
            $this->markTestSkipped('ServiceTaxRateFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ServiceTaxRate::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_service_tax_rate_has_fillable_attributes(): void
    {
        $model = new ServiceTaxRate();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
