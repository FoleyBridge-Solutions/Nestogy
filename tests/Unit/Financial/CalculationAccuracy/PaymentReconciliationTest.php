<?php

namespace Tests\Unit\Financial\CalculationAccuracy;

use Tests\Unit\Financial\FinancialTestCase;
use App\Domains\Financial\Services\PaymentService;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * Comprehensive payment reconciliation accuracy tests
 * 
 * Ensures payment allocation, reconciliation, and balance calculations
 * are accurate and maintain financial integrity
 */
class PaymentReconciliationTest extends FinancialTestCase
{
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
    }

    /** @test */
    public function single_payment_full_invoice_reconciliation()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 500.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $originalAmount = $invoice->amount;

        // Create payment for exact invoice amount
        $payment = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $originalAmount,
            'payment_method' => 'check',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();

        $this->assertMonetaryEquals(0.00, $invoice->getBalance());
        $this->assertMonetaryEquals($originalAmount, $payment->amount);
        $this->assertEquals('paid', $invoice->status);
        $this->assertPrecisionMaintained($payment->amount, 2);
    }

    /** @test */
    public function partial_payment_balance_accuracy()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 1000.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $totalAmount = $invoice->amount;

        // Create partial payment (60% of invoice)
        $partialAmount = $totalAmount * 0.6;
        $payment = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $partialAmount,
            'payment_method' => 'credit_card',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();
        $expectedBalance = $totalAmount - $partialAmount;

        $this->assertMonetaryEquals($expectedBalance, $invoice->getBalance());
        $this->assertMonetaryEquals($partialAmount, $payment->amount);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertPrecisionMaintained($invoice->getBalance(), 2);
    }

    /** @test */
    public function multiple_partial_payments_reconciliation()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 750.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $totalAmount = $invoice->amount;

        // Create first partial payment
        $payment1 = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => 300.00,
            'payment_method' => 'check',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();
        $this->assertMonetaryEquals($totalAmount - 300.00, $invoice->getBalance());

        // Create second partial payment
        $payment2 = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => 250.00,
            'payment_method' => 'credit_card',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();
        $this->assertMonetaryEquals($totalAmount - 550.00, $invoice->getBalance());

        // Create final payment
        $remainingBalance = $invoice->getBalance();
        $payment3 = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $remainingBalance,
            'payment_method' => 'ach',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();

        $this->assertMonetaryEquals(0.00, $invoice->getBalance());
        $this->assertEquals('paid', $invoice->status);

        // Verify total payments equal invoice amount
        $totalPaid = $payment1->amount + $payment2->amount + $payment3->amount;
        $this->assertMonetaryEquals($totalAmount, $totalPaid);
    }

    /** @test */
    public function overpayment_handling_accuracy()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 400.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $totalAmount = $invoice->amount;

        // Create overpayment (25% more than invoice amount)
        $overpaymentAmount = $totalAmount * 1.25;
        $payment = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $overpaymentAmount,
            'payment_method' => 'check',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();
        $expectedCredit = $overpaymentAmount - $totalAmount;

        // Balance should be negative (credit)
        $this->assertMonetaryEquals(-$expectedCredit, $invoice->getBalance());
        $this->assertEquals('overpaid', $invoice->status);
        $this->assertPrecisionMaintained($payment->amount, 2);

        // Verify credit amount is accurate
        $actualCredit = abs($invoice->getBalance());
        $this->assertMonetaryEquals($expectedCredit, $actualCredit);
    }

    /** @test */
    public function payment_allocation_across_multiple_invoices()
    {
        // Create multiple invoices for the same client
        $invoice1 = $this->createTestInvoice(['due_date' => now()->subDays(10)]);
        $this->createTestInvoiceItem($invoice1, ['price' => 300.00, 'quantity' => 1]);
        
        $invoice2 = $this->createTestInvoice(['due_date' => now()->subDays(5)]);
        $this->createTestInvoiceItem($invoice2, ['price' => 400.00, 'quantity' => 1]);
        
        $invoice1->refresh();
        $invoice2->refresh();
        
        $totalOwed = $invoice1->amount + $invoice2->amount;

        // Create payment that covers both invoices
        $payment = $this->paymentService->allocatePaymentAcrossInvoices([
            'client_id' => $this->client->id,
            'amount' => $totalOwed,
            'payment_method' => 'ach',
            'payment_date' => now(),
            'company_id' => $this->company->id,
            'allocation_method' => 'oldest_first'
        ]);

        $invoice1->refresh();
        $invoice2->refresh();

        $this->assertMonetaryEquals(0.00, $invoice1->getBalance());
        $this->assertMonetaryEquals(0.00, $invoice2->getBalance());
        $this->assertEquals('paid', $invoice1->status);
        $this->assertEquals('paid', $invoice2->status);

        // Verify payment allocation records
        $allocatedTotal = $payment->allocations->sum('amount');
        $this->assertMonetaryEquals($totalOwed, $allocatedTotal);
    }

    /** @test */
    public function payment_with_processing_fees_accuracy()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 1000.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $invoiceAmount = $invoice->amount;

        // Create payment with processing fee (3% credit card fee)
        $processingFee = round($invoiceAmount * 0.03, 2);
        $payment = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $invoiceAmount,
            'payment_method' => 'credit_card',
            'payment_date' => now(),
            'company_id' => $this->company->id,
            'processing_fee' => $processingFee
        ]);

        $invoice->refresh();

        // Invoice should be marked as paid despite processing fee
        $this->assertMonetaryEquals(0.00, $invoice->getBalance());
        $this->assertEquals('paid', $invoice->status);

        // Payment record should include processing fee
        $this->assertMonetaryEquals($processingFee, $payment->processing_fee);
        $this->assertMonetaryEquals($invoiceAmount, $payment->amount);
        
        // Net received amount should be payment minus processing fee
        $netReceived = $payment->amount - $payment->processing_fee;
        $expectedNet = $invoiceAmount - $processingFee;
        $this->assertMonetaryEquals($expectedNet, $netReceived);
    }

    /** @test */
    public function refund_processing_accuracy()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 600.00, 'quantity' => 1]);
        
        $invoice->refresh();

        // Create full payment
        $payment = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'payment_method' => 'credit_card',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);

        // Process partial refund
        $refundAmount = 200.00;
        $refund = $this->paymentService->processRefund([
            'original_payment_id' => $payment->id,
            'amount' => $refundAmount,
            'reason' => 'Service cancellation',
            'refund_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();
        $payment->refresh();

        // Invoice balance should reflect the refund
        $expectedBalance = $refundAmount;
        $this->assertMonetaryEquals($expectedBalance, $invoice->getBalance());
        
        // Payment should show net amount after refund
        $expectedNetPayment = $payment->amount - $refundAmount;
        $this->assertMonetaryEquals($expectedNetPayment, $payment->getNetAmount());
        
        $this->assertMonetaryEquals($refundAmount, $refund->amount);
        $this->assertEquals('refunded', $refund->type);
        $this->assertPrecisionMaintained($refund->amount, 2);
    }

    /** @test */
    public function payment_reconciliation_with_discounts()
    {
        $invoice = $this->createTestInvoice(['discount' => 100.00]);
        $this->createTestInvoiceItem($invoice, ['price' => 500.00, 'quantity' => 1]);
        
        $invoice->refresh();
        
        // Invoice amount should be $500 + tax - $100 discount
        $invoiceAmount = $invoice->amount;
        $this->assertLessThan(500.00, $invoiceAmount); // Should be less due to discount

        // Create payment for discounted amount
        $payment = $this->paymentService->processPayment([
            'invoice_id' => $invoice->id,
            'amount' => $invoiceAmount,
            'payment_method' => 'check',
            'payment_date' => now(),
            'company_id' => $this->company->id
        ]);

        $invoice->refresh();

        $this->assertMonetaryEquals(0.00, $invoice->getBalance());
        $this->assertEquals('paid', $invoice->status);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function payment_currency_precision_edge_cases()
    {
        // Test payment amounts that might cause precision issues
        $testCases = [
            ['invoice_amount' => 33.33, 'payment_amount' => 33.33],
            ['invoice_amount' => 66.67, 'payment_amount' => 66.67],
            ['invoice_amount' => 0.01, 'payment_amount' => 0.01],
            ['invoice_amount' => 999.99, 'payment_amount' => 999.99],
            ['invoice_amount' => 123.456, 'payment_amount' => 123.46], // Should round
        ];

        foreach ($testCases as $case) {
            $invoice = $this->createTestInvoice();
            $this->createTestInvoiceItem($invoice, [
                'price' => $case['invoice_amount'], 
                'quantity' => 1
            ]);
            
            $invoice->refresh();

            $payment = $this->paymentService->processPayment([
                'invoice_id' => $invoice->id,
                'amount' => $case['payment_amount'],
                'payment_method' => 'check',
                'payment_date' => now(),
                'company_id' => $this->company->id
            ]);

            $invoice->refresh();

            // Verify precision is maintained
            $this->assertPrecisionMaintained($payment->amount, 2);
            $this->assertPrecisionMaintained($invoice->getBalance(), 2);

            // If payment equals invoice amount (within precision), balance should be zero
            if (round($case['payment_amount'], 2) >= round($invoice->amount, 2)) {
                $this->assertLessThanOrEqual(0.01, abs($invoice->getBalance()));
            }
        }
    }

    /** @test */
    public function payment_batch_processing_consistency()
    {
        // Create multiple invoices
        $invoices = [];
        $totalAmount = 0;

        for ($i = 0; $i < 5; $i++) {
            $invoice = $this->createTestInvoice(['client_id' => $this->client->id]);
            $this->createTestInvoiceItem($invoice, ['price' => 100.00 + ($i * 50), 'quantity' => 1]);
            $invoice->refresh();
            
            $invoices[] = $invoice;
            $totalAmount += $invoice->amount;
        }

        // Process batch payment
        $batchPayment = $this->paymentService->processBatchPayment([
            'client_id' => $this->client->id,
            'amount' => $totalAmount,
            'payment_method' => 'ach',
            'payment_date' => now(),
            'company_id' => $this->company->id,
            'invoice_ids' => collect($invoices)->pluck('id')->toArray()
        ]);

        // Verify all invoices are paid
        foreach ($invoices as $invoice) {
            $invoice->refresh();
            $this->assertMonetaryEquals(0.00, $invoice->getBalance());
            $this->assertEquals('paid', $invoice->status);
        }

        // Verify batch payment amount equals sum of invoice amounts
        $calculatedTotal = collect($invoices)->sum('amount');
        $this->assertMonetaryEquals($calculatedTotal, $batchPayment->amount);
        $this->assertPrecisionMaintained($batchPayment->amount, 2);
    }
}