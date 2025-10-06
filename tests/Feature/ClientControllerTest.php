<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    public function test_index_returns_view_for_authenticated_user(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.index-livewire');
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        auth()->logout();

        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_json_when_requested(): void
    {
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
        ]);
    }

    public function test_index_filters_by_search(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corporation',
            'lead' => false,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Tech Solutions',
            'lead' => false,
        ]);

        $response = $this->get(route('clients.index', ['search' => 'Acme']));

        $response->assertStatus(200);
    }

    public function test_index_excludes_leads(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Customer Client',
            'lead' => false,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Lead Client',
            'lead' => true,
        ]);

        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
    }

    public function test_create_returns_view(): void
    {
        $response = $this->get(route('clients.create'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.create');
    }

    public function test_store_creates_client_successfully(): void
    {
        $clientData = [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'company_name' => 'Test Company',
            'website' => 'https://test.com',
            'status' => 'active',
            'lead' => false,
        ];

        $response = $this->post(route('clients.store'), $clientData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_store_returns_json_for_json_request(): void
    {
        $clientData = [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'status' => 'active',
        ];

        $response = $this->postJson(route('clients.store'), $clientData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Client created successfully',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->post(route('clients.store'), []);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_store_sets_selected_client(): void
    {
        $clientData = [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'status' => 'active',
        ];

        $this->post(route('clients.store'), $clientData);

        $client = Client::where('email', 'test@example.com')->first();
        $this->assertEquals($client->id, session('selected_client_id'));
    }

    public function test_show_redirects_to_index_with_client_set(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('clients.show', $client));

        $response->assertRedirect(route('clients.index'));
        $this->assertEquals($client->id, session('selected_client_id'));
    }

    public function test_show_denies_access_to_other_company_client(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->get(route('clients.show', $client));

        $response->assertStatus(403);
    }

    public function test_update_modifies_client_successfully(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $updateData = [
            'name' => 'Updated Client Name',
            'email' => $client->email,
            'status' => 'active',
        ];

        $response = $this->put(route('clients.update', $client), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client Name',
        ]);
    }

    public function test_update_returns_json_for_json_request(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $updateData = [
            'name' => 'Updated Client Name',
            'email' => $client->email,
        ];

        $response = $this->putJson(route('clients.update', $client), $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Client updated successfully',
        ]);
    }

    public function test_destroy_permanently_deletes_client(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $clientId = $client->id;
        $client->delete();

        $response = $this->delete(route('clients.destroy', $client->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
    }

    public function test_export_csv_generates_csv_file(): void
    {
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $response = $this->get(route('clients.export.csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Client Name', $response->getContent());
    }

    public function test_get_active_clients_returns_active_clients_only(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'lead' => false,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'inactive',
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.active'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_get_active_clients_filters_by_search(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Searchable Client',
            'status' => 'active',
            'lead' => false,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Other Client',
            'status' => 'active',
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.active', ['q' => 'Searchable']));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_leads_returns_leads_view(): void
    {
        $response = $this->get(route('clients.leads'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.index-livewire-leads');
    }

    public function test_convert_lead_changes_lead_to_customer(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => true,
        ]);

        $response = $this->post(route('clients.convert-lead', $client));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'lead' => false,
        ]);
    }

    public function test_convert_lead_rejects_non_lead_client(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $response = $this->post(route('clients.convert-lead', $client));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_download_template_returns_csv(): void
    {
        $response = $this->get(route('clients.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('name', $response->getContent());
    }

    public function test_select_client_sets_session(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.select', $client));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals($client->id, session('selected_client_id'));
    }

    public function test_select_client_returns_json_when_requested(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->postJson(route('clients.select', $client));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Client selected successfully',
        ]);
    }

    public function test_select_screen_returns_view(): void
    {
        $response = $this->get(route('clients.select-screen'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.select-screen');
    }

    public function test_clear_selection_removes_client_from_session(): void
    {
        session(['selected_client_id' => 123]);

        $response = $this->get(route('clients.clear-selection'));

        $response->assertRedirect();
        $this->assertNull(session('selected_client_id'));
    }

    public function test_dynamic_index_shows_client_list_when_no_selection(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.index-livewire');
    }

    public function test_dynamic_index_shows_selected_client(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.show-livewire');
    }

    public function test_dynamic_index_clears_invalid_client_selection(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $otherCompany->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
        $this->assertNull(session('selected_client_id'));
    }

    public function test_leads_import_form_returns_view(): void
    {
        $response = $this->get(route('clients.leads.import.form'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.leads-import');
    }

    public function test_leads_import_template_returns_csv(): void
    {
        $response = $this->get(route('clients.leads.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
        $this->assertStringContainsString('Last', $response->getContent());
    }

    public function test_leads_import_creates_leads_from_csv(): void
    {
        Storage::fake('local');

        $csvContent = "First,Last,Email\nJohn,Doe,john@example.com\nJane,Smith,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
            'skip_duplicates' => false,
        ]);

        $response->assertRedirect(route('clients.leads'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'lead' => true,
            'company_id' => $this->company->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'lead' => true,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_leads_import_skips_duplicates_when_requested(): void
    {
        Storage::fake('local');

        Client::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'john@example.com',
            'lead' => true,
        ]);

        $csvContent = "First,Last,Email\nJohn,Doe,john@example.com\nJane,Smith,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
            'skip_duplicates' => true,
        ]);

        $response->assertRedirect(route('clients.leads'));

        $this->assertEquals(1, Client::where('email', 'john@example.com')->count());
        $this->assertEquals(1, Client::where('email', 'jane@example.com')->count());
    }

    public function test_leads_import_validates_file_type(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('leads.txt', 100);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
        ]);

        $response->assertSessionHasErrors('csv_file');
    }

    public function test_select_client_validates_return_to_url(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.select', $client), [
            'return_to' => '/clients/123/edit',
        ]);

        $response->assertRedirect('/clients/'.$client->id.'/edit');
    }

    public function test_select_client_rejects_external_redirect(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.select', $client), [
            'return_to' => 'https://evil.com',
        ]);

        $response->assertRedirect(route('clients.index', ['client' => $client->id]));
    }

    public function test_convert_lead_sets_selected_client(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => true,
        ]);

        $this->post(route('clients.convert-lead', $client));

        $this->assertEquals($client->id, session('selected_client_id'));
    }
}
