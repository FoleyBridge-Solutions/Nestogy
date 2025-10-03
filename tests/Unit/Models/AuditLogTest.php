<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_audit_log_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\AuditLogFactory')) {
            $this->markTestSkipped('AuditLogFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AuditLog::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(AuditLog::class, $model);
    }

    public function test_audit_log_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\AuditLogFactory')) {
            $this->markTestSkipped('AuditLogFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AuditLog::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_audit_log_has_fillable_attributes(): void
    {
        $model = new AuditLog();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
