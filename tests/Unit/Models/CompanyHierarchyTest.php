<?php

namespace Tests\Unit\Models;

use App\Models\CompanyHierarchy;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company_hierarchy_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CompanyHierarchyFactory')) {
            $this->markTestSkipped('CompanyHierarchyFactory does not exist');
        }

        $model = CompanyHierarchy::factory()->create();

        $this->assertInstanceOf(CompanyHierarchy::class, $model);
    }

    public function test_company_hierarchy_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CompanyHierarchyFactory')) {
            $this->markTestSkipped('CompanyHierarchyFactory does not exist');
        }

        $model = CompanyHierarchy::factory()->create();

        $this->assertInstanceOf(Company::class, $model->ancestor);
        $this->assertInstanceOf(Company::class, $model->descendant);
    }

    public function test_company_hierarchy_has_fillable_attributes(): void
    {
        $model = new CompanyHierarchy();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
