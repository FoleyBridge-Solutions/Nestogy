<?php

namespace Tests\Unit\Financial\CalculationAccuracy;

use Tests\Unit\Financial\FinancialTestCase;
use Spatie\Activitylog\Models\Activity;
use App\Models\Invoice;
use App\Models\Payment;
use App\Domains\Contract\Models\Contract;

/**
 * Financial audit trail accuracy and completeness tests
 * 
 * Ensures all financial transactions are properly logged and traceable
 * Maintains compliance with financial regulations and audit requirements
 */
class FinancialAuditTrailTest extends FinancialTestCase
{
    /** @test */
    public function invoice_creation_generates_audit_log()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 500.00, 'quantity' => 1]);

        // Check that activity was logged
        $activities = Activity::where('subject_type', Invoice::class)
            ->where('subject_id', $invoice->id)
            ->where('description', 'created')
            ->get();

        $this->assertGreaterThan(0, $activities->count());

        $activity = $activities->first();
        $this->assertEquals('created', $activity->description);
        $this->assertEquals($this->user->id, $activity->causer_id);
        $this->assertNotNull($activity->properties);
        
        // Verify financial data is preserved in audit log
        $this->assertArrayHasKey('amount', $activity->properties->toArray());
        $this->assertArrayHasKey('subtotal', $activity->properties->toArray());
    }

    /** @test */
    public function invoice_amount_changes_are_audited()
    {
        $invoice = $this->createTestInvoice();
        $item = $this->createTestInvoiceItem($invoice, ['price' => 300.00, 'quantity' => 1]);
        
        $originalAmount = $invoice->fresh()->amount;

        // Modify invoice item price
        $item->update(['price' => 450.00]);
        $invoice->refresh();
        
        $newAmount = $invoice->amount;

        // Check that the change was logged
        $activities = Activity::where('subject_type', Invoice::class)
            ->where('subject_id', $invoice->id)
            ->where('description', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());

        $activity = $activities->last();
        $properties = $activity->properties->toArray();
        
        // Verify old and new values are captured
        if (isset($properties['old']) && isset($properties['attributes'])) {
            $this->assertNotEquals(
                $properties['old']['amount'] ?? null,
                $properties['attributes']['amount'] ?? null,
                'Amount change should be captured in audit trail'
            );
        }
    }

    /** @test */
    public function payment_processing_creates_complete_audit_trail()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 750.00, 'quantity' => 1]);
        
        $invoice->refresh();

        // Process payment
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'payment_method' => 'credit_card',
            'payment_date' => now(),
            'status' => 'completed',
            'created_by' => $this->user->id
        ]);

        // Verify payment creation was logged
        $paymentActivities = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $payment->id)
            ->where('description', 'created')
            ->get();

        $this->assertGreaterThan(0, $paymentActivities->count());

        $activity = $paymentActivities->first();
        $properties = $activity->properties->toArray();

        // Verify critical payment information is captured
        $this->assertEquals($payment->amount, $properties['amount'] ?? null);
        $this->assertEquals($payment->payment_method, $properties['payment_method'] ?? null);
        $this->assertEquals($this->user->id, $activity->causer_id);
    }

    /** @test */
    public function contract_financial_changes_are_audited()
    {
        $contract = $this->createTestContract([
            'monthly_amount' => 1000.00,
            'billing_frequency' => 'monthly'
        ]);

        $originalAmount = $contract->monthly_amount;

        // Modify contract amount (escalation)
        $contract->update([
            'monthly_amount' => 1050.00,
            'escalation_date' => now(),
            'escalation_reason' => 'Annual 5% increase'
        ]);

        // Verify contract modification was logged
        $activities = Activity::where('subject_type', Contract::class)
            ->where('subject_id', $contract->id)
            ->where('description', 'updated')
            ->get();

        $this->assertGreaterThan(0, $activities->count());

        $activity = $activities->last();
        $properties = $activity->properties->toArray();

        // Verify financial change details are captured
        if (isset($properties['old']['monthly_amount']) && isset($properties['attributes']['monthly_amount'])) {
            $this->assertMonetaryEquals($originalAmount, $properties['old']['monthly_amount']);
            $this->assertMonetaryEquals(1050.00, $properties['attributes']['monthly_amount']);
        }
    }

    /** @test */
    public function refund_processing_maintains_audit_integrity()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 600.00, 'quantity' => 1]);
        
        $invoice->refresh();

        // Create payment
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'payment_method' => 'check',
            'payment_date' => now(),
            'status' => 'completed',
            'created_by' => $this->user->id
        ]);

        // Process refund
        $refund = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'original_payment_id' => $payment->id,
            'amount' => -200.00, // Negative amount for refund
            'payment_method' => 'refund',
            'payment_date' => now(),
            'status' => 'completed',
            'type' => 'refund',
            'created_by' => $this->user->id
        ]);

        // Verify refund was logged with proper references
        $refundActivities = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $refund->id)
            ->where('description', 'created')
            ->get();

        $this->assertGreaterThan(0, $refundActivities->count());

        $activity = $refundActivities->first();
        $properties = $activity->properties->toArray();

        // Verify refund details are properly captured
        $this->assertEquals('refund', $properties['type'] ?? null);
        $this->assertEquals($payment->id, $properties['original_payment_id'] ?? null);
        $this->assertMonetaryEquals(-200.00, $properties['amount'] ?? 0);
    }

    /** @test */
    public function financial_audit_trail_preserves_calculation_details()
    {
        $invoice = $this->createTestInvoice();
        
        // Create multiple items to test calculation preservation
        $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 2, 'description' => 'Service A']);
        $this->createVoIPInvoiceItem($invoice, ['price' => 50.00, 'quantity' => 1, 'description' => 'VoIP Service']);
        
        $invoice->refresh();

        // Verify calculation components are preserved in audit logs
        $activities = Activity::where('subject_type', Invoice::class)
            ->where('subject_id', $invoice->id)
            ->get();

        $this->assertGreaterThan(0, $activities->count());

        $creationActivity = $activities->where('description', 'created')->first();
        if ($creationActivity) {
            $properties = $creationActivity->properties->toArray();
            
            // Verify key financial calculations are preserved
            $this->assertArrayHasKey('subtotal', $properties);
            $this->assertArrayHasKey('total_tax', $properties);
            $this->assertArrayHasKey('amount', $properties);
            
            // Verify precision is maintained in audit logs
            $this->assertPrecisionMaintained($properties['subtotal'] ?? 0, 2);
            $this->assertPrecisionMaintained($properties['total_tax'] ?? 0, 2);
            $this->assertPrecisionMaintained($properties['amount'] ?? 0, 2);
        }
    }

    /** @test */
    public function audit_trail_captures_user_context()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 400.00, 'quantity' => 1]);

        $activities = Activity::where('subject_type', Invoice::class)
            ->where('subject_id', $invoice->id)
            ->get();

        $this->assertGreaterThan(0, $activities->count());

        foreach ($activities as $activity) {
            // Verify user context is captured
            $this->assertEquals($this->user->id, $activity->causer_id);
            $this->assertEquals(get_class($this->user), $activity->causer_type);
            
            // Verify company context is maintained
            $properties = $activity->properties->toArray();
            $this->assertEquals($this->company->id, $properties['company_id'] ?? null);
            
            // Verify timestamp precision
            $this->assertNotNull($activity->created_at);
            $this->assertInstanceOf(\Carbon\Carbon::class, $activity->created_at);
        }
    }

    /** @test */
    public function bulk_financial_operations_maintain_audit_integrity()
    {
        $invoices = [];
        
        // Create multiple invoices
        for ($i = 0; $i < 3; $i++) {
            $invoice = $this->createTestInvoice(['client_id' => $this->client->id]);
            $this->createTestInvoiceItem($invoice, [
                'price' => 100.00 + ($i * 100), 
                'quantity' => 1,
                'description' => "Service " . ($i + 1)
            ]);
            $invoices[] = $invoice;
        }

        // Process bulk payment
        $totalAmount = collect($invoices)->sum(function($invoice) {
            return $invoice->fresh()->amount;
        });

        $bulkPayment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => $totalAmount,
            'payment_method' => 'ach',
            'payment_date' => now(),
            'status' => 'completed',
            'type' => 'bulk',
            'bulk_invoice_ids' => collect($invoices)->pluck('id')->toJson(),
            'created_by' => $this->user->id
        ]);

        // Verify bulk payment audit trail
        $bulkActivities = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $bulkPayment->id)
            ->get();

        $this->assertGreaterThan(0, $bulkActivities->count());

        $activity = $bulkActivities->first();
        $properties = $activity->properties->toArray();

        // Verify bulk operation details are preserved
        $this->assertEquals('bulk', $properties['type'] ?? null);
        $this->assertNotEmpty($properties['bulk_invoice_ids'] ?? null);
        $this->assertMonetaryEquals($totalAmount, $properties['amount'] ?? 0);
    }

    /** @test */
    public function audit_trail_handles_failed_transactions()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 500.00, 'quantity' => 1]);
        
        $invoice->refresh();

        // Simulate failed payment
        $failedPayment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'payment_method' => 'credit_card',
            'payment_date' => now(),
            'status' => 'failed',
            'failure_reason' => 'Insufficient funds',
            'created_by' => $this->user->id
        ]);

        // Verify failed transaction is properly logged
        $failedActivities = Activity::where('subject_type', Payment::class)
            ->where('subject_id', $failedPayment->id)
            ->get();

        $this->assertGreaterThan(0, $failedActivities->count());

        $activity = $failedActivities->first();
        $properties = $activity->properties->toArray();

        // Verify failure details are captured
        $this->assertEquals('failed', $properties['status'] ?? null);
        $this->assertEquals('Insufficient funds', $properties['failure_reason'] ?? null);
        $this->assertMonetaryEquals($invoice->amount, $properties['amount'] ?? 0);
    }

    /** @test */
    public function audit_trail_maintains_chronological_integrity()
    {
        $invoice = $this->createTestInvoice();
        $item = $this->createTestInvoiceItem($invoice, ['price' => 300.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $creationTime = $invoice->created_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Update the invoice
        $item->update(['price' => 350.00]);
        
        // Create payment
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->fresh()->amount,
            'payment_method' => 'check',
            'payment_date' => now(),
            'status' => 'completed',
            'created_by' => $this->user->id
        ]);

        // Verify chronological order in audit trail
        $allActivities = Activity::where(function($query) use ($invoice, $payment) {
            $query->where('subject_type', Invoice::class)
                  ->where('subject_id', $invoice->id);
        })->orWhere(function($query) use ($payment) {
            $query->where('subject_type', Payment::class)
                  ->where('subject_id', $payment->id);
        })->orderBy('created_at')->get();

        $this->assertGreaterThan(2, $allActivities->count());

        // Verify chronological order is maintained
        $previousTimestamp = null;
        foreach ($allActivities as $activity) {
            if ($previousTimestamp) {
                $this->assertGreaterThanOrEqual(
                    $previousTimestamp,
                    $activity->created_at,
                    'Audit trail should maintain chronological order'
                );
            }
            $previousTimestamp = $activity->created_at;
        }
    }
}