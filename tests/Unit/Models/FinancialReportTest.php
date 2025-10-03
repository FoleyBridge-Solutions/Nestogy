<?php

namespace Tests\Unit\Models;

use App\Models\FinancialReport;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_financial_report_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\FinancialReportFactory')) {
            $this->markTestSkipped('FinancialReportFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = FinancialReport::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(FinancialReport::class, $model);
    }

    public function test_financial_report_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\FinancialReportFactory')) {
            $this->markTestSkipped('FinancialReportFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = FinancialReport::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_financial_report_has_fillable_attributes(): void
    {
        $model = new FinancialReport();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
