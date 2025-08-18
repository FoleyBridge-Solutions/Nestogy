<?php

namespace Tests\Unit\Traits;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BelongsToCompanyTest extends TestCase
{
    use DatabaseTransactions;
    
    private Company $primaryCompany;
    private Company $secondaryCompany;
    private User $primaryUser;
    private User $secondaryUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test companies using existing data
        $this->primaryCompany = Company::first() ?? Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);
        
        $this->secondaryCompany = Company::create([
            'name' => 'Secondary Company',
            'email' => 'secondary@company.com',
        ]);
        
        // Create test users
        $this->primaryUser = User::create([
            'company_id' => $this->primaryCompany->id,
            'name' => 'Test User',
            'email' => 'test@user.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->secondaryUser = User::create([
            'company_id' => $this->secondaryCompany->id,
            'name' => 'Secondary User',
            'email' => 'secondary@user.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function it_automatically_sets_company_id_when_creating_model()
    {
        $this->actingAs($this->primaryUser);
        
        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);

        $this->assertEquals($this->primaryCompany->id, $client->company_id);
    }

    /** @test */
    public function it_does_not_override_explicitly_set_company_id()
    {
        $this->actAsUser();
        
        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'company_id' => $this->secondaryCompany->id,
        ]);

        // Should use the explicitly set company_id
        $this->assertEquals($this->secondaryCompany->id, $client->company_id);
    }

    /** @test */
    public function it_applies_global_scope_to_filter_by_company()
    {
        $this->actAsUser();
        
        // Create clients for both companies
        $primaryClient = Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
            'company_id' => $this->testCompany->id,
        ]);
        
        $secondaryClient = Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
            'company_id' => $this->secondaryCompany->id,
        ]);

        // Should only see clients from authenticated user's company
        $clients = Client::all();
        
        $this->assertCount(1, $clients);
        $this->assertEquals($primaryClient->id, $clients->first()->id);
        $this->assertBelongsToTestCompany($clients->first());
    }

    /** @test */
    public function it_can_query_specific_company_with_scope()
    {
        $this->actAsUser();
        
        // Create clients for both companies
        Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
            'company_id' => $this->testCompany->id,
        ]);
        
        $secondaryClient = Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
            'company_id' => $this->secondaryCompany->id,
        ]);

        // Query for secondary company specifically
        $secondaryClients = Client::forCompany($this->secondaryCompany->id)->get();
        
        $this->assertCount(1, $secondaryClients);
        $this->assertEquals($secondaryClient->id, $secondaryClients->first()->id);
        $this->assertEquals($this->secondaryCompany->id, $secondaryClients->first()->company_id);
    }

    /** @test */
    public function it_uses_current_user_company_when_no_company_specified_in_scope()
    {
        $this->actAsUser();
        
        // Create clients for both companies
        $primaryClient = Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
            'company_id' => $this->testCompany->id,
        ]);
        
        Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
            'company_id' => $this->secondaryCompany->id,
        ]);

        // Using forCompany() without parameters should use current user's company
        $clients = Client::forCompany()->get();
        
        $this->assertCount(1, $clients);
        $this->assertEquals($primaryClient->id, $clients->first()->id);
        $this->assertBelongsToTestCompany($clients->first());
    }

    /** @test */
    public function it_returns_all_records_when_no_company_id_in_scope_and_no_auth()
    {
        Auth::logout();
        
        // Create clients for both companies
        Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
            'company_id' => $this->testCompany->id,
        ]);
        
        Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
            'company_id' => $this->secondaryCompany->id,
        ]);

        $clients = Client::forCompany()->get();
        
        // When no company ID and no auth, should return query as-is
        $this->assertCount(2, $clients);
    }

    /** @test */
    public function it_establishes_company_relationship()
    {
        $this->actAsUser();
        
        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);

        $this->assertInstanceOf(Company::class, $client->company);
        $this->assertEquals($this->testCompany->id, $client->company->id);
        $this->assertEquals($this->testCompany->name, $client->company->name);
    }

    /** @test */
    public function it_maintains_tenant_isolation_across_different_users()
    {
        // Act as primary company user
        $this->actAsUser();
        
        $primaryClient = Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
        ]);

        $primaryClients = Client::all();
        $this->assertCount(1, $primaryClients);
        $this->assertBelongsToTestCompany($primaryClients->first());

        // Switch to secondary company user
        $this->actingAs($this->secondaryUser);
        
        $secondaryClient = Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
        ]);

        $secondaryClients = Client::all();
        $this->assertCount(1, $secondaryClients);
        $this->assertEquals($this->secondaryCompany->id, $secondaryClients->first()->company_id);
        
        // Ensure we can't see the primary company's client
        $this->assertNotEquals($primaryClient->id, $secondaryClients->first()->id);
    }

    /** @test */
    public function it_can_bypass_global_scope_when_needed()
    {
        $this->actAsUser();
        
        // Create clients for both companies
        Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
            'company_id' => $this->testCompany->id,
        ]);
        
        Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
            'company_id' => $this->secondaryCompany->id,
        ]);

        // Bypass the company scope to see all clients
        $allClients = Client::withoutGlobalScope('company')->get();
        
        $this->assertCount(2, $allClients);
    }

    /** @test */
    public function it_does_not_set_company_id_when_no_authenticated_user()
    {
        Auth::logout();
        
        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);

        $this->assertNull($client->company_id);
    }

    /** @test */
    public function it_applies_scope_correctly_when_switching_authenticated_users()
    {
        // Create client as primary user
        $this->actAsUser();
        
        $primaryClient = Client::create([
            'name' => 'Primary Client',
            'email' => 'primary@test.com',
        ]);

        // Switch to secondary user and create client
        $this->actingAs($this->secondaryUser);
        
        $secondaryClient = Client::create([
            'name' => 'Secondary Client',
            'email' => 'secondary@test.com',
        ]);

        // Verify each user can only see their own company's clients
        $this->actAsUser();
        $primaryClients = Client::all();
        $this->assertCount(1, $primaryClients);
        $this->assertEquals($primaryClient->id, $primaryClients->first()->id);

        $this->actingAs($this->secondaryUser);
        $secondaryClients = Client::all();
        $this->assertCount(1, $secondaryClients);
        $this->assertEquals($secondaryClient->id, $secondaryClients->first()->id);
    }

    /** @test */
    public function it_works_with_model_relationships()
    {
        $this->actAsUser();
        
        $client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);

        // Test the relationship
        $company = $client->company;
        
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals($this->testCompany->id, $company->id);
    }

    /** @test */
    public function it_maintains_isolation_in_complex_queries()
    {
        $this->actAsUser();
        
        // Create multiple clients for primary company
        $primaryClient1 = Client::create([
            'name' => 'Primary Client 1',
            'email' => 'primary1@test.com',
        ]);
        
        $primaryClient2 = Client::create([
            'name' => 'Primary Client 2',
            'email' => 'primary2@test.com',
        ]);

        // Switch to secondary company and create clients
        $this->actingAs($this->secondaryUser);
        
        Client::create([
            'name' => 'Secondary Client 1',
            'email' => 'secondary1@test.com',
        ]);
        
        Client::create([
            'name' => 'Secondary Client 2',
            'email' => 'secondary2@test.com',
        ]);

        // Complex query as primary user
        $this->actAsUser();
        
        $clients = Client::where('name', 'like', '%Client%')
                         ->orderBy('name')
                         ->get();
        
        $this->assertCount(2, $clients);
        $this->assertEquals($primaryClient1->id, $clients->first()->id);
        $this->assertEquals($primaryClient2->id, $clients->last()->id);
        
        foreach ($clients as $client) {
            $this->assertBelongsToTestCompany($client);
        }
    }
}