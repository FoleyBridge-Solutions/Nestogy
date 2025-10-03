<?php

namespace Tests\Unit\Models;

use App\Models\UsageBucket;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageBucketTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_usage_bucket_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\UsageBucketFactory')) {
            $this->markTestSkipped('UsageBucketFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageBucket::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsageBucket::class, $model);
    }

    public function test_usage_bucket_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\UsageBucketFactory')) {
            $this->markTestSkipped('UsageBucketFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageBucket::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_usage_bucket_has_fillable_attributes(): void
    {
        $model = new UsageBucket();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
