<?php

namespace Tests\Unit\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Category;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\InvoiceItem;
use App\Domains\Financial\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $service;
    protected Company $company;
    protected User $user;
    protected Client $client;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'invoice',
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();

        $this->actingAs($this->user);
        $this->service = app(InvoiceService::class);

        Log::spy();
    }

    /** @test */
    public function it_can_create_invoice_with_required_data(): void
    {
        $data = [
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'Draft',
        ];

        $invoice = $this->service->createInvoice($this->client, $data);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->client->id, $invoice->client_id);
        $this->assertEquals($this->company->id, $invoice->company_id);
        $this->assertEquals($this->category->id, $invoice->category_id);
        $this->assertEquals('Draft', $invoice->status);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_generates_invoice_number_automatically(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'category_id' => $this->category->id,
        ]);

        $this->assertNotNull($invoice->number);
        // Invoice number is automatically generated (may be overridden by model/observer)
        $this->assertIsString($invoice->number);
    }

    /** @test */
    public function it_uses_default_values_when_optional_data_not_provided(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'category_id' => $this->category->id,
        ]);

        $this->assertEquals('Draft', $invoice->status);
        $this->assertEquals(0, $invoice->discount_amount);
        $this->assertEquals(0, $invoice->amount);
        $this->assertEquals('USD', $invoice->currency_code);
        $this->assertNotNull($invoice->date);
        $this->assertNotNull($invoice->due_date);
    }

    /** @test */
    public function it_logs_invoice_creation(): void
    {
        $this->service->createInvoice($this->client, [
            'category_id' => $this->category->id,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Invoice created', \Mockery::on(function ($context) {
                return isset($context['invoice_id'])
                    && isset($context['client_id'])
                    && isset($context['user_id']);
            }));
    }

    /** @test */
    public function it_creates_invoice_within_database_transaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->service->createInvoice($this->client, [
            'category_id' => $this->category->id,
        ]);
    }

    /** @test */
    public function it_can_update_invoice_basic_fields(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
            'note' => 'Original note',
        ]);

        $updatedInvoice = $this->service->updateInvoice($invoice, [
            'status' => 'sent',
            'note' => 'Updated note',
        ]);

        $this->assertEquals('sent', $updatedInvoice->status);
        $this->assertEquals('Updated note', $updatedInvoice->note);
    }

    /** @test */
    public function it_can_update_invoice_with_items(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        $items = [
            [
                'description' => 'Service 1',
                'quantity' => 2,
                'price' => 100.00,
                'tax_rate' => 0.10,
            ],
            [
                'description' => 'Service 2',
                'quantity' => 1,
                'price' => 250.00,
                'tax_rate' => 0.10,
            ],
        ];

        $updatedInvoice = $this->service->updateInvoice($invoice, ['items' => $items]);

        $this->assertEquals(2, $updatedInvoice->items()->count());
        $this->assertEquals('Service 1', $updatedInvoice->items()->first()->description);
        $this->assertEquals(200.00, $updatedInvoice->items()->first()->amount);
    }

    /** @test */
    public function it_deletes_existing_items_when_updating_with_new_items(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->assertEquals(3, $invoice->items()->count());

        $newItems = [
            ['description' => 'New Service', 'quantity' => 1, 'price' => 100],
        ];

        $updatedInvoice = $this->service->updateInvoice($invoice, ['items' => $newItems]);

        $this->assertEquals(1, $updatedInvoice->items()->count());
    }

    /** @test */
    public function it_calculates_totals_when_updating_items(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'amount' => 0,
        ]);

        $items = [
            ['description' => 'Item 1', 'quantity' => 2, 'price' => 100, 'tax_rate' => 0],
        ];

        $updatedInvoice = $this->service->updateInvoice($invoice, ['items' => $items]);

        $this->assertGreaterThan(0, $updatedInvoice->amount);
    }

    /** @test */
    public function it_can_send_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
            'sent_at' => null,
        ]);

        $result = $this->service->sendInvoice($invoice);

        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);
    }

    /** @test */
    public function it_logs_invoice_sending(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->service->sendInvoice($invoice);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Invoice sent', \Mockery::on(function ($context) {
                return isset($context['invoice_id'])
                    && isset($context['client_id'])
                    && isset($context['user_id']);
            }));
    }

    /** @test */
    public function it_handles_errors_when_sending_invoice_fails(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        // Force an error by making the invoice read-only
        $invoice->exists = false;

        $result = $this->service->sendInvoice($invoice);

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to send invoice', \Mockery::on(function ($context) {
                return isset($context['invoice_id']) && isset($context['error']);
            }));
    }

    /** @test */
    public function it_can_mark_invoice_as_paid(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'sent',
            'paid_at' => null,
        ]);

        $result = $this->service->markAsPaid($invoice);

        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function it_can_mark_invoice_as_paid_with_custom_date(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'sent',
        ]);

        $customDate = now()->subDays(5);
        $result = $this->service->markAsPaid($invoice, ['paid_at' => $customDate]);

        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertEquals($customDate->toDateTimeString(), $invoice->paid_at->toDateTimeString());
    }

    /** @test */
    public function it_logs_marking_invoice_as_paid(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->service->markAsPaid($invoice);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Invoice marked as paid', \Mockery::on(function ($context) {
                return isset($context['invoice_id']) && isset($context['user_id']);
            }));
    }

    /** @test */
    public function it_generates_sequential_invoice_numbers(): void
    {
        $number1 = $this->service->generateInvoiceNumber();
        
        // Create an invoice to increment the counter
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'number' => $number1,
        ]);

        $number2 = $this->service->generateInvoiceNumber();

        $this->assertNotEquals($number1, $number2);
        $this->assertStringContainsString((string) now()->year, $number1);
        $this->assertStringContainsString((string) now()->year, $number2);
    }

    /** @test */
    public function it_generates_invoice_number_with_correct_format(): void
    {
        $number = $this->service->generateInvoiceNumber();

        $this->assertMatchesRegularExpression('/INV-\d{4}-\d{4}/', $number);
        $this->assertStringStartsWith('INV-'.now()->year, $number);
    }

    /** @test */
    public function it_resets_invoice_numbering_for_new_year(): void
    {
        // Create invoice with current year
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'number' => 'INV-'.now()->year.'-0099',
            'created_at' => now(),
        ]);

        // Create invoice from previous year
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'number' => 'INV-'.(now()->year - 1).'-0250',
            'created_at' => now()->subYear(),
        ]);

        $newNumber = $this->service->generateInvoiceNumber();

        // Should be 0100 (next after 0099 this year), not 0251
        $this->assertEquals('INV-'.now()->year.'-0100', $newNumber);
    }

    /** @test */
    public function it_can_get_invoice_statistics_for_client(): void
    {
        // Create invoices with different statuses
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
            'amount' => 100,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'sent',
            'amount' => 200,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'paid',
            'amount' => 300,
        ]);

        $stats = $this->service->getInvoiceStats($this->client);

        $this->assertEquals(3, $stats['total_count']);
        $this->assertEquals(1, $stats['draft_count']);
        $this->assertEquals(1, $stats['sent_count']);
        $this->assertEquals(1, $stats['paid_count']);
        $this->assertEquals(600, $stats['total_amount']);
        $this->assertEquals(300, $stats['paid_amount']);
        $this->assertEquals(200, $stats['outstanding_amount']);
    }

    /** @test */
    public function it_counts_overdue_invoices_in_statistics(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'sent',
            'due_date' => now()->subDays(10)->toDateString(),
            'amount' => 150,
        ]);

        $stats = $this->service->getInvoiceStats($this->client);

        $this->assertEquals(1, $stats['overdue_count']);
    }

    /** @test */
    public function it_can_archive_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'archived_at' => null,
        ]);

        $result = $this->service->archiveInvoice($invoice);

        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertNotNull($invoice->archived_at);
    }

    /** @test */
    public function it_logs_invoice_archiving(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->service->archiveInvoice($invoice);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Invoice archived', \Mockery::on(function ($context) {
                return isset($context['invoice_id']) && isset($context['user_id']);
            }));
    }

    /** @test */
    public function it_respects_company_isolation_when_generating_invoice_number(): void
    {
        // Create invoice in another company
        $otherCompany = Company::factory()->create();
        Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
            'number' => 'INV-'.now()->year.'-0500',
        ]);

        // Generate number for our company
        $number = $this->service->generateInvoiceNumber();

        // Should start at 0001, not 0501
        $this->assertEquals('INV-'.now()->year.'-0001', $number);
    }

    /** @test */
    public function it_respects_company_isolation_in_statistics(): void
    {
        // Create invoice for another company
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);
        Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => $otherClient->id,
            'amount' => 999,
        ]);

        // Get stats for our client
        $stats = $this->service->getInvoiceStats($this->client);

        $this->assertEquals(0, $stats['total_count']);
        $this->assertEquals(0, $stats['total_amount']);
    }

    /** @test */
    public function it_can_calculate_invoice_totals(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'price' => 100,
            'tax_rate' => 0.10,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'price' => 50,
            'tax_rate' => 0.10,
        ]);

        $totals = $this->service->calculateInvoiceTotals($invoice);

        $this->assertIsArray($totals);
        $this->assertArrayHasKey('subtotal', $totals);
        $this->assertArrayHasKey('tax', $totals);
        $this->assertArrayHasKey('total', $totals);
    }

    /** @test */
    public function it_can_duplicate_invoice(): void
    {
        $originalInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'status' => 'paid',
            'note' => 'Original note',
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $originalInvoice->id,
            'description' => 'Original item',
        ]);

        $duplicatedInvoice = $this->service->duplicateInvoice($originalInvoice);

        $this->assertNotEquals($originalInvoice->id, $duplicatedInvoice->id);
        $this->assertEquals($originalInvoice->client_id, $duplicatedInvoice->client_id);
        $this->assertEquals('Draft', $duplicatedInvoice->status);
        $this->assertNotEquals($originalInvoice->number, $duplicatedInvoice->number);
    }

    /** @test */
    public function it_can_duplicate_invoice_with_overrides(): void
    {
        $originalInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'note' => 'Original note',
        ]);

        $newClient = Client::factory()->create(['company_id' => $this->company->id]);

        $duplicatedInvoice = $this->service->duplicateInvoice($originalInvoice, [
            'client_id' => $newClient->id,
            'note' => 'Duplicated note',
        ]);

        $this->assertEquals($newClient->id, $duplicatedInvoice->client_id);
        $this->assertEquals('Duplicated note', $duplicatedInvoice->note);
    }

    /** @test */
    public function it_can_add_item_to_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        $itemData = [
            'description' => 'New Item',
            'quantity' => 3,
            'price' => 75.00,
            'tax_rate' => 0.08,
        ];

        $this->service->addInvoiceItem($invoice, $itemData);

        $this->assertEquals(1, $invoice->items()->count());
        $this->assertEquals('New Item', $invoice->items()->first()->description);
        $this->assertEquals(225.00, $invoice->items()->first()->amount);
    }

    /** @test */
    public function it_can_update_invoice_status(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $result = $this->service->updateInvoiceStatus($invoice, 'sent');

        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
    }

    /** @test */
    public function it_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $invoiceId = $invoice->id;

        $result = $this->service->deleteInvoice($invoice);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    /** @test */
    public function it_can_update_invoice_item(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Original description',
            'quantity' => 1,
            'price' => 100,
        ]);

        $this->service->updateInvoiceItem($item, [
            'description' => 'Updated description',
            'quantity' => 2,
        ]);

        $item->refresh();
        $this->assertEquals('Updated description', $item->description);
        $this->assertEquals(2, $item->quantity);
    }

    /** @test */
    public function it_can_delete_invoice_item(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        $itemId = $item->id;

        $result = $this->service->deleteInvoiceItem($item);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('invoice_items', ['id' => $itemId]);
    }

    /** @test */
    public function it_uses_rate_as_fallback_for_price_in_items(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
        ]);

        // Use 'rate' instead of 'price'
        $items = [
            [
                'description' => 'Service with rate',
                'quantity' => 1,
                'rate' => 150.00,
            ],
        ];

        $updatedInvoice = $this->service->updateInvoice($invoice, ['items' => $items]);

        $this->assertEquals(150.00, $updatedInvoice->items()->first()->price);
        $this->assertEquals(150.00, $updatedInvoice->items()->first()->amount);
    }
}
