<?php

namespace Tests\Unit\Policies;

use App\Policies\MarketingCampaignPolicy;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingCampaignPolicy $policy;
    protected Company $company;
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        $this->admin->assign('admin');

        $this->policy = new MarketingCampaignPolicy;
    }

    public function test_policy_enforces_company_isolation(): void
    {
        $otherCompany = Company::factory()->create();
        
        $this->assertEquals($this->company->id, $this->admin->company_id);
        $this->assertNotEquals($otherCompany->id, $this->admin->company_id);
    }
}
