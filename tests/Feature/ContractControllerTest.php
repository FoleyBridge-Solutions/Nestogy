<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Client;
use App\Models\User;
use App\Models\Company;
use App\Models\ContractTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContractControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_display_contracts_index()
    {
        Contract::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.contracts.index'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.contracts.index');
        $response->assertViewHas('contracts');
    }

    /** @test */
    public function it_can_show_contract_creation_form()
    {
        $response = $this->get(route('financial.contracts.create'));

        $response->assertStatus(200);
        $response->assertViewIs('financial.contracts.create');
        $response->assertViewHas(['clients', 'templates']);
    }

    /** @test */
    public function it_can_create_a_new_contract()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $contractData = [
            'client_id' => $this->client->id,
            'template_id' => $template->id,
            'title' => 'Test Service Agreement',
            'contract_type' => 'service_agreement',
            'contract_value' => 10000.00,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(12)->format('Y-m-d'),
            'terms' => 'Standard service terms and conditions.',
            'billing_frequency' => 'monthly',
            'payment_terms' => 'net_30',
        ];

        $response = $this->post(route('financial.contracts.store'), $contractData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('contracts', [
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Test Service Agreement',
            'contract_value' => 10000.00,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_contract()
    {
        $response = $this->post(route('financial.contracts.store'), []);

        $response->assertSessionHasErrors([
            'client_id',
            'title',
            'contract_type',
            'contract_value',
            'start_date',
            'end_date',
        ]);
    }

    /** @test */
    public function it_can_display_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.contracts.show', $contract));

        $response->assertStatus(200);
        $response->assertViewIs('financial.contracts.show');
        $response->assertViewHas('contract');
        $response->assertSee($contract->title);
    }

    /** @test */
    public function it_can_show_contract_edit_form()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.contracts.edit', $contract));

        $response->assertStatus(200);
        $response->assertViewIs('financial.contracts.edit');
        $response->assertViewHas(['contract', 'clients', 'templates']);
    }

    /** @test */
    public function it_can_update_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $updateData = [
            'client_id' => $this->client->id,
            'title' => 'Updated Service Agreement',
            'contract_type' => 'service_agreement',
            'contract_value' => 15000.00,
            'start_date' => $contract->start_date->format('Y-m-d'),
            'end_date' => $contract->end_date->format('Y-m-d'),
            'terms' => 'Updated terms and conditions.',
            'billing_frequency' => 'monthly',
            'payment_terms' => 'net_30',
        ];

        $response = $this->put(route('financial.contracts.update', $contract), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'title' => 'Updated Service Agreement',
            'contract_value' => 15000.00,
        ]);
    }

    /** @test */
    public function it_can_delete_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft', // Only draft contracts can be deleted
        ]);

        $response = $this->delete(route('financial.contracts.destroy', $contract));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }

    /** @test */
    public function it_prevents_deleting_active_contracts()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
        ]);

        $response = $this->delete(route('financial.contracts.destroy', $contract));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    /** @test */
    public function it_can_approve_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->post(route('financial.contracts.approve', $contract), [
            'comments' => 'Approved for implementation',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $contract->refresh();
        $this->assertEquals('approved', $contract->status);
    }

    /** @test */
    public function it_can_reject_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->post(route('financial.contracts.reject', $contract), [
            'reason' => 'Terms need revision',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $contract->refresh();
        $this->assertEquals('rejected', $contract->status);
    }

    /** @test */
    public function it_can_activate_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'approved',
        ]);

        $response = $this->post(route('financial.contracts.activate', $contract));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $contract->refresh();
        $this->assertEquals('active', $contract->status);
    }

    /** @test */
    public function it_can_terminate_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('financial.contracts.terminate', $contract), [
            'reason' => 'Client request',
            'termination_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $contract->refresh();
        $this->assertEquals('terminated', $contract->status);
    }

    /** @test */
    public function it_can_convert_contract_to_invoice()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('financial.contracts.convert-to-invoice', $contract), [
            'invoice_type' => 'milestone',
            'amount' => 2500.00,
            'description' => 'First milestone payment',
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $contract->id,
            'client_id' => $this->client->id,
            'amount' => 2500.00,
        ]);
    }

    /** @test */
    public function it_can_generate_contract_pdf()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.contracts.pdf', $contract));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_duplicate_a_contract()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->post(route('financial.contracts.duplicate', $contract));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertEquals(2, Contract::where('company_id', $this->company->id)->count());
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_other_company_contracts()
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);
        
        $contract = Contract::factory()->create([
            'company_id' => $otherCompany->id,
            'client_id' => $otherClient->id,
        ]);

        $response = $this->get(route('financial.contracts.show', $contract));
        $response->assertStatus(404);

        $response = $this->get(route('financial.contracts.edit', $contract));
        $response->assertStatus(404);

        $response = $this->delete(route('financial.contracts.destroy', $contract));
        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_authentication_for_contract_routes()
    {
        auth()->logout();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->get(route('financial.contracts.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('financial.contracts.show', $contract));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('financial.contracts.store'), []);
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_can_filter_contracts_by_status()
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('financial.contracts.index', ['status' => 'active']));
        
        $response->assertStatus(200);
        $response->assertViewHas('contracts');
        
        $contracts = $response->viewData('contracts');
        $this->assertEquals(1, $contracts->count());
        $this->assertEquals('active', $contracts->first()->status);
    }
}