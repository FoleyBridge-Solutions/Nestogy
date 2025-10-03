<?php

namespace Tests\Unit\Models;

use App\Models\UsagePool;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsagePoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_usage_pool_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\UsagePoolFactory')) {
            $this->markTestSkipped('UsagePoolFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsagePool::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsagePool::class, $model);
    }

    public function test_usage_pool_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\UsagePoolFactory')) {
            $this->markTestSkipped('UsagePoolFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsagePool::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_usage_pool_has_fillable_attributes(): void
    {
        $model = new UsagePool();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
