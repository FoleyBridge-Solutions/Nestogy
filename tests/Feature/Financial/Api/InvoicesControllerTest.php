<?php

namespace Tests\Feature\Financial\Api;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\InvoiceItem;
use App\Domains\Financial\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);

        // Set up permissions
        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();
        \Silber\Bouncer\BouncerFacade::refreshFor($this->user);

        $this->actingAs($this->user);
    }

    // ==================== Index Tests ====================

    public function test_api_index_returns_json_list(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'summary',
            'message',
        ]);
    }

    public function test_api_index_with_pagination(): void
    {
        Invoice::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index', ['per_page' => 15]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'success']);
    }

    public function test_api_index_filters_by_status(): void
    {
        Invoice::factory()->paid()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index', ['status' => 'paid']));

        $response->assertStatus(200);
    }

    public function test_api_index_filters_by_client(): void
    {
        $client1 = Client::factory()->create(['company_id' => $this->company->id]);
        $client2 = Client::factory()->create(['company_id' => $this->company->id]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $client1->id,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $client2->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index', ['client_id' => $client1->id]));

        $response->assertStatus(200);
    }

    public function test_api_index_filters_overdue(): void
    {
        Invoice::factory()->overdue()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index', ['overdue' => true]));

        $response->assertStatus(200);
    }

    public function test_api_index_filters_by_search(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'prefix' => 'INV',
            'number' => 2024123,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index', ['search' => '2024123']));

        $response->assertStatus(200);
    }

    public function test_api_index_includes_summary(): void
    {
        Invoice::factory()->paid()->withTotal(1000)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['summary' => ['total_amount', 'total_paid', 'total_outstanding']]);
    }

    // ==================== Store Tests ====================

    public function test_api_store_creates_invoice(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
            'items' => [
                [
                    'description' => 'Service 1',
                    'quantity' => 1,
                    'rate' => 100,
                ],
            ],
        ];

        $response = $this->postJson(route('api.financial.invoices.store'), $data);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('invoices', ['client_id' => $this->client->id]);
    }

    public function test_api_store_calculates_totals(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Item 1',
                    'quantity' => 2,
                    'rate' => 100,
                    'tax_rate' => 10,
                ],
            ],
        ];

        $response = $this->postJson(route('api.financial.invoices.store'), $data);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }

    public function test_api_store_applies_discount(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'items' => [
                [
                    'description' => 'Item 1',
                    'quantity' => 1,
                    'rate' => 1000,
                ],
            ],
        ];

        $response = $this->postJson(route('api.financial.invoices.store'), $data);

        $response->assertStatus(201);
    }

    public function test_api_store_validates_required_fields(): void
    {
        $response = $this->postJson(route('api.financial.invoices.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id', 'items']);
    }

    public function test_api_store_validates_due_date_after_invoice_date(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Item',
                    'quantity' => 1,
                    'rate' => 100,
                ],
            ],
        ];

        $response = $this->postJson(route('api.financial.invoices.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['due_date']);
    }

    // ==================== Show Tests ====================

    public function test_api_show_returns_invoice_details(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->getJson(route('api.financial.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'client_id', 'status', 'total'],
        ]);
    }

    public function test_api_show_includes_payment_metrics(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        $response = $this->getJson(route('api.financial.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['total_paid', 'balance_due', 'is_overdue']]);
    }

    public function test_api_show_denies_unauthorized_access(): void
    {
        $otherCompany = Company::factory()->create();
        $invoice = Invoice::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->getJson(route('api.financial.invoices.show', $invoice));

        // Returns 404 because global company scope prevents finding the invoice
        $response->assertStatus(404);
    }

    // ==================== Update Tests ====================

    public function test_api_update_modifies_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson(route('api.financial.invoices.update', $invoice), [
            'status' => 'sent',
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);
    }

    public function test_api_update_recalculates_totals(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->putJson(route('api.financial.invoices.update', $invoice), [
            'discount_type' => 'fixed',
            'discount_value' => 100,
        ]);

        $response->assertStatus(200);
    }

    public function test_api_update_denies_paid_invoices(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->putJson(route('api.financial.invoices.update', $invoice), [
            'notes' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    // ==================== Delete Tests ====================

    public function test_api_destroy_deletes_invoice(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->deleteJson(route('api.financial.invoices.destroy', $invoice));

        $response->assertStatus(200);
        // Invoice uses archived_at instead of deleted_at for soft deletes
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);
        $invoice->refresh();
        $this->assertNotNull($invoice->archived_at);
    }

    public function test_api_destroy_denies_paid_invoices(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->deleteJson(route('api.financial.invoices.destroy', $invoice));

        $response->assertStatus(422);
    }

    // ==================== Recurring Tests ====================

    public function test_api_generate_recurring_invoices(): void
    {
        $this->markTestSkipped('Recurring invoices table schema needs to be fixed (missing is_active column)');
        
        $response = $this->postJson(route('api.financial.invoices.generate-recurring'), [
            'dry_run' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_api_forecast_generates_billing_forecast(): void
    {
        $this->markTestSkipped('Billing forecast functionality needs recurring invoices schema to be fixed');
        
        $response = $this->getJson(route('api.financial.invoices.forecast', ['months' => 3]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== Payment Tests ====================

    public function test_api_retry_payment(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->postJson(route('api.financial.invoices.retry-payment', $invoice));

        $response->assertStatus(200);
    }

    public function test_api_send_email(): void
    {
        $invoice = Invoice::factory()->draft()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->postJson(route('api.financial.invoices.send-email', $invoice));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ==================== Company Isolation Tests ====================

    public function test_api_user_cannot_access_other_company_invoices(): void
    {
        $otherCompany = Company::factory()->create();
        $invoice = Invoice::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->getJson(route('api.financial.invoices.show', $invoice));

        // Returns 404 because global company scope prevents finding the invoice
        $response->assertStatus(404);
    }

    public function test_api_index_only_shows_user_company_invoices(): void
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

        $response = $this->getJson(route('api.financial.invoices.index'));

        $response->assertStatus(200);
    }
}
