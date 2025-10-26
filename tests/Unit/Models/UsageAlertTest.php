<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\UsageAlert;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class UsageAlertTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_usage_alert_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = UsageAlert::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsageAlert::class, $model);
    }

    public function test_usage_alert_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = UsageAlert::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_usage_alert_has_fillable_attributes(): void
    {
        $model = new UsageAlert();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
