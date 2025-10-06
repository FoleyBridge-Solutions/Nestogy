<?php

namespace Tests\Feature\Controllers;

use App\Domains\Client\Services\ClientService;
use App\Imports\ClientsImport;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
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

    public function test_index_filters_by_search_term(): void
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

    public function test_index_filters_by_type(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'business',
            'lead' => false,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'individual',
            'lead' => false,
        ]);

        $response = $this->get(route('clients.index', ['type' => 'business']));

        $response->assertStatus(200);
    }

    public function test_index_filters_by_status(): void
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

        $response = $this->get(route('clients.index', ['status' => 'active']));

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

    public function test_index_excludes_archived_clients(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Active Client',
            'lead' => false,
            'archived_at' => null,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Archived Client',
            'lead' => false,
            'archived_at' => now(),
        ]);

        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
    }

    public function test_index_only_shows_company_clients(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $otherCompany = Company::factory()->create();
        Client::factory()->create([
            'company_id' => $otherCompany->id,
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.index'));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
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

    public function test_store_returns_json_for_api_request(): void
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
        $response->assertJsonStructure(['client' => ['client_id', 'name']]);
    }

    public function test_store_validates_required_name(): void
    {
        $response = $this->post(route('clients.store'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_validates_required_email(): void
    {
        $response = $this->post(route('clients.store'), [
            'name' => 'Test Client',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_format(): void
    {
        $response = $this->post(route('clients.store'), [
            'name' => 'Test Client',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_sets_selected_client_in_session(): void
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

    public function test_store_logs_client_creation(): void
    {
        $clientData = [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'status' => 'active',
        ];

        $response = $this->post(route('clients.store'), $clientData);

        $response->assertSessionHas('success');
    }

    public function test_store_handles_errors_gracefully(): void
    {
        $this->mock(ClientService::class, function ($mock) {
            $mock->shouldReceive('createClient')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->post(route('clients.store'), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_show_redirects_to_index_with_client_set_in_session(): void
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

    public function test_show_updates_client_accessed_at_timestamp(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'accessed_at' => null,
        ]);

        session(['selected_client_id' => $client->id]);

        $this->get(route('clients.index'));

        $client->refresh();
        $this->assertNotNull($client->accessed_at);
    }

    public function test_show_loads_related_data(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        Contact::factory()->create(['client_id' => $client->id, 'company_id' => $this->company->id]);
        Location::factory()->create(['client_id' => $client->id, 'company_id' => $this->company->id]);

        session(['selected_client_id' => $client->id]);

        $response = $this->get(route('clients.index'));

        $response->assertStatus(200);
    }

    public function test_show_returns_json_for_api_request(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('clients.show', $client));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'status',
            'stats',
            'metrics',
        ]);
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

    public function test_update_returns_json_for_api_request(): void
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

    public function test_update_validates_required_fields(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->put(route('clients.update', $client), [
            'email' => $client->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_denies_access_to_other_company_client(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'email' => $client->email,
        ]);

        $response->assertStatus(403);
    }

    public function test_update_logs_modification(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'email' => $client->email,
        ]);

        $response->assertSessionHas('success');
    }

    public function test_update_handles_errors_gracefully(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->mock(ClientService::class, function ($mock) use ($client) {
            $mock->shouldReceive('updateClient')
                ->andThrow(new \Exception('Update failed'));
        });

        $response = $this->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'email' => $client->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_archive_soft_deletes_client(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->post(route('clients.archive'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_archive_requires_authenticated_client_in_session(): void
    {
        $response = $this->post(route('clients.archive'));

        $response->assertStatus(302);
    }

    public function test_archive_denies_access_to_other_company_client(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $otherCompany->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->post(route('clients.archive'));

        $response->assertStatus(302);
    }

    public function test_restore_undeletes_archived_client(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $client->delete();
        session(['selected_client_id' => $client->id]);

        $response = $this->post(route('clients.restore'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'archived_at' => null,
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

    public function test_destroy_requires_client_to_be_soft_deleted_first(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'archived_at' => null,
        ]);
    }

    public function test_destroy_denies_access_to_other_company_client(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $otherCompany->id]);
        $client->delete();

        $response = $this->delete(route('clients.destroy', $client->id));

        $response->assertStatus(403);
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

    public function test_export_csv_includes_only_company_clients(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'My Company Client',
            'lead' => false,
        ]);

        $otherCompany = Company::factory()->create();
        Client::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Client',
            'lead' => false,
        ]);

        $response = $this->get(route('clients.export.csv'));

        $content = $response->getContent();
        $this->assertStringContainsString('My Company Client', $content);
        $this->assertStringNotContainsString('Other Company Client', $content);
    }

    public function test_export_csv_excludes_archived_clients(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Active Client',
            'lead' => false,
            'archived_at' => null,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Archived Client',
            'lead' => false,
            'archived_at' => now(),
        ]);

        $response = $this->get(route('clients.export.csv'));

        $content = $response->getContent();
        $this->assertStringContainsString('Active Client', $content);
        $this->assertStringNotContainsString('Archived Client', $content);
    }

    public function test_export_csv_includes_contact_and_location_data(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'company_id' => $this->company->id,
            'primary' => true,
        ]);

        $location = Location::factory()->create([
            'client_id' => $client->id,
            'company_id' => $this->company->id,
            'primary' => true,
        ]);

        $response = $this->get(route('clients.export.csv'));

        $content = $response->getContent();
        $this->assertStringContainsString($contact->name, $content);
    }

    public function test_update_notes_updates_client_notes(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->patchJson(route('clients.update-notes'), [
            'notes' => 'Updated notes content',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'notes' => 'Updated notes content',
        ]);
    }

    public function test_update_notes_allows_null_notes(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'notes' => 'Existing notes',
        ]);
        session(['selected_client_id' => $client->id]);

        $response = $this->patchJson(route('clients.update-notes'), [
            'notes' => null,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'notes' => null,
        ]);
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

    public function test_get_active_clients_excludes_leads(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'lead' => false,
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'lead' => true,
        ]);

        $response = $this->getJson(route('clients.active'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_get_active_clients_filters_by_search_query(): void
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

    public function test_get_active_clients_limits_results_to_50(): void
    {
        Client::factory()->count(60)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.active'));

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(50, count($response->json()));
    }

    public function test_get_active_clients_orders_by_accessed_at_desc(): void
    {
        $client1 = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'lead' => false,
            'accessed_at' => now()->subDays(2),
        ]);

        $client2 = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'lead' => false,
            'accessed_at' => now()->subDay(),
        ]);

        $response = $this->getJson(route('clients.active'));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals($client2->id, $data[0]['id']);
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

    public function test_convert_lead_sets_selected_client(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => true,
        ]);

        $this->post(route('clients.convert-lead', $client));

        $this->assertEquals($client->id, session('selected_client_id'));
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

    public function test_convert_lead_returns_json_for_api_request(): void
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'lead' => true,
        ]);

        $response = $this->postJson(route('clients.convert-lead', $client));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Lead converted to customer successfully',
        ]);
    }

    public function test_convert_lead_denies_access_to_other_company_client(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $otherCompany->id,
            'lead' => true,
        ]);

        $response = $this->post(route('clients.convert-lead', $client));

        $response->assertStatus(403);
    }

    public function test_import_form_returns_view(): void
    {
        $response = $this->get(route('clients.import.form'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.import');
    }

    public function test_import_processes_csv_file(): void
    {
        Storage::fake('local');
        Excel::fake();

        $file = UploadedFile::fake()->create('clients.csv', 100, 'text/csv');

        $response = $this->post(route('clients.import'), [
            'file' => $file,
        ]);

        Excel::assertImported('clients.csv', function (ClientsImport $import) {
            return true;
        });
    }

    public function test_import_validates_file_required(): void
    {
        $response = $this->post(route('clients.import'), []);

        $response->assertSessionHasErrors('file');
    }

    public function test_import_validates_file_type(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('clients.pdf', 100);

        $response = $this->post(route('clients.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_import_validates_file_size(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('clients.csv', 11000, 'text/csv');

        $response = $this->post(route('clients.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_download_template_returns_csv(): void
    {
        $response = $this->get(route('clients.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('name', $response->getContent());
        $this->assertStringContainsString('email', $response->getContent());
    }

    public function test_download_template_includes_sample_row(): void
    {
        $response = $this->get(route('clients.import.template'));

        $content = $response->getContent();
        $this->assertStringContainsString('Acme Corporation', $content);
    }

    public function test_tags_get_returns_view(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->get(route('clients.tags'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.tags');
        $response->assertViewHas('client');
    }

    public function test_tags_post_updates_client_tags(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        session(['selected_client_id' => $client->id]);

        $tag1 = Tag::factory()->create(['company_id' => $this->company->id]);
        $tag2 = Tag::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.tags'), [
            'tags' => [$tag1->id, $tag2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_tags_validates_tag_ids_exist(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        session(['selected_client_id' => $client->id]);

        $response = $this->post(route('clients.tags'), [
            'tags' => [99999],
        ]);

        $response->assertSessionHasErrors('tags.0');
    }

    public function test_select_client_sets_session(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.select', $client));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals($client->id, session('selected_client_id'));
    }

    public function test_select_client_returns_json_for_api_request(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->postJson(route('clients.select', $client));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Client selected successfully',
        ]);
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

    public function test_select_client_rejects_protocol_relative_urls(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.select', $client), [
            'return_to' => '//evil.com/path',
        ]);

        $response->assertRedirect(route('clients.index', ['client' => $client->id]));
    }

    public function test_select_client_updates_client_id_in_return_url(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $response = $this->post(route('clients.select', $client), [
            'return_to' => '/clients/999/tickets',
        ]);

        $response->assertRedirect('/clients/'.$client->id.'/tickets');
    }

    public function test_select_client_denies_access_to_other_company_client(): void
    {
        $otherCompany = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->post(route('clients.select', $client));

        $response->assertStatus(403);
    }

    public function test_select_screen_returns_view(): void
    {
        $response = $this->get(route('clients.select-screen'));

        $response->assertStatus(200);
        $response->assertViewIs('clients.select-screen');
    }

    public function test_select_screen_filters_by_search(): void
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Searchable Client',
            'status' => 'active',
            'lead' => false,
        ]);

        $response = $this->get(route('clients.select-screen', ['search' => 'Searchable']));

        $response->assertStatus(200);
    }

    public function test_clear_selection_removes_client_from_session(): void
    {
        session(['selected_client_id' => 123]);

        $response = $this->get(route('clients.clear-selection'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNull(session('selected_client_id'));
    }

    public function test_clear_selection_returns_json_for_api_request(): void
    {
        session(['selected_client_id' => 123]);

        $response = $this->getJson(route('clients.clear-selection'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Client selection cleared',
        ]);
    }

    public function test_validate_batch_returns_valid_client_ids(): void
    {
        $client1 = Client::factory()->create(['company_id' => $this->company->id]);
        $client2 = Client::factory()->create(['company_id' => $this->company->id]);
        $otherClient = Client::factory()->create();

        $response = $this->postJson(route('clients.validate-batch'), [
            'ids' => [$client1->id, $client2->id, $otherClient->id, 99999],
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment([$client1->id, $client2->id]);
    }

    public function test_validate_batch_validates_ids_are_required(): void
    {
        $response = $this->postJson(route('clients.validate-batch'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ids');
    }

    public function test_validate_batch_validates_ids_are_integers(): void
    {
        $response = $this->postJson(route('clients.validate-batch'), [
            'ids' => ['not-an-integer'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ids.0');
    }

    public function test_validate_batch_excludes_archived_clients(): void
    {
        $activeClient = Client::factory()->create(['company_id' => $this->company->id]);
        $archivedClient = Client::factory()->create([
            'company_id' => $this->company->id,
            'archived_at' => now(),
        ]);

        $response = $this->postJson(route('clients.validate-batch'), [
            'ids' => [$activeClient->id, $archivedClient->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([$activeClient->id]);
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

    public function test_dynamic_index_redirects_unauthenticated_user(): void
    {
        auth()->logout();

        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login'));
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
        $this->assertStringContainsString('First', $response->getContent());
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

    public function test_leads_import_validates_required_fields(): void
    {
        $response = $this->post(route('clients.leads.import'), []);

        $response->assertSessionHasErrors(['csv_file', 'default_status']);
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

    public function test_leads_import_validates_default_status_values(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('leads.csv', 100, 'text/csv');

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'invalid-status',
        ]);

        $response->assertSessionHasErrors('default_status');
    }

    public function test_leads_import_adds_import_notes_when_provided(): void
    {
        Storage::fake('local');

        $csvContent = "First,Last,Email\nJohn,Doe,john@example.com";
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
            'import_notes' => 'Imported from trade show',
        ]);

        $response->assertRedirect(route('clients.leads'));

        $this->assertDatabaseHas('clients', [
            'email' => 'john@example.com',
            'notes' => 'Imported from trade show',
        ]);
    }

    public function test_leads_import_validates_email_format(): void
    {
        Storage::fake('local');

        $csvContent = "First,Last,Email\nJohn,Doe,invalid-email";
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
        ]);

        $response->assertRedirect(route('clients.leads'));
        $response->assertSessionHas('import_details');
    }

    public function test_leads_import_handles_empty_rows(): void
    {
        Storage::fake('local');

        $csvContent = "First,Last,Email\nJohn,Doe,john@example.com\n,,,\nJane,Smith,jane@example.com";
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
        ]);

        $response->assertRedirect(route('clients.leads'));

        $this->assertEquals(2, Client::where('lead', true)->count());
    }

    public function test_leads_import_supports_various_column_names(): void
    {
        Storage::fake('local');

        $csvContent = "firstname,surname,e-mail\nJohn,Doe,john@example.com";
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csvContent);

        $response = $this->post(route('clients.leads.import'), [
            'csv_file' => $file,
            'default_status' => 'active',
        ]);

        $response->assertRedirect(route('clients.leads'));

        $this->assertDatabaseHas('clients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_switch_redirects_with_info_message(): void
    {
        $response = $this->get(route('clients.switch'));

        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('info');
    }

    public function test_data_endpoint_returns_datatables_response(): void
    {
        Client::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ]);
    }

    public function test_data_endpoint_supports_search(): void
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

        $response = $this->getJson(route('clients.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Acme'],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_data_endpoint_supports_sorting(): void
    {
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 1, 'dir' => 'asc']],
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_data_endpoint_supports_pagination(): void
    {
        Client::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'lead' => false,
        ]);

        $response = $this->getJson(route('clients.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => ''],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data);
    }
}
