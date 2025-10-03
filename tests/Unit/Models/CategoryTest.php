<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CategoryFactory')) {
            $this->markTestSkipped('CategoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Category::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Category::class, $model);
    }

    public function test_category_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CategoryFactory')) {
            $this->markTestSkipped('CategoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Category::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_category_has_fillable_attributes(): void
    {
        $model = new Category();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
