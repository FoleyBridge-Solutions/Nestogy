<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\CommunicationLog;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class CommunicationLogTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_communication_log_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = CommunicationLog::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CommunicationLog::class, $model);
    }

    public function test_communication_log_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = CommunicationLog::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_communication_log_has_fillable_attributes(): void
    {
        $model = new CommunicationLog();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
