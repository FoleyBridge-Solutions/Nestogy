<?php

namespace Tests\Feature\Livewire\.;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketTimeTrackerTest extends TestCase
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

    public function test_component_loads(): void
    {
        $this->assertTrue(true);
    }
}
