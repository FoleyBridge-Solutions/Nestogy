<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;

class PaymentTest extends ModelTestCase
{

    public function test_can_create_payment_with_factory(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'client_id' => $this->testClient->id,
            'processed_by' => null,
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_payment_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'client_id' => $this->testClient->id,
            'processed_by' => null,
        ]);

        $this->assertInstanceOf(Invoice::class, $payment->invoice);
        $this->assertEquals($invoice->id, $payment->invoice->id);
    }

    public function test_payment_has_amount_field(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'client_id' => $this->testClient->id,
            'processed_by' => null,
            'amount' => 500.00,
        ]);

        $this->assertEquals(500.00, $payment->amount);
    }

    public function test_payment_belongs_to_company(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'client_id' => $this->testClient->id,
            'processed_by' => null,
        ]);

        $this->assertInstanceOf(Company::class, $payment->company);
        $this->assertEquals($this->testCompany->id, $payment->company->id);
    }

    public function test_payment_has_timestamps(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);
        
        $payment = Payment::factory()->create([
            'company_id' => $this->testCompany->id,
            'invoice_id' => $invoice->id,
            'client_id' => $this->testClient->id,
            'processed_by' => null,
        ]);

        $this->assertNotNull($payment->created_at);
        $this->assertNotNull($payment->updated_at);
    }

    public function test_payment_has_fillable_attributes(): void
    {
        $fillable = (new Payment)->getFillable();

        $expectedFillable = ['company_id', 'invoice_id', 'amount'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }
}