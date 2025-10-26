<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\ProductBundle;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ProductBundleTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_product_bundle_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = ProductBundle::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ProductBundle::class, $model);
    }

    public function test_product_bundle_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = ProductBundle::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_product_bundle_has_fillable_attributes(): void
    {
        $model = new ProductBundle();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
