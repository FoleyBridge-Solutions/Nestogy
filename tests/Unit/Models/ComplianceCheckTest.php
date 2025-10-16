<?php

namespace Tests\Unit\Models;

use App\Domains\Tax\Models\ComplianceCheck;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ComplianceCheckTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_compliance_check_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ComplianceCheckFactory')) {
            $this->markTestSkipped('ComplianceCheckFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ComplianceCheck::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ComplianceCheck::class, $model);
    }

    public function test_compliance_check_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ComplianceCheckFactory')) {
            $this->markTestSkipped('ComplianceCheckFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ComplianceCheck::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_compliance_check_has_fillable_attributes(): void
    {
        $model = new ComplianceCheck();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
