<?php

namespace Tests\Unit\Controllers;

use App\Domains\Ticket\Controllers\TicketQueueController;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TicketQueueControllerTest extends TestCase
{
    use RefreshesDatabase;

    protected TicketQueueController $controller;

    protected Company $company;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->controller = new TicketQueueController;
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'status' => true,
        ]);
        
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_get_today_time_statistics_returns_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTodayTimeStatistics');
        $method->setAccessible(true);

        $stats = $method->invoke($this->controller, $this->user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_hours', $stats);
        $this->assertArrayHasKey('billable_hours', $stats);
        $this->assertArrayHasKey('entries_count', $stats);
    }

    public function test_get_today_time_statistics_calculates_total_hours(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $entry1 = TicketTimeEntry::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'work_date' => today(),
            'hours_worked' => 2.5,
            'billable' => true,
        ]);

        $entry2 = TicketTimeEntry::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'work_date' => today(),
            'hours_worked' => 1.5,
            'billable' => false,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTodayTimeStatistics');
        $method->setAccessible(true);

        $stats = $method->invoke($this->controller, $this->user);

        $this->assertIsNumeric($stats['total_hours']);
        $this->assertIsNumeric($stats['billable_hours']);
        $this->assertIsInt($stats['entries_count']);
        $this->assertGreaterThan(0, $stats['entries_count']);
    }
}
