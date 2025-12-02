<?php

namespace Tests\Unit\Models\Ticket;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Client $client;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_can_create_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $ticket->company);
        $this->assertEquals($this->company->id, $ticket->company->id);
    }

    public function test_belongs_to_client(): void
    {
        $ticket = Ticket::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Client::class, $ticket->client);
        $this->assertEquals($this->client->id, $ticket->client->id);
    }

    public function test_belongs_to_assigned_user(): void
    {
        $ticket = Ticket::factory()->create([
            'assigned_to' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $ticket->assignedTo);
        $this->assertEquals($this->user->id, $ticket->assignedTo->id);
    }

    public function test_casts_datetime_fields(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now(),
            'due_date' => now()->addDays(3),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ticket->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ticket->due_date);
    }

    public function test_has_status_field(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'open',
        ]);

        $this->assertEquals('open', $ticket->status);
    }

    public function test_has_priority_field(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'priority' => 'high',
        ]);

        $this->assertEquals('high', $ticket->priority);
    }
}
