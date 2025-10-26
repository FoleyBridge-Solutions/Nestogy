<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\UsageTier;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class UsageTierTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_usage_tier_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = UsageTier::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsageTier::class, $model);
    }

    public function test_usage_tier_belongs_to_company(): void
    {
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
