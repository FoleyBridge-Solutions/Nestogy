<?php

namespace Tests\Unit\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Services\ResolutionEstimateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolutionEstimateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ResolutionEstimateService $service;
    protected User $user;
    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        Carbon::setTestNow('2025-01-15 10:00:00');
        
        $this->service = new ResolutionEstimateService();
        
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calculate_estimated_resolution_returns_carbon_instance()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Medium',
            'category' => 'General',
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_critical_priority_uses_4_hour_base()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->lessThanOrEqualTo(now()->addHours(6)));
    }

    public function test_high_priority_uses_8_hour_base()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'High',
            'category' => 'General',
            'assigned_to' => $this->user->id,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(7)));
    }

    public function test_medium_priority_uses_24_hour_base()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Medium',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(20)));
    }

    public function test_low_priority_uses_48_hour_base()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(40)));
    }

    public function test_workload_factor_increases_with_more_active_tickets()
    {
        $tech = User::factory()->create(['company_id' => $this->company->id]);

        Ticket::factory()->count(12)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $tech->id,
            'status' => 'In Progress',
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'General',
            'assigned_to' => $tech->id,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(6)));
    }

    public function test_no_assigned_user_uses_higher_workload_factor()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(4)));
    }

    public function test_complex_category_increases_estimate()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'Network',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(5)));
    }

    public function test_queue_factor_increases_with_client_pending_tickets()
    {
        Ticket::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'Open',
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(6)));
    }

    public function test_adjust_for_business_hours_skips_weekends()
    {
        Carbon::setTestNow('2025-01-17 10:00:00');

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertFalse($result->isWeekend());
    }

    public function test_adjust_for_business_hours_sets_start_time_if_before_9am()
    {
        Carbon::setTestNow('2025-01-15 06:00:00');

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->hour >= 9);
    }

    public function test_adjust_for_business_hours_moves_to_next_day_if_after_5pm()
    {
        Carbon::setTestNow('2025-01-15 16:00:00');

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(Carbon::parse('2025-01-15 17:00:00')));
    }

    public function test_update_estimate_for_ticket_updates_estimated_resolution_at()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Medium',
            'category' => 'General',
            'status' => 'Open',
        ]);

        $this->service->updateEstimateForTicket($ticket);

        $ticket->refresh();
        $this->assertNotNull($ticket->estimated_resolution_at);
    }

    public function test_update_estimate_for_ticket_does_not_update_resolved_tickets()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Medium',
            'status' => 'Resolved',
            'estimated_resolution_at' => null,
        ]);

        $this->service->updateEstimateForTicket($ticket);

        $ticket->refresh();
        $this->assertNull($ticket->estimated_resolution_at);
    }

    public function test_update_estimate_for_ticket_does_not_update_closed_tickets()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Medium',
            'status' => 'Closed',
            'estimated_resolution_at' => null,
        ]);

        $this->service->updateEstimateForTicket($ticket);

        $ticket->refresh();
        $this->assertNull($ticket->estimated_resolution_at);
    }

    public function test_recalculate_for_technician_updates_all_assigned_tickets()
    {
        $tech = User::factory()->create(['company_id' => $this->company->id]);

        $tickets = Ticket::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $tech->id,
            'status' => 'In Progress',
            'estimated_resolution_at' => null,
        ]);

        $this->service->recalculateForTechnician($tech->id);

        foreach ($tickets as $ticket) {
            $ticket->refresh();
            $this->assertNotNull($ticket->estimated_resolution_at);
        }
    }

    public function test_recalculate_for_technician_skips_resolved_tickets()
    {
        $tech = User::factory()->create(['company_id' => $this->company->id]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $tech->id,
            'status' => 'Resolved',
            'estimated_resolution_at' => null,
        ]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $tech->id,
            'status' => 'Open',
            'estimated_resolution_at' => null,
        ]);

        $this->service->recalculateForTechnician($tech->id);

        $resolvedTicket = Ticket::where('assigned_to', $tech->id)->where('status', 'Resolved')->first();
        $openTicket = Ticket::where('assigned_to', $tech->id)->where('status', 'Open')->first();

        $this->assertNull($resolvedTicket->estimated_resolution_at);
        $this->assertNotNull($openTicket->estimated_resolution_at);
    }

    public function test_get_average_resolution_time_returns_hours()
    {
        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_at' => now()->subHours(10),
            'resolved_at' => now()->subHours(2),
        ]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_at' => now()->subHours(6),
            'resolved_at' => now()->subHours(2),
        ]);

        $result = $this->service->getAverageResolutionTime();

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_get_average_resolution_time_filters_by_priority()
    {
        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'created_at' => now()->subHours(10),
            'resolved_at' => now()->subHours(2),
        ]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
            'created_at' => now()->subHours(100),
            'resolved_at' => now()->subHours(2),
        ]);

        $result = $this->service->getAverageResolutionTime(['priority' => 'Critical']);

        $this->assertLessThan(10, $result);
    }

    public function test_get_average_resolution_time_filters_by_category()
    {
        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category' => 'Network',
            'created_at' => now()->subHours(10),
            'resolved_at' => now()->subHours(2),
        ]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category' => 'General',
            'created_at' => now()->subHours(100),
            'resolved_at' => now()->subHours(2),
        ]);

        $result = $this->service->getAverageResolutionTime(['category' => 'Network']);

        $this->assertLessThan(10, $result);
    }

    public function test_get_average_resolution_time_filters_by_assigned_to()
    {
        $tech1 = User::factory()->create(['company_id' => $this->company->id]);
        $tech2 = User::factory()->create(['company_id' => $this->company->id]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $tech1->id,
            'created_at' => now()->subHours(10),
            'resolved_at' => now()->subHours(2),
        ]);

        Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $tech2->id,
            'created_at' => now()->subHours(100),
            'resolved_at' => now()->subHours(2),
        ]);

        $result = $this->service->getAverageResolutionTime(['assigned_to' => $tech1->id]);

        $this->assertLessThan(10, $result);
    }

    public function test_get_average_resolution_time_returns_zero_for_no_tickets()
    {
        $result = $this->service->getAverageResolutionTime();

        $this->assertEquals(0, $result);
    }

    public function test_server_category_increases_factor()
    {
        $serverTicket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'Server',
            'assigned_to' => null,
        ]);

        $generalTicket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'General',
            'assigned_to' => null,
        ]);

        $serverEstimate = $this->service->calculateEstimatedResolution($serverTicket);
        $generalEstimate = $this->service->calculateEstimatedResolution($generalTicket);

        $this->assertTrue($serverEstimate->greaterThan($generalEstimate));
    }

    public function test_security_category_increases_factor()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'Security',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(4)));
    }

    public function test_database_category_increases_factor()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
            'category' => 'Database',
            'assigned_to' => null,
        ]);

        $result = $this->service->calculateEstimatedResolution($ticket);

        $this->assertTrue($result->greaterThan(now()->addHours(4)));
    }
}
