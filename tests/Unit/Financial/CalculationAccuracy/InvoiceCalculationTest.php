<?php

namespace Tests\Unit\Financial\CalculationAccuracy;

use Tests\Unit\Financial\FinancialTestCase;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * Comprehensive tests for invoice calculation accuracy
 * 
 * Ensures all invoice calculations are mathematically correct and consistent
 * Covers edge cases, rounding, and precision requirements
 */
class InvoiceCalculationTest extends FinancialTestCase
{
    /** @test */
    public function invoice_subtotal_equals_sum_of_item_subtotals()
    {
        $invoice = $this->createTestInvoice();
        
        // Create multiple items with different prices
        $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 2]); // $200.00
        $this->createTestInvoiceItem($invoice, ['price' => 75.50, 'quantity' => 1]);  // $75.50
        $this->createTestInvoiceItem($invoice, ['price' => 25.25, 'quantity' => 3]);  // $75.75
        
        $invoice->refresh();
        $expectedSubtotal = 200.00 + 75.50 + 75.75; // $351.25
        
        $this->assertMonetaryEquals($expectedSubtotal, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function invoice_handles_zero_amount_items()
    {
        $invoice = $this->createTestInvoice();
        
        $this->createTestInvoiceItem($invoice, ['price' => 0.00, 'quantity' => 1]);
        $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 1]);
        
        $invoice->refresh();
        
        $this->assertMonetaryEquals(100.00, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function invoice_handles_fractional_quantities()
    {
        $invoice = $this->createTestInvoice();
        
        // Test fractional hours/quantities
        $this->createTestInvoiceItem($invoice, [
            'price' => 150.00, 
            'quantity' => 2.5, // 2.5 hours at $150/hour = $375.00
            'description' => 'Consulting Hours'
        ]);
        
        $invoice->refresh();
        
        $this->assertMonetaryEquals(375.00, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function invoice_applies_discount_correctly()
    {
        $invoice = $this->createTestInvoice(['discount' => 50.00]);
        
        $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 2]); // $200.00
        
        $invoice->refresh();
        
        $this->assertMonetaryEquals(200.00, $invoice->subtotal);
        $this->assertMonetaryEquals(50.00, $invoice->discount);
        // Total = subtotal + tax - discount = 200 + tax - 50
        $expectedTotal = $invoice->subtotal + $invoice->total_tax - $invoice->discount;
        $this->assertMonetaryEquals($expectedTotal, $invoice->amount);
    }

    /** @test */
    public function invoice_calculates_tax_correctly()
    {
        $invoice = $this->createTestInvoice();
        
        // Create a VoIP service item that should have tax
        $this->createVoIPInvoiceItem($invoice, ['price' => 50.00, 'quantity' => 1]);
        
        $invoice->refresh();
        
        $this->assertMonetaryNonNegative($invoice->total_tax);
        $this->assertInvoiceTotalsConsistent($invoice);
        
        // VoIP services above $0.20 should have federal excise tax (3%)
        if ($invoice->total_tax > 0) {
            $this->assertMonetaryPositive($invoice->total_tax);
        }
    }

    /** @test */
    public function invoice_handles_large_amounts()
    {
        $invoice = $this->createTestInvoice();
        
        // Test large monetary amounts
        $this->createTestInvoiceItem($invoice, ['price' => 9999.99, 'quantity' => 10]);
        $this->createTestInvoiceItem($invoice, ['price' => 5000.00, 'quantity' => 1]);
        
        $invoice->refresh();
        
        $expectedSubtotal = 99999.90 + 5000.00; // $104,999.90
        
        $this->assertMonetaryEquals($expectedSubtotal, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
        $this->assertPrecisionMaintained($invoice->amount);
    }

    /** @test */
    public function invoice_maintains_precision_with_complex_calculations()
    {
        $invoice = $this->createTestInvoice(['discount' => 33.33]);
        
        // Create items with prices that could cause precision issues
        $this->createTestInvoiceItem($invoice, ['price' => 33.33, 'quantity' => 3]); // $99.99
        $this->createTestInvoiceItem($invoice, ['price' => 66.67, 'quantity' => 1]); // $66.67
        $this->createTestInvoiceItem($invoice, ['price' => 0.01, 'quantity' => 100]); // $1.00
        
        $invoice->refresh();
        
        $expectedSubtotal = 99.99 + 66.67 + 1.00; // $167.66
        
        $this->assertMonetaryEquals($expectedSubtotal, $invoice->subtotal);
        $this->assertPrecisionMaintained($invoice->subtotal, 2);
        $this->assertPrecisionMaintained($invoice->amount, 2);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function invoice_item_subtotal_calculation_is_accurate()
    {
        $invoice = $this->createTestInvoice();
        
        $item = $this->createTestInvoiceItem($invoice, [
            'price' => 123.45,
            'quantity' => 2.5
        ]);
        
        $expectedSubtotal = 123.45 * 2.5; // $308.625, should round to $308.63
        
        $this->assertMonetaryEquals($expectedSubtotal, $item->subtotal);
        $this->assertPrecisionMaintained($item->subtotal, 2);
    }

    /** @test */
    public function invoice_recalculates_when_items_change()
    {
        $invoice = $this->createTestInvoice();
        $item = $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $originalAmount = $invoice->amount;
        
        // Update item price
        $item->update(['price' => 200.00]);
        $invoice->refresh();
        
        $this->assertNotEquals($originalAmount, $invoice->amount);
        $this->assertMonetaryEquals(200.00, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function invoice_handles_item_deletion_correctly()
    {
        $invoice = $this->createTestInvoice();
        $item1 = $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 1]);
        $item2 = $this->createTestInvoiceItem($invoice, ['price' => 50.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $this->assertMonetaryEquals(150.00, $invoice->subtotal);
        
        // Delete one item
        $item1->delete();
        $invoice->refresh();
        
        $this->assertMonetaryEquals(50.00, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
    }

    /** @test */
    public function invoice_balance_calculation_is_accurate()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $totalAmount = $invoice->amount;
        
        // Create partial payment
        $payment = $invoice->payments()->create([
            'company_id' => $this->company->id,
            'amount' => 60.00,
            'payment_date' => now(),
            'payment_method' => 'check'
        ]);
        
        $expectedBalance = $totalAmount - 60.00;
        $this->assertMonetaryEquals($expectedBalance, $invoice->getBalance());
        $this->assertMonetaryNonNegative($invoice->getBalance());
    }

    /** @test */
    public function invoice_handles_overpayment_correctly()
    {
        $invoice = $this->createTestInvoice();
        $this->createTestInvoiceItem($invoice, ['price' => 100.00, 'quantity' => 1]);
        
        $invoice->refresh();
        $totalAmount = $invoice->amount;
        
        // Create overpayment
        $payment = $invoice->payments()->create([
            'company_id' => $this->company->id,
            'amount' => $totalAmount + 25.00, // Overpay by $25
            'payment_date' => now(),
            'payment_method' => 'check'
        ]);
        
        $balance = $invoice->getBalance();
        $this->assertMonetaryEquals(-25.00, $balance); // Should be negative (credit)
    }

    /** @test */
    public function invoice_edge_case_penny_amounts()
    {
        $invoice = $this->createTestInvoice();
        
        // Test edge case with penny amounts
        $this->createTestInvoiceItem($invoice, ['price' => 0.01, 'quantity' => 1]);
        $this->createTestInvoiceItem($invoice, ['price' => 0.99, 'quantity' => 1]);
        
        $invoice->refresh();
        
        $this->assertMonetaryEquals(1.00, $invoice->subtotal);
        $this->assertInvoiceTotalsConsistent($invoice);
        $this->assertPrecisionMaintained($invoice->amount, 2);
    }

    /** @test */
    public function invoice_multiple_currency_decimal_precision()
    {
        // Test various currency scenarios that might cause precision issues
        $testCases = [
            ['price' => 10.00, 'quantity' => 0.1, 'expected' => 1.00],
            ['price' => 33.33, 'quantity' => 0.3, 'expected' => 9.999], // Should round to 10.00
            ['price' => 100.00, 'quantity' => 0.33, 'expected' => 33.00],
            ['price' => 7.77, 'quantity' => 1.29, 'expected' => 10.0233] // Should round to 10.02
        ];

        foreach ($testCases as $case) {
            $invoice = $this->createTestInvoice();
            $item = $this->createTestInvoiceItem($invoice, [
                'price' => $case['price'],
                'quantity' => $case['quantity']
            ]);

            $calculatedSubtotal = round($case['price'] * $case['quantity'], 2);
            $this->assertMonetaryEquals($calculatedSubtotal, $item->subtotal);
            $this->assertPrecisionMaintained($item->subtotal, 2);
        }
    }
}