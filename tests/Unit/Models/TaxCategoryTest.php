<?php

namespace Tests\Unit\Models;

use App\Models\TaxCategory;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tax_category_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxCategoryFactory')) {
            $this->markTestSkipped('TaxCategoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxCategory::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxCategory::class, $model);
    }

    public function test_tax_category_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxCategoryFactory')) {
            $this->markTestSkipped('TaxCategoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxCategory::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_category_has_fillable_attributes(): void
    {
        $model = new TaxCategory();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
