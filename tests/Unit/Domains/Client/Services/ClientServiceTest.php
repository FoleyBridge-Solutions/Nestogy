<?php

namespace Tests\Unit\Domains\Client\Services;

use App\Domains\Client\Services\ClientService;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientServiceTest extends TestCase
{
    private ClientService $service;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ClientService::class);
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_create_a_client()
    {
        $data = [
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'phone' => '123-456-7890',
            'website' => 'https://testclient.com',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TX',
            'zip' => '12345',
            'country' => 'USA',
        ];

        $client = $this->service->create($data);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Test Client', $client->name);
        $this->assertEquals('client@test.com', $client->email);
        $this->assertEquals($this->company->id, $client->company_id);
    }

    #[Test]
    public function it_can_update_a_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ];

        $updatedClient = $this->service->update($client, $updateData);

        $this->assertEquals('Updated Name', $updatedClient->name);
        $this->assertEquals('updated@test.com', $updatedClient->email);
    }

    #[Test]
    public function it_can_delete_a_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $result = $this->service->delete($client);

        $this->assertTrue($result);
        $this->assertSoftDeleted($client);
    }

    #[Test]
    public function it_can_search_clients()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Corporation',
            'email' => 'alpha@corp.com',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beta Industries',
            'email' => 'beta@industries.com',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Gamma Solutions',
            'email' => 'gamma@solutions.com',
        ]);

        // Search by name
        $results = $this->service->search('Alpha');
        $this->assertCount(1, $results);
        $this->assertEquals('Alpha Corporation', $results->first()->name);

        // Search by email
        $results = $this->service->search('industries.com');
        $this->assertCount(1, $results);
        $this->assertEquals('Beta Industries', $results->first()->name);
    }

    #[Test]
    public function it_can_get_client_statistics()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create related data (invoices, tickets, etc.)
        // This would normally involve creating related models

        $stats = $this->service->getStatistics($client);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('open_tickets', $stats);
        $this->assertArrayHasKey('total_assets', $stats);
    }

    #[Test]
    public function it_respects_company_boundaries()
    {
        $otherCompany = Company::factory()->create();
        
        // Create client for other company
        Client::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Client',
        ]);

        // Create client for our company
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Our Company Client',
        ]);

        $clients = $this->service->getAllForCompany();

        $this->assertCount(1, $clients);
        $this->assertEquals('Our Company Client', $clients->first()->name);
    }

    #[Test]
    public function it_can_get_active_clients()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Active Client',
            'status' => 'active',
        ]);

        Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Client',
            'status' => 'inactive',
        ]);

        $activeClients = $this->service->getActive();

        $this->assertCount(1, $activeClients);
        $this->assertEquals('Active Client', $activeClients->first()->name);
    }

    #[Test]
    public function it_can_export_client_data()
    {
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $exportData = $this->service->exportToArray();

        $this->assertIsArray($exportData);
        $this->assertCount(3, $exportData);
        
        foreach ($exportData as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('email', $row);
            $this->assertArrayHasKey('phone', $row);
        }
    }

    #[Test]
    public function it_validates_email_uniqueness_within_company()
    {
        Client::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@test.com',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->create([
            'name' => 'New Client',
            'email' => 'existing@test.com',
        ]);
    }

    #[Test]
    public function it_can_merge_duplicate_clients()
    {
        $primaryClient = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Primary Client',
        ]);

        $duplicateClient = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Duplicate Client',
        ]);

        $mergedClient = $this->service->merge($primaryClient, $duplicateClient);

        $this->assertEquals($primaryClient->id, $mergedClient->id);
        $this->assertSoftDeleted($duplicateClient);
    }
}