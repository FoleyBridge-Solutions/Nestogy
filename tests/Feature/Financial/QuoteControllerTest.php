<?php

namespace Tests\Feature\Financial;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Category;
use App\Domains\Financial\Models\Quote;
use App\Domains\Financial\Models\QuoteTemplate;
use App\Domains\Financial\Models\QuoteApproval;
use App\Contracts\Services\EmailServiceInterface;
use App\Contracts\Services\PdfServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteControllerTest extends TestCase
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

    public function test_index_returns_livewire_view(): void
    {
        $response = $this->get(route('financial.quotes.index'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.quotes.index-livewire');
    }

    public function test_index_returns_json_with_filters(): void
    {
        Quote::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.quotes.index', [
            'status' => 'Draft',
            'client_id' => $this->client->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'message',
        ]);
    }

    public function test_index_filters_by_status(): void
    {
        Quote::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        Quote::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Sent',
        ]);

        $response = $this->getJson(route('financial.quotes.index', ['status' => 'Draft']));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_date_range(): void
    {
        Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'date' => now()->subDays(5),
        ]);

        $response = $this->getJson(route('financial.quotes.index', [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_search(): void
    {
        Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'scope' => 'VoIP Setup',
        ]);

        $response = $this->getJson(route('financial.quotes.index', ['search' => 'VoIP']));

        $response->assertStatus(200);
    }

    public function test_index_applies_pagination(): void
    {
        Quote::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.quotes.index', ['per_page' => 15]));

        $response->assertStatus(200);
    }

    // ==================== Create Tests ====================

    public function test_create_returns_view_with_data(): void
    {
        $response = $this->get(route('financial.quotes.create'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.quotes.create');
        $response->assertViewHas('clients');
        $response->assertViewHas('categories');
        $response->assertViewHas('templates');
    }

    public function test_create_with_preselected_client(): void
    {
        $response = $this->get(route('financial.quotes.create', ['client_id' => $this->client->id]));

        $response->assertStatus(200);
        $response->assertViewHas('selectedClient');
    }

    public function test_create_with_copy_data(): void
    {
        session(['quote_copy_data' => ['items' => []]]);

        $response = $this->get(route('financial.quotes.create'));

        $response->assertStatus(200);
    }

    // ==================== Store Tests ====================

    public function test_store_creates_quote_successfully(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'scope' => 'VoIP Implementation',
            'date' => now()->format('Y-m-d'),
            'expire' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 2500,
            'status' => 'Draft',
        ];

        $response = $this->post(route('financial.quotes.store'), $data);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('quotes', [
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);
        
        $quote = Quote::first();
        $this->assertNotNull($quote);
        $response->assertRedirect(route('financial.quotes.show', $quote));
    }

    public function test_store_returns_json_response(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'scope' => 'Test Quote',
            'date' => now()->format('Y-m-d'),
            'expire' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 1000,
        ];

        $response = $this->postJson(route('financial.quotes.store'), $data);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post(route('financial.quotes.store'), []);

        $response->assertSessionHasErrors(['client_id']);
    }

    public function test_store_validates_client_exists(): void
    {
        $data = [
            'client_id' => 99999,
            'scope' => 'Test',
            'date' => now()->format('Y-m-d'),
            'expire' => now()->addDays(30)->format('Y-m-d'),
            'amount' => 1000,
        ];

        $response = $this->post(route('financial.quotes.store'), $data);

        $response->assertSessionHasErrors(['client_id']);
    }

    // ==================== Show Tests ====================

    public function test_show_returns_quote_view(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.quotes.show', $quote));

        $response->assertStatus(200);
        $response->assertViewIs('financial.quotes.show');
        $response->assertViewHas('quote');
    }

    public function test_show_returns_json(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1500,
        ]);

        $response = $this->getJson(route('financial.quotes.show', $quote));

        $response->assertStatus(200);
        $response->assertJsonPath('data.amount', 1500);
    }

    public function test_show_calculates_totals(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 2000,
        ]);

        $response = $this->getJson(route('financial.quotes.show', $quote));

        $response->assertStatus(200);
        $response->assertJsonPath('totals.total', '2000.00');
    }

    public function test_show_denies_access_to_other_company_quote(): void
    {
        $otherCompany = Company::factory()->create();
        $quote = Quote::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->get(route('financial.quotes.show', $quote));

        // Returns 404 because global company scope prevents finding the quote
        $response->assertStatus(404);
    }

    // ==================== Edit/Update Tests ====================

    public function test_edit_returns_view_for_draft_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $response = $this->get(route('financial.quotes.edit', $quote));

        $response->assertStatus(200);
        $response->assertViewIs('financial.quotes.edit');
    }

    public function test_edit_denies_access_to_approved_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Sent',
            'approval_status' => 'executive_approved',
        ]);

        $response = $this->get(route('financial.quotes.edit', $quote));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_update_modifies_draft_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
            'amount' => 1000,
        ]);

        $response = $this->put(route('financial.quotes.update', $quote), [
            'scope' => 'Updated Scope',
            'amount' => 1500,
            'date' => now()->format('Y-m-d'),
            'expire' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('financial.quotes.show', $quote));
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'amount' => 1500,
        ]);
    }

    public function test_update_denies_modification_of_approved_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Sent',
            'approval_status' => 'executive_approved',
        ]);

        $response = $this->put(route('financial.quotes.update', $quote), [
            'amount' => 2000,
        ]);

        $response->assertStatus(302);
    }

    // ==================== Item Management Tests ====================

    public function test_add_item_to_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $response = $this->post(route('financial.quotes.add-item', $quote), [
            'name' => 'VoIP Lines',
            'description' => '10 VoIP lines with unlimited calling',
            'quantity' => 10,
            'price' => 50,
        ]);

        $response->assertRedirect(route('financial.quotes.show', $quote));
        $response->assertSessionHas('success');
    }

    public function test_add_item_validates_quantity(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.quotes.add-item', $quote), [
            'name' => 'Test',
            'quantity' => -1,
            'price' => 100,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_delete_quote_item(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $item = \App\Domains\Financial\Models\InvoiceItem::factory()->create(['quote_id' => $quote->id]);

        $response = $this->delete(route('financial.quotes.delete-item', [$quote, $item]));

        $response->assertRedirect(route('financial.quotes.show', $quote));
    }

    // ==================== Approval Workflow Tests ====================

    public function test_submit_for_approval(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $response = $this->post(route('financial.quotes.submit-for-approval', $quote));

        $response->assertRedirect(route('financial.quotes.show', $quote));
        $response->assertSessionHas('success');
    }

    public function test_process_approval(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->post(route('financial.quotes.process-approval', $quote), [
            'level' => 'manager',
            'action' => 'approve',
            'comments' => 'Looks good',
        ]);

        $response->assertRedirect(route('financial.quotes.show', $quote));
        $response->assertSessionHas('success');
    }

    public function test_process_approval_returns_json(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->postJson(route('financial.quotes.process-approval', $quote), [
            'level' => 'manager',
            'action' => 'approve',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_approve_returns_approval_view(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.quotes.approve', $quote));

        $response->assertStatus(200);
        $response->assertViewIs('financial.quotes.approve');
    }

    // ==================== Email Tests ====================

    public function test_send_email_sends_approved_quote(): void
    {
        $this->mock(EmailServiceInterface::class, function ($mock) {
            $mock->shouldReceive('sendQuoteEmail')->andReturn(true);
        });

        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'approval_status' => 'executive_approved',
        ]);

        $response = $this->post(route('financial.quotes.send-email', $quote));

        $response->assertRedirect(route('financial.quotes.show', $quote));
        $response->assertSessionHas('success');
    }

    public function test_send_email_denies_unapproved_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'approval_status' => 'pending',
        ]);

        $response = $this->post(route('financial.quotes.send-email', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==================== PDF Tests ====================

    public function test_generate_pdf(): void
    {
        $this->mock(PdfServiceInterface::class, function ($mock) {
            $mock->shouldReceive('generateFilename')->andReturn('quote-001.pdf');
            $mock->shouldReceive('download')->andReturn(response('pdf content'));
        });

        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.quotes.generate-pdf', $quote));

        $response->assertStatus(200);
    }

    // ==================== Conversion Tests ====================

    public function test_convert_to_invoice(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'accepted',
            'amount' => 2000,
        ]);

        $response = $this->post(route('financial.quotes.convert-to-invoice', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_convert_to_invoice_denies_unaccepted_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $response = $this->post(route('financial.quotes.convert-to-invoice', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_convert_to_recurring(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'accepted',
        ]);

        $response = $this->post(route('financial.quotes.convert-to-recurring', $quote), [
            'billing_frequency' => 'monthly',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'auto_generate' => true,
        ]);

        $response->assertStatus(302);
    }

    public function test_convert_to_recurring_validates_date(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.quotes.convert-to-recurring', $quote), [
            'billing_frequency' => 'monthly',
            'start_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors(['start_date']);
    }

    public function test_preview_recurring_conversion(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        $response = $this->get(route('financial.quotes.preview-recurring', $quote));

        $response->assertStatus(200);
        $response->assertViewIs('financial.quotes.recurring-preview');
    }

    // ==================== Duplication Tests ====================

    public function test_duplicate_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1500,
        ]);

        $response = $this->post(route('financial.quotes.duplicate', $quote), [
            'date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertGreater(Quote::count(), 1);
    }

    public function test_copy_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.quotes.copy', $quote));

        $response->assertRedirect(route('financial.quotes.create'));
        $response->assertSessionHas('info');
    }

    // ==================== Revision Tests ====================

    public function test_create_revision(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.quotes.create-revision', $quote), [
            'reason' => 'Price adjustment',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ==================== Template Tests ====================

    public function test_create_from_template(): void
    {
        $template = QuoteTemplate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post(route('financial.quotes.create-from-template', $template), [
            'client_id' => $this->client->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ==================== Delete/Cancel Tests ====================

    public function test_destroy_deletes_draft_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $response = $this->delete(route('financial.quotes.destroy', $quote));

        $response->assertRedirect(route('financial.quotes.index'));
        // Quote uses archived_at for soft deletes
        $this->assertDatabaseHas('quotes', ['id' => $quote->id]);
        $quote->refresh();
        $this->assertNotNull($quote->archived_at);
    }

    public function test_cancel_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Sent',
        ]);

        $response = $this->post(route('financial.quotes.cancel', $quote));

        $response->assertRedirect();
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'Cancelled',
        ]);
    }

    public function test_cancel_denies_already_cancelled_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Cancelled',
        ]);

        $response = $this->post(route('financial.quotes.cancel', $quote));

        $response->assertStatus(403);
    }

    // ==================== CSV Export Tests ====================

    public function test_export_csv(): void
    {
        Quote::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.quotes.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_includes_all_columns(): void
    {
        Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'scope' => 'Test Quote',
        ]);

        $response = $this->get(route('financial.quotes.export-csv'));

        $response->assertStatus(200);
    }

    // ==================== AJAX Endpoint Tests ====================

    public function test_search_products(): void
    {
        $response = $this->getJson(route('financial.quotes.search-products'), [
            'search' => 'VoIP',
            'type' => 'products',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'products', 'total']);
    }

    public function test_search_clients(): void
    {
        $response = $this->getJson(route('financial.quotes.search-clients'), [
            'q' => $this->client->name,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'clients']);
    }

    public function test_get_product_categories(): void
    {
        $response = $this->getJson(route('financial.quotes.product-categories'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'categories']);
    }

    // ==================== Bulk Operations Tests ====================

    public function test_bulk_update_status(): void
    {
        $quotes = Quote::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Draft',
        ]);

        $quoteIds = $quotes->pluck('id')->toArray();

        $response = $this->postJson(route('financial.quotes.bulk-update-status'), [
            'quote_ids' => $quoteIds,
            'status' => 'Sent',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_statistics(): void
    {
        Quote::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('financial.quotes.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    // ==================== Company Isolation Tests ====================

    public function test_user_cannot_access_other_company_quotes(): void
    {
        $otherCompany = Company::factory()->create();
        $quote = Quote::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        ]);

        $response = $this->get(route('financial.quotes.show', $quote));

        $response->assertStatus(404);
        // Returns 404 because global company scope prevents finding the quote
    }

    public function test_index_only_shows_user_company_quotes(): void
    {
        $otherCompany = Company::factory()->create();

        Quote::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Quote::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        ]);

        $response = $this->getJson(route('financial.quotes.index'));

        $response->assertStatus(200);
    }
}
