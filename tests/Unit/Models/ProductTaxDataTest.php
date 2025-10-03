<?php

namespace Tests\Unit\Models;

use App\Models\ProductTaxData;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTaxDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product_tax_data_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ProductTaxDataFactory')) {
            $this->markTestSkipped('ProductTaxDataFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ProductTaxData::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ProductTaxData::class, $model);
    }

    public function test_product_tax_data_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ProductTaxDataFactory')) {
            $this->markTestSkipped('ProductTaxDataFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ProductTaxData::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_product_tax_data_has_fillable_attributes(): void
    {
        $model = new ProductTaxData();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
