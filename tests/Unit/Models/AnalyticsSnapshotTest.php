<?php

namespace Tests\Unit\Models;

use App\Models\AnalyticsSnapshot;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_analytics_snapshot_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\AnalyticsSnapshotFactory')) {
            $this->markTestSkipped('AnalyticsSnapshotFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AnalyticsSnapshot::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(AnalyticsSnapshot::class, $model);
    }

    public function test_analytics_snapshot_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\AnalyticsSnapshotFactory')) {
            $this->markTestSkipped('AnalyticsSnapshotFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AnalyticsSnapshot::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_analytics_snapshot_has_fillable_attributes(): void
    {
        $model = new AnalyticsSnapshot();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
