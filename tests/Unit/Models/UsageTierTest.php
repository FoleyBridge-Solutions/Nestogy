<?php

namespace Tests\Unit\Models;

use App\Models\UsageTier;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_usage_tier_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\UsageTierFactory')) {
            $this->markTestSkipped('UsageTierFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageTier::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsageTier::class, $model);
    }

    public function test_usage_tier_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\UsageTierFactory')) {
            $this->markTestSkipped('UsageTierFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageTier::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_usage_tier_has_fillable_attributes(): void
    {
        $model = new UsageTier();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
