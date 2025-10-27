<?php

namespace Tests\Feature\Financial;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Category;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\InvoiceItem;
use App\Domains\Financial\Models\Payment;
use App\Contracts\Services\EmailServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Client $client;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->category = Category::factory()->create(['company_id' => $this->company->id]);

        // Set up permissions
        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();
        \Silber\Bouncer\BouncerFacade::refreshFor($this->user);

        $this->actingAs($this->user);
    }

    // ==================== Index Tests ====================

    public function test_index_returns_livewire_view_for_authenticated_user(): void
    {
        $response = $this->get(route('financial.invoices.index'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.index-livewire');
    }

    public function test_index_returns_json_when_requested(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.invoices.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invoices',
            'totals',
        ]);
    }

    public function test_index_filters_by_status(): void
    {
        Invoice::factory()->count(2)->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Invoice::factory()->count(3)->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.invoices.index', ['status' => 'Draft']));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_date_range(): void
    {
        Invoice::factory()->recent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.invoices.index', [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_search(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'scope' => 'Test scope',
        ]);

        $response = $this->getJson(route('financial.invoices.index', ['search' => 'Test']));

        $response->assertStatus(200);
    }

    public function test_index_calculates_statistics(): void
    {
        Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 500,
        ]);

        $response = $this->getJson(route('financial.invoices.index'));

        $response->assertStatus(200);
        // Amounts are returned as decimal strings
        $response->assertJsonPath('totals.paid', '1000.00');
        $response->assertJsonPath('totals.sent', '500.00');
    }

    public function test_index_applies_client_filter_from_session(): void
    {
        session(['selected_client_id' => $this->client->id]);

        Invoice::factory()->create(['company_id' => $this->company->id, 'client_id' => $this->client->id]);

        $response = $this->getJson(route('financial.invoices.index'));

        $response->assertStatus(200);
    }

    // ==================== Create/Store Tests ====================

    public function test_create_returns_view(): void
    {
        $response = $this->get(route('financial.invoices.create'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.create-livewire');
    }

    public function test_store_creates_invoice_successfully(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 1500,
            'status' => 'Draft',
            'currency_code' => 'USD',
            'note' => 'Test invoice',
        ];

        $response = $this->post(route('financial.invoices.store'), $data);

        $response->assertRedirect(route('financial.invoices.show', Invoice::first()));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_store_returns_json_response(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 1500,
            'status' => 'Draft',
            'currency_code' => 'USD',
        ];

        $response = $this->postJson(route('financial.invoices.store'), $data);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post(route('financial.invoices.store'), []);

        $response->assertSessionHasErrors(['client_id']);
    }

    public function test_store_validates_invalid_client(): void
    {
        $data = [
            'client_id' => 99999,
            'number' => 'INV-2024-001',
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 1500,
        ];

        $response = $this->post(route('financial.invoices.store'), $data);

        $response->assertSessionHasErrors(['client_id']);
    }

    // ==================== Show Tests ====================

    public function test_show_returns_invoice_view(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.show-livewire');
        $response->assertViewHas('invoice', $invoice);
    }

    public function test_show_loads_invoice_relationships(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        InvoiceItem::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $response = $this->getJson(route('financial.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertJsonPath('invoice.id', $invoice->id);
    }

    public function test_show_denies_access_to_other_company_invoice(): void
    {
        $otherCompany = Company::factory()->create();
        $invoice = Invoice::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->get(route('financial.invoices.show', $invoice));

        // Returns 404 because global company scope prevents finding the invoice
        $response->assertStatus(404);
    }

    public function test_show_calculates_invoice_totals(): void
    {
        $invoice = Invoice::factory()->withTotal(1500)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertJsonPath('totals.total', '1500.00');
    }

    // ==================== Edit/Update Tests ====================

    public function test_edit_returns_view_for_draft_invoice(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.invoices.edit', $invoice));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.edit-livewire');
    }

    public function test_edit_denies_access_to_sent_invoice(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.invoices.edit', $invoice));

        $response->assertStatus(302);
    }

    public function test_update_modifies_draft_invoice(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
            'category_id' => $this->category->id,
        ]);

        $response = $this->put(route('financial.invoices.update', $invoice), [
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'scope' => 'UPDATED',
            'amount' => 1500,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'Draft',
            'currency_code' => 'USD',
        ]);

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount' => 1500,
        ]);
    }

    public function test_update_denies_modification_of_sent_invoice(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->put(route('financial.invoices.update', $invoice), [
            'amount' => 2000,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    // ==================== Item Management Tests ====================

    public function test_add_item_to_invoice(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.invoices.add-item', $invoice), [
            'name' => 'Test Service',
            'description' => 'Service description',
            'quantity' => 2,
            'price' => 100,
        ]);

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'name' => 'Test Service',
        ]);
    }

    public function test_add_item_validates_required_fields(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.invoices.add-item', $invoice), [
            'name' => '',
            'quantity' => -1,
            'price' => -50,
        ]);

        $response->assertSessionHasErrors(['name', 'quantity', 'price']);
    }

    public function test_update_invoice_item(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Original Item',
            'price' => 100,
        ]);

        $response = $this->put(route('financial.invoices.update-item', [$invoice, $item]), [
            'name' => 'Updated Item',
            'description' => 'Updated description',
            'quantity' => 3,
            'price' => 150,
        ]);

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $this->assertDatabaseHas('invoice_items', [
            'id' => $item->id,
            'name' => 'Updated Item',
            'price' => 150,
        ]);
    }

    public function test_delete_invoice_item(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->delete(route('financial.invoices.delete-item', [$invoice, $item]));

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);
    }

    // ==================== Payment Tests ====================

    public function test_add_payment_to_invoice(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        $response = $this->post(route('financial.invoices.add-payment', $invoice), [
            'amount' => 500,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $response->assertSessionHas('success');
    }

    public function test_add_payment_validates_amount(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.invoices.add-payment', $invoice), [
            'amount' => -100,
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    // ==================== Status Update Tests ====================

    public function test_update_invoice_status(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->patch(route('financial.invoices.update-status', $invoice), [
            'status' => 'Sent',
        ]);

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Sent',
        ]);
    }

    public function test_update_status_validates_valid_statuses(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->patch(route('financial.invoices.update-status', $invoice), [
            'status' => 'Invalid',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    // ==================== Email Tests ====================

    public function test_send_email_updates_draft_status(): void
    {
        $this->mock(EmailServiceInterface::class, function ($mock) {
            $mock->shouldReceive('sendInvoiceEmail')->andReturn(true);
        });

        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.invoices.send-email', $invoice));

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Sent',
        ]);
    }

    public function test_send_email_does_not_update_already_sent_status(): void
    {
        $this->mock(EmailServiceInterface::class, function ($mock) {
            $mock->shouldReceive('sendInvoiceEmail')->andReturn(true);
        });

        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.invoices.send-email', $invoice));

        $response->assertRedirect(route('financial.invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Sent',
        ]);
    }

    // ==================== PDF Tests ====================

    public function test_generate_pdf(): void
    {
        $this->mock(PdfServiceInterface::class, function ($mock) {
            $mock->shouldReceive('generateFilename')->andReturn('invoice-001.pdf');
            $mock->shouldReceive('download')->andReturn(response('pdf content'));
        });

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.invoices.pdf', $invoice));

        $response->assertStatus(200);
    }

    // ==================== Copy/Duplicate Tests ====================

    public function test_duplicate_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        $response = $this->post(route('financial.invoices.duplicate', $invoice), [
            'date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertGreaterThan(1, Invoice::count());
    }

    // ==================== Delete Tests ====================

    public function test_destroy_deletes_invoice(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->delete(route('financial.invoices.destroy', $invoice));

        $response->assertRedirect(route('financial.invoices.index'));
        // Invoice uses archived_at for soft deletes
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        $invoice->refresh();
        $this->assertNotNull($invoice->archived_at);
    }

    public function test_destroy_denies_access_without_permission(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        // Revoke permissions
        \Silber\Bouncer\BouncerFacade::disallow($this->user)->everything();
        \Silber\Bouncer\BouncerFacade::refreshFor($this->user);
        $response = $this->delete(route('financial.invoices.destroy', $invoice));

        $response->assertStatus(403);
    }

    // ==================== CSV Export Tests ====================

    public function test_export_csv(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.invoices.export.csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_filters_by_client(): void
    {
        $client1 = Client::factory()->create(['company_id' => $this->company->id]);
        $client2 = Client::factory()->create(['company_id' => $this->company->id]);

        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $client1->id,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $client2->id,
        ]);

        $response = $this->get(route('financial.invoices.export.csv', ['client_id' => $client1->id]));

        $response->assertStatus(200);
    }

    // ==================== Notes Tests ====================

    public function test_update_notes(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->putJson(route('financial.invoices.update', $invoice), [
            'note' => 'Updated notes',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'note' => 'Updated notes',
        ]);
    }

    // ==================== Status Filter Routes Tests ====================

    public function test_overdue_route_returns_view(): void
    {
        $response = $this->get(route('financial.invoices.overdue'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.index-livewire');
    }

    public function test_draft_route_returns_view(): void
    {
        $response = $this->get(route('financial.invoices.draft'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.index-livewire');
    }

    public function test_sent_route_returns_view(): void
    {
        $response = $this->get(route('financial.invoices.sent'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.index-livewire');
    }

    public function test_paid_route_returns_view(): void
    {
        $response = $this->get(route('financial.invoices.paid'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.invoices.index-livewire');
    }

    // ==================== Company Isolation Tests ====================

    public function test_user_cannot_access_other_company_invoices(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $invoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        ]);

        $response = $this->get(route('financial.invoices.show', $invoice));

        // Returns 404 because global company scope prevents finding the invoice
        $response->assertStatus(404);
    }

    public function test_index_only_shows_user_company_invoices(): void
    {
        $otherCompany = Company::factory()->create();

        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        ]);

        $response = $this->getJson(route('financial.invoices.index'));

        $response->assertStatus(200);
    }
}
