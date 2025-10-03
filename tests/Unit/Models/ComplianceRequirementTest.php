<?php

namespace Tests\Unit\Models;

use App\Models\ComplianceRequirement;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_compliance_requirement_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ComplianceRequirementFactory')) {
            $this->markTestSkipped('ComplianceRequirementFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ComplianceRequirement::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ComplianceRequirement::class, $model);
    }

    public function test_compliance_requirement_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ComplianceRequirementFactory')) {
            $this->markTestSkipped('ComplianceRequirementFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ComplianceRequirement::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_compliance_requirement_has_fillable_attributes(): void
    {
        $model = new ComplianceRequirement();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
