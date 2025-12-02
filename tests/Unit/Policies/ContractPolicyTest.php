<?php

namespace Tests\Unit\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Models\User;
use App\Policies\ContractPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ContractPolicy $policy;
    protected Company $company;
    protected User $user;
    protected Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ContractPolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->contract = Contract::factory()->create([
            'company_id' => $this->company->id,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_contracts_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Contract::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_contract_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->contract);

        $this->assertTrue($this->policy->view($this->user, $this->contract));
    }

    public function test_user_cannot_view_contract_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherContract = Contract::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherContract));
    }

    public function test_user_can_create_contract_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Contract::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_contract_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->contract);

        $this->assertTrue($this->policy->update($this->user, $this->contract));
    }

    public function test_user_can_delete_contract_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->contract);

        $this->assertTrue($this->policy->delete($this->user, $this->contract));
    }

    public function test_enforces_company_isolation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherContract = Contract::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();

        $this->assertFalse($this->policy->view($this->user, $otherContract));
        $this->assertFalse($this->policy->update($this->user, $otherContract));
        $this->assertFalse($this->policy->delete($this->user, $otherContract));
    }
}
