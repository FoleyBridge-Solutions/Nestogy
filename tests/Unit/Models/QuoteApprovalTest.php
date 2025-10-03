<?php

namespace Tests\Unit\Models;

use App\Models\QuoteApproval;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_quote_approval_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\QuoteApprovalFactory')) {
            $this->markTestSkipped('QuoteApprovalFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteApproval::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(QuoteApproval::class, $model);
    }

    public function test_quote_approval_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\QuoteApprovalFactory')) {
            $this->markTestSkipped('QuoteApprovalFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteApproval::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_quote_approval_has_fillable_attributes(): void
    {
        $model = new QuoteApproval();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
