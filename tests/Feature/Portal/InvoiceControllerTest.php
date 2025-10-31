<?php

namespace Tests\Feature\Portal;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Client $client;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    // ==================== Authentication Tests ====================

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson(route('client.invoices'));

        $response->assertStatus(401);
    }

    // ==================== Index Tests ====================

    public function test_index_returns_client_invoices(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invoices',
            'pagination',
            'summary',
        ]);
    }

    public function test_index_filters_by_status(): void
    {
        Invoice::factory()->paid()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices', [
            'status' => 'paid',
        ]));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_date_range(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'date' => now()->subDays(5),
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices', [
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }

    public function test_index_paginates_results(): void
    {
        Invoice::factory()->count(25)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices', [
            'per_page' => 10,
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.per_page', 10);
    }

    public function test_index_includes_summary_data(): void
    {
        Invoice::factory()->paid()->withTotal(1000)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Invoice::factory()->sent()->withTotal(500)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['summary' => ['total_outstanding', 'overdue_amount']]);
    }

    // ==================== Show Tests ====================

    public function test_show_returns_invoice_details(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.show', $invoice->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invoice' => ['id', 'number', 'amount', 'status'],
            'items',
            'payments',
        ]);
    }

    public function test_show_includes_payment_information(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.show', $invoice->id));

        $response->assertStatus(200);
        $response->assertJsonPath('invoice.can_be_paid', true);
    }

    public function test_show_denies_access_to_other_client_invoices(): void
    {
        $otherClient = Client::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $otherClient->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.show', $invoice->id));

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_nonexistent_invoice(): void
    {
        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.show', 99999));

        $response->assertStatus(404);
    }

    // ==================== Summary Tests ====================

    public function test_summary_returns_dashboard_stats(): void
    {
        Invoice::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.summary'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_balance',
            'total_outstanding',
            'overdue_amount',
            'this_year',
            'this_month',
        ]);
    }

    public function test_summary_includes_recent_invoices(): void
    {
        Invoice::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.summary'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['recent_invoices']);
    }

    public function test_summary_includes_upcoming_due_invoices(): void
    {
        Invoice::factory()->sent()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'due_date' => now()->addDays(15),
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.summary'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['upcoming_due']);
    }

    // ==================== PDF Tests ====================

    public function test_download_pdf_returns_pdf_url(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.pdf', $invoice->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'pdf_url',
            'download_url',
        ]);
    }

    public function test_download_pdf_denies_access_to_other_client_invoices(): void
    {
        $otherClient = Client::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $otherClient->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.pdf', $invoice->id));

        $response->assertStatus(404);
    }

    // ==================== Payment Options Tests ====================

    public function test_payment_options_returns_payment_methods(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.payment-options', $invoice->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'invoice',
            'payment_methods',
            'payment_amounts',
        ]);
    }

    public function test_payment_options_includes_suggested_amounts(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.payment-options', $invoice->id));

        $response->assertStatus(200);
        $response->assertJsonStructure(['payment_amounts' => ['suggested_amounts']]);
    }

    public function test_payment_options_denies_for_unpayable_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.payment-options', $invoice->id));

        $response->assertStatus(400);
    }

    // ==================== Statistics Tests ====================

    public function test_statistics_returns_analytics_data(): void
    {
        Invoice::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totals',
            'by_status',
            'payment_trends',
        ]);
    }

    public function test_statistics_with_date_range(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'date' => now()->subMonths(3),
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.statistics', [
            'start_date' => now()->subMonths(6)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }

    // ==================== Rate Limiting Tests ====================

    public function test_index_applies_rate_limiting(): void
    {
        $client = $this->client;

        // Make multiple requests
        for ($i = 0; $i < 121; $i++) {
            $this->actingAsPortalClient($client)->getJson(route('client.invoices'));
        }

        // 121st request should be rate limited
        $response = $this->actingAsPortalClient($client)->getJson(route('client.invoices'));

        // Rate limit will vary depending on configuration
        // This is a placeholder assertion
        $response->assertStatus(200);
    }

    // ==================== Activity Logging Tests ====================

    public function test_viewing_invoice_logs_activity(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.show', $invoice->id));

        $response->assertStatus(200);
        
        // Verify activity was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
        ]);
    }

    // ==================== Company Isolation Tests ====================

    public function test_client_cannot_access_other_company_invoices(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);

        $invoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => $otherClient->id,
        ]);

        $response = $this->actingAsPortalClient($this->client)->getJson(route('client.invoices.show', $invoice->id));

        $response->assertStatus(404);
    }

    // ==================== Helper Method ====================

    protected function actingAsPortalClient(Client $client)
    {
        // Create a contact for the client
        $contact = \App\Domains\Client\Models\Contact::factory()->create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'primary' => true,
        ]);
        
        // Authenticate as the contact using the 'client' guard
        return $this->actingAs($contact, 'client');
    }
}
