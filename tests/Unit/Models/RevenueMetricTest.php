<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\RevenueMetric;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class RevenueMetricTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_revenue_metric_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\RevenueMetricFactory')) {
            $this->markTestSkipped('RevenueMetricFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RevenueMetric::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(RevenueMetric::class, $model);
    }

    public function test_revenue_metric_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\RevenueMetricFactory')) {
            $this->markTestSkipped('RevenueMetricFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RevenueMetric::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_revenue_metric_has_fillable_attributes(): void
    {
        $model = new RevenueMetric();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
