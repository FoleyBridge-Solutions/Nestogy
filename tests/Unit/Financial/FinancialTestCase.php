<?php

namespace Tests\Unit\Financial;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Company;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\Payment;
use App\Domains\Contract\Models\Contract;
use App\Domains\Financial\Services\InvoiceService;
use App\Services\VoIPTaxService;
use Carbon\Carbon;

/**
 * Base test case for financial accuracy tests
 * 
 * Provides common setup, utilities, and assertion methods for financial testing
 */
abstract class FinancialTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Client $client;
    protected VoIPTaxService $taxService;
    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and user
        $this->company = Company::factory()->create([
            'name' => 'Test MSP Company',
            'currency' => 'USD',
            'timezone' => 'America/New_York'
        ]);
        
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'test@nestogy.com'
        ]);
        
        $this->actingAs($this->user);
        
        // Create test client
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client Corp'
        ]);
        
        // Initialize services
        $this->taxService = app(VoIPTaxService::class);
        $this->invoiceService = app(InvoiceService::class);
    }

    /**
     * Assert that two monetary values are equal within acceptable precision
     * Prevents floating point precision issues
     */
    protected function assertMonetaryEquals(float $expected, float $actual, string $message = ''): void
    {
        $this->assertEquals(
            round($expected, 2),
            round($actual, 2),
            $message ?: "Monetary values should be equal. Expected: $expected, Actual: $actual"
        );
    }

    /**
     * Assert that a monetary value is positive
     */
    protected function assertMonetaryPositive(float $value, string $message = ''): void
    {
        $this->assertGreaterThan(0, round($value, 2), $message ?: "Value should be positive: $value");
    }

    /**
     * Assert that a monetary value is zero or positive
     */
    protected function assertMonetaryNonNegative(float $value, string $message = ''): void
    {
        $this->assertGreaterThanOrEqual(0, round($value, 2), $message ?: "Value should be non-negative: $value");
    }

    /**
     * Assert that invoice totals are mathematically consistent
     */
    protected function assertInvoiceTotalsConsistent(Invoice $invoice): void
    {
        $calculatedSubtotal = $invoice->items->sum('subtotal');
        $calculatedTax = $invoice->items->sum('tax_amount');
        $calculatedTotal = $calculatedSubtotal + $calculatedTax - $invoice->discount;

        $this->assertMonetaryEquals(
            $calculatedSubtotal,
            $invoice->subtotal,
            "Invoice subtotal should equal sum of item subtotals"
        );

        $this->assertMonetaryEquals(
            $calculatedTax,
            $invoice->total_tax,
            "Invoice tax should equal sum of item taxes"
        );

        $this->assertMonetaryEquals(
            $calculatedTotal,
            $invoice->amount,
            "Invoice total should equal subtotal + tax - discount"
        );
    }

    /**
     * Assert that tax calculations are within acceptable ranges
     */
    protected function assertTaxRatesValid(array $taxResult): void
    {
        $this->assertArrayHasKey('total_tax', $taxResult);
        $this->assertArrayHasKey('breakdown', $taxResult);
        
        $this->assertMonetaryNonNegative($taxResult['total_tax'], 'Total tax cannot be negative');
        
        // Validate tax breakdown sums to total
        if (!empty($taxResult['breakdown'])) {
            $breakdownTotal = array_sum(array_column($taxResult['breakdown'], 'amount'));
            $this->assertMonetaryEquals(
                $taxResult['total_tax'],
                $breakdownTotal,
                'Tax breakdown should sum to total tax'
            );
        }
    }

    /**
     * Create a test invoice with specified parameters
     */
    protected function createTestInvoice(array $overrides = []): Invoice
    {
        return Invoice::factory()->create(array_merge([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'currency' => 'USD'
        ], $overrides));
    }

    /**
     * Create a test invoice item with specified parameters
     */
    protected function createTestInvoiceItem(Invoice $invoice, array $overrides = []): InvoiceItem
    {
        return InvoiceItem::factory()->create(array_merge([
            'invoice_id' => $invoice->id,
            'company_id' => $this->company->id,
            'description' => 'Test Service',
            'quantity' => 1,
            'price' => 100.00
        ], $overrides));
    }

    /**
     * Create a test VoIP service invoice item
     */
    protected function createVoIPInvoiceItem(Invoice $invoice, array $overrides = []): InvoiceItem
    {
        return $this->createTestInvoiceItem($invoice, array_merge([
            'description' => 'VoIP Service',
            'service_type' => 'voip',
            'quantity' => 1,
            'price' => 25.00, // Above $0.20 threshold for federal excise tax
            'is_voip_service' => true
        ], $overrides));
    }

    /**
     * Create a test contract with billing configuration
     */
    protected function createTestContract(array $overrides = []): Contract
    {
        return Contract::factory()->create(array_merge([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'title' => 'Test MSP Contract',
            'status' => 'active',
            'start_date' => now(),
            'billing_frequency' => 'monthly'
        ], $overrides));
    }

    /**
     * Assert that monetary precision is maintained
     */
    protected function assertPrecisionMaintained(float $value, int $expectedDecimals = 2): void
    {
        $actualDecimals = strlen(substr(strrchr((string)$value, "."), 1));
        $this->assertLessThanOrEqual(
            $expectedDecimals,
            $actualDecimals,
            "Value $value should not exceed $expectedDecimals decimal places"
        );
    }

    /**
     * Generate test data for edge case scenarios
     */
    protected function getEdgeCaseValues(): array
    {
        return [
            'zero' => 0.00,
            'penny' => 0.01,
            'threshold' => 0.20, // VoIP federal tax threshold
            'small' => 1.99,
            'medium' => 99.99,
            'large' => 999.99,
            'very_large' => 9999.99,
            'max_precision' => 123.45
        ];
    }

    /**
     * Validate that all currency values in an array are properly formatted
     */
    protected function assertCurrencyArrayValid(array $values, string $context = ''): void
    {
        foreach ($values as $key => $value) {
            if (is_numeric($value)) {
                $this->assertPrecisionMaintained($value, 2);
                $this->assertMonetaryNonNegative($value, "$context: $key should be non-negative");
            }
        }
    }
}