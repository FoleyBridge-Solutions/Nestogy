<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\AnalyticsSnapshot;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class AnalyticsSnapshotTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_analytics_snapshot_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = AnalyticsSnapshot::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(AnalyticsSnapshot::class, $model);
    }

    public function test_analytics_snapshot_belongs_to_company(): void
    {
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
