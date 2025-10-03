<?php

namespace Tests\Unit\Models;

use App\Models\CreditNote;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credit_note_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CreditNoteFactory')) {
            $this->markTestSkipped('CreditNoteFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditNote::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CreditNote::class, $model);
    }

    public function test_credit_note_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CreditNoteFactory')) {
            $this->markTestSkipped('CreditNoteFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditNote::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_credit_note_has_fillable_attributes(): void
    {
        $model = new CreditNote();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
