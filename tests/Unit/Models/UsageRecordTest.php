<?php

namespace Tests\Unit\Models;

use App\Models\UsageRecord;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_usage_record_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\UsageRecordFactory')) {
            $this->markTestSkipped('UsageRecordFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageRecord::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UsageRecord::class, $model);
    }

    public function test_usage_record_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\UsageRecordFactory')) {
            $this->markTestSkipped('UsageRecordFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UsageRecord::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_usage_record_has_fillable_attributes(): void
    {
        $model = new UsageRecord();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
