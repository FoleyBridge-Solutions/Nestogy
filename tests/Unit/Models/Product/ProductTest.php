<?php

namespace Tests\Unit\Models\Product;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    public function test_can_create_product(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $product->company);
        $this->assertEquals($this->company->id, $product->company->id);
    }

    public function test_casts_price_as_decimal(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'price' => 99.99,
        ]);

        $this->assertEquals(99.99, $product->price);
    }

    public function test_casts_boolean_fields(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'is_taxable' => true,
        ]);

        $this->assertIsBool($product->is_active);
        $this->assertIsBool($product->is_taxable);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->is_taxable);
    }
}
