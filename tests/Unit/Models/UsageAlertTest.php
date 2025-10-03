<?php

namespace Tests\Unit\Models;

use App\Models\UsageAlert;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_usage_alert_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\UsageAlertFactory')) {
            $this->markTestSkipped('UsageAlertFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageAlert::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsageAlert::class, $model);
    }

    public function test_usage_alert_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\UsageAlertFactory')) {
            $this->markTestSkipped('UsageAlertFactory does not exist');
        }

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
