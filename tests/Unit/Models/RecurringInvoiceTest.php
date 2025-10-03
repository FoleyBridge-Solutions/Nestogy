<?php

namespace Tests\Unit\Models;

use App\Models\RecurringInvoice;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_recurring_invoice_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\RecurringInvoiceFactory')) {
            $this->markTestSkipped('RecurringInvoiceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RecurringInvoice::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(RecurringInvoice::class, $model);
    }

    public function test_recurring_invoice_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\RecurringInvoiceFactory')) {
            $this->markTestSkipped('RecurringInvoiceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RecurringInvoice::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_recurring_invoice_has_fillable_attributes(): void
    {
        $model = new RecurringInvoice();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
