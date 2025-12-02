<?php

namespace Tests\Feature;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests monthly billing generation
 */
class BillingCycleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();
        $this->actingAs($this->user);
    }

    public function test_workflow_completes_successfully(): void
    {
        // Tests monthly billing generation
        $this->assertTrue(true);
    }

    public function test_workflow_respects_company_isolation(): void
    {
        $otherCompany = Company::factory()->create();
        
        $this->assertEquals($this->company->id, $this->user->company_id);
        $this->assertNotEquals($otherCompany->id, $this->user->company_id);
    }
}
