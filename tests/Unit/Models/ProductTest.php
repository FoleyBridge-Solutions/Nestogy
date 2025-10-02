<?php

namespace Tests\Unit\Models;

use App\Models\Product;

class ProductTest extends ModelTestCase
{

    public function test_can_create_product_with_factory(): void
    {
        $company = $this->testCompany;
        $category = $this->testCategory;
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_belongs_to_company(): void
    {
        $company = $this->testCompany;
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\App\Models\Company::class, $product->company);
        $this->assertEquals($company->id, $product->company->id);
    }

    public function test_product_has_name_and_price(): void
    {
        $company = $this->testCompany;
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Product',
            'price' => 99.99,
        ]);

        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price);
    }

    public function test_product_has_fillable_attributes(): void
    {
        $fillable = (new Product)->getFillable();

        $expectedFillable = ['company_id', 'name', 'base_price', 'description'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_product_has_sku_field(): void
    {
        $company = $this->testCompany;
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'sku' => 'PROD-001',
        ]);

        $this->assertEquals('PROD-001', $product->sku);
    }

    public function test_product_has_active_status(): void
    {
        $company = $this->testCompany;
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->assertTrue($product->is_active);
    }

    public function test_product_can_be_inactive(): void
    {
        $company = $this->testCompany;
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'is_active' => false,
        ]);

        $this->assertFalse($product->is_active);
    }

    public function test_product_has_timestamps(): void
    {
        $company = $this->testCompany;
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
    }

    public function test_belongs_to_category()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
        ]);

        $this->assertInstanceOf(\App\Models\Category::class, $product->category);
        $this->assertEquals($this->testCategory->id, $product->category->id);
    }

    public function test_price_accessor_returns_base_price()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'base_price' => 150.00,
        ]);

        $this->assertEquals(150.00, $product->price);
    }

    public function test_formatted_price()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'base_price' => 123.45,
            'currency_code' => 'USD',
        ]);

        $this->assertEquals('$123.45', $product->getFormattedPrice());
    }

    public function test_currency_symbol()
    {
        $usdProduct = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'currency_code' => 'USD',
        ]);

        $this->assertEquals('$', $usdProduct->getCurrencySymbol());
    }

    public function test_profit_margin_calculation()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'base_price' => 100.00,
            'cost' => 60.00,
        ]);

        $this->assertEquals(40.00, $product->getProfitMargin());
    }

    public function test_markup_percentage_calculation()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'base_price' => 150.00,
            'cost' => 100.00,
        ]);

        $this->assertEquals(50.00, $product->getMarkupPercentage());
    }

    public function test_has_cost_method()
    {
        $productWithCost = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'cost' => 50.00,
        ]);

        $productNoCost = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'cost' => null,
        ]);

        $this->assertTrue($productWithCost->hasCost());
        $this->assertFalse($productNoCost->hasCost());
    }

    public function test_is_service_method()
    {
        $service = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'type' => 'service',
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'type' => 'product',
        ]);

        $this->assertTrue($service->isService());
        $this->assertFalse($product->isService());
    }

    public function test_scope_search()
    {
        Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'name' => 'Web Hosting Service',
        ]);

        Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'name' => 'Domain Registration',
        ]);

        $results = Product::search('Hosting')->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals('Web Hosting Service', $results->first()->name);
    }

    public function test_scope_by_price_range()
    {
        Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'base_price' => 50.00,
        ]);

        Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'base_price' => 150.00,
        ]);

        $results = Product::byPriceRange(100, 200)->get();

        $this->assertEquals(1, $results->count());
    }

    public function test_scope_active()
    {
        Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
            'is_active' => false,
        ]);

        $results = Product::active()->get();

        $this->assertEquals(1, $results->count());
    }

    public function test_soft_deletes()
    {
        $product = Product::factory()->create([
            'company_id' => $this->testCompany->id,
            'category_id' => $this->testCategory->id,
        ]);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}