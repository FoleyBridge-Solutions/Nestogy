<?php

namespace Tests\Unit\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Ticket\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TicketPolicy $policy;
    protected Company $company;
    protected User $user;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TicketPolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_tickets_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Ticket::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_ticket_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->ticket);

        $this->assertTrue($this->policy->view($this->user, $this->ticket));
    }

    public function test_user_cannot_view_ticket_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherTicket = Ticket::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherTicket));
    }

    public function test_user_can_create_ticket_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Ticket::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_ticket_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->ticket);

        $this->assertTrue($this->policy->update($this->user, $this->ticket));
    }

    public function test_user_can_delete_ticket_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->ticket);

        $this->assertTrue($this->policy->delete($this->user, $this->ticket));
    }

    public function test_enforces_company_isolation_on_all_actions(): void
    {
        $otherCompany = Company::factory()->create();
        $otherTicket = Ticket::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Even with permissions, user cannot access other company data
        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();

        $this->assertFalse($this->policy->view($this->user, $otherTicket));
        $this->assertFalse($this->policy->update($this->user, $otherTicket));
        $this->assertFalse($this->policy->delete($this->user, $otherTicket));
    }
}
