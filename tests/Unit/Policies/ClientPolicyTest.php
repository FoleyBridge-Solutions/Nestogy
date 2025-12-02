<?php

namespace Tests\Unit\Policies;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Policies\ClientPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ClientPolicy $policy;
    protected Company $company;
    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ClientPolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Set up Bouncer permissions
        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_clients_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Client::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_cannot_view_any_clients_without_permission(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_client_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->client);

        $this->assertTrue($this->policy->view($this->user, $this->client));
    }

    public function test_user_cannot_view_client_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherClient));
    }

    public function test_user_can_create_client_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Client::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_cannot_create_client_without_permission(): void
    {
        $this->assertFalse($this->policy->create($this->user));
    }

    public function test_user_can_update_client_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->client);

        $this->assertTrue($this->policy->update($this->user, $this->client));
    }

    public function test_user_cannot_update_client_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', Client::class);

        $this->assertFalse($this->policy->update($this->user, $otherClient));
    }

    public function test_user_can_delete_client_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->client);

        $this->assertTrue($this->policy->delete($this->user, $this->client));
    }

    public function test_user_cannot_delete_client_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->delete($this->user, $otherClient));
    }
}
