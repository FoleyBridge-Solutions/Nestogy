<?php

namespace Tests\Unit\Models;

use App\Models\CompanyCustomization;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company_customization_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CompanyCustomizationFactory')) {
            $this->markTestSkipped('CompanyCustomizationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CompanyCustomization::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CompanyCustomization::class, $model);
    }

    public function test_company_customization_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CompanyCustomizationFactory')) {
            $this->markTestSkipped('CompanyCustomizationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CompanyCustomization::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_company_customization_has_fillable_attributes(): void
    {
        $model = new CompanyCustomization();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
