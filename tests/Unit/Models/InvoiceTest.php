<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;

class InvoiceTest extends ModelTestCase
{

    public function test_can_create_invoice_with_factory(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_invoice_belongs_to_company(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Company::class, $invoice->company);
        $this->assertEquals($company->id, $invoice->company->id);
    }

    public function test_invoice_belongs_to_client(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Client::class, $invoice->client);
        $this->assertEquals($client->id, $invoice->client->id);
    }

    public function test_invoice_has_status_field(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $this->assertEquals('draft', $invoice->status);
    }

    public function test_invoice_can_have_different_statuses(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
            'status' => 'paid',
        ]);

        $this->assertEquals('paid', $invoice->status);
    }

    public function test_invoice_has_amount_field(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
            'amount' => 1500.50,
        ]);

        $this->assertEquals(1500.50, $invoice->amount);
    }

    public function test_invoice_has_currency_code(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
            'currency_code' => 'USD',
        ]);

        $this->assertEquals('USD', $invoice->currency_code);
    }

    public function test_invoice_has_date_and_due_date(): void
    {
        $company = $this->testCompany;
        $client = $this->testClient;
        $category = $this->testCategory;
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category_id' => $category->id,
        ]);

        $this->assertNotNull($invoice->date);
        $this->assertNotNull($invoice->due_date);
    }

    public function test_invoice_has_fillable_attributes(): void
    {
        $fillable = (new Invoice)->getFillable();

        $expectedFillable = ['company_id', 'client_id', 'status', 'amount', 'currency_code'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_is_draft_returns_true_for_draft_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Draft',
        ]);

        $this->assertTrue($invoice->isDraft());
    }

    public function test_is_paid_returns_true_for_paid_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Paid',
        ]);

        $this->assertTrue($invoice->isPaid());
    }

    public function test_is_overdue_returns_true_for_overdue_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'due_date' => now()->subDays(5),
            'status' => 'sent',
        ]);

        $this->assertTrue($invoice->isOverdue());
    }

    public function test_is_overdue_returns_false_for_future_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'due_date' => now()->addDays(30),
            'status' => 'sent',
        ]);

        $this->assertFalse($invoice->isOverdue());
    }

    public function test_mark_as_sent_updates_status(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Draft',
        ]);

        $invoice->markAsSent();

        $this->assertEquals('Sent', $invoice->status);
    }

    public function test_mark_as_paid_updates_status(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Sent',
        ]);

        $invoice->markAsPaid();

        $this->assertEquals('Paid', $invoice->status);
    }

    public function test_get_formatted_amount_returns_currency_formatted_string(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'amount' => 1234.56,
            'currency_code' => 'USD',
        ]);

        $formatted = $invoice->getFormattedAmount();

        $this->assertStringContainsString('1,234.56', $formatted);
    }

    public function test_get_currency_symbol_returns_correct_symbol(): void
    {
        $usdInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'currency_code' => 'USD',
        ]);

        $eurInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'currency_code' => 'EUR',
        ]);

        $this->assertEquals('$', $usdInvoice->getCurrencySymbol());
        $this->assertEquals('€', $eurInvoice->getCurrencySymbol());
    }

    public function test_get_total_paid_returns_sum_of_payments(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'amount' => 1000.00,
        ]);

        $totalPaid = $invoice->getTotalPaid();

        $this->assertEquals(0, $totalPaid);
    }

    public function test_get_balance_returns_remaining_amount(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'amount' => 1000.00,
        ]);

        $balance = $invoice->getBalance();

        $this->assertEquals(1000.00, $balance);
    }

    public function test_is_fully_paid_returns_false_for_unpaid_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'amount' => 1000.00,
            'status' => 'sent',
        ]);

        $this->assertFalse($invoice->isFullyPaid());
    }

    public function test_scope_overdue_filters_overdue_invoices(): void
    {
        $overdueInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'due_date' => now()->subDays(5),
            'status' => 'sent',
        ]);

        $futureInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'due_date' => now()->addDays(30),
            'status' => 'sent',
        ]);

        $overdueInvoices = Invoice::overdue()->get();

        $this->assertTrue($overdueInvoices->contains($overdueInvoice));
        $this->assertFalse($overdueInvoices->contains($futureInvoice));
    }

    public function test_scope_paid_filters_paid_invoices(): void
    {
        $paidInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Paid',
        ]);

        $unpaidInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Sent',
        ]);

        $paidInvoices = Invoice::paid()->get();

        $this->assertTrue($paidInvoices->contains($paidInvoice));
        $this->assertFalse($paidInvoices->contains($unpaidInvoice));
    }

    public function test_scope_unpaid_filters_unpaid_invoices(): void
    {
        $unpaidInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Sent',
        ]);

        $paidInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'Paid',
        ]);

        $unpaidInvoices = Invoice::unpaid()->get();

        $this->assertTrue($unpaidInvoices->contains($unpaidInvoice));
        $this->assertFalse($unpaidInvoices->contains($paidInvoice));
    }

    public function test_scope_by_status_filters_by_given_status(): void
    {
        $draftInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'draft',
        ]);

        $sentInvoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'status' => 'sent',
        ]);

        $draftInvoices = Invoice::byStatus('draft')->get();

        $this->assertTrue($draftInvoices->contains($draftInvoice));
        $this->assertFalse($draftInvoices->contains($sentInvoice));
    }

    public function test_items_relationship_exists(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $invoice->items());
    }

    public function test_payments_relationship_exists(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $invoice->payments());
    }

    public function test_tickets_relationship_exists(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $invoice->tickets());
    }

    public function test_category_relationship_exists(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $invoice->category());
    }

    public function test_recurring_invoice_relationship_exists(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $invoice->recurringInvoice());
    }

    public function test_format_currency_formats_amount_correctly(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->testCompany->id,
            'client_id' => $this->testClient->id,
            'category_id' => $this->testCategory->id,
            'currency_code' => 'USD',
        ]);

        $formatted = $invoice->formatCurrency(1234.56);

        $this->assertEquals('$1,234.56', $formatted);
    }
}