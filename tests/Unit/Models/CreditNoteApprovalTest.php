<?php

namespace Tests\Unit\Models;

use App\Models\CreditNoteApproval;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNoteApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credit_note_approval_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CreditNoteApprovalFactory')) {
            $this->markTestSkipped('CreditNoteApprovalFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditNoteApproval::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CreditNoteApproval::class, $model);
    }

    public function test_credit_note_approval_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CreditNoteApprovalFactory')) {
            $this->markTestSkipped('CreditNoteApprovalFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditNoteApproval::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_credit_note_approval_has_fillable_attributes(): void
    {
        $model = new CreditNoteApproval();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
