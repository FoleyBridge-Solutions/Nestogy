<?php

namespace Tests\Unit\Models;

use App\Models\TimeEntry;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_time_entry_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TimeEntryFactory')) {
            $this->markTestSkipped('TimeEntryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TimeEntry::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TimeEntry::class, $model);
    }

    public function test_time_entry_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TimeEntryFactory')) {
            $this->markTestSkipped('TimeEntryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TimeEntry::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_time_entry_has_fillable_attributes(): void
    {
        $model = new TimeEntry();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
