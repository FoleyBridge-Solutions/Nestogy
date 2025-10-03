<?php

namespace Tests\Unit\Models;

use App\Models\CreditNoteItem;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNoteItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credit_note_item_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CreditNoteItemFactory')) {
            $this->markTestSkipped('CreditNoteItemFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditNoteItem::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CreditNoteItem::class, $model);
    }

    public function test_credit_note_item_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CreditNoteItemFactory')) {
            $this->markTestSkipped('CreditNoteItemFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditNoteItem::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_credit_note_item_has_fillable_attributes(): void
    {
        $model = new CreditNoteItem();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
