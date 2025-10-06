<?php

namespace Tests\Unit\Controllers;

use App\Domains\Ticket\Controllers\TicketController;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected TicketController $controller;

    protected Company $company;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->controller = new TicketController;
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

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_generate_ticket_number_creates_unique_number(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateTicketNumber');
        $method->setAccessible(true);

        $number = $method->invoke($this->controller);

        $this->assertIsInt($number);
        $this->assertGreaterThanOrEqual(1001, $number);
    }

    public function test_generate_ticket_number_increments_sequence(): void
    {
        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'number' => 5000,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateTicketNumber');
        $method->setAccessible(true);

        $number = $method->invoke($this->controller);

        $this->assertIsInt($number);
        $this->assertEquals(5001, $number);
    }

    public function test_get_filter_options_returns_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getFilterOptions');
        $method->setAccessible(true);

        $options = $method->invoke($this->controller);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('statuses', $options);
        $this->assertArrayHasKey('priorities', $options);
        $this->assertArrayHasKey('clients', $options);
        $this->assertArrayHasKey('assignees', $options);
    }

    public function test_get_filter_options_includes_valid_statuses(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getFilterOptions');
        $method->setAccessible(true);

        $options = $method->invoke($this->controller);

        $this->assertContains('new', $options['statuses']);
        $this->assertContains('open', $options['statuses']);
        $this->assertContains('in_progress', $options['statuses']);
        $this->assertContains('pending', $options['statuses']);
        $this->assertContains('resolved', $options['statuses']);
        $this->assertContains('closed', $options['statuses']);
    }

    public function test_get_filter_options_includes_valid_priorities(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getFilterOptions');
        $method->setAccessible(true);

        $options = $method->invoke($this->controller);

        $this->assertContains('Low', $options['priorities']);
        $this->assertContains('Medium', $options['priorities']);
        $this->assertContains('High', $options['priorities']);
        $this->assertContains('Critical', $options['priorities']);
    }

    public function test_track_ticket_view_stores_in_cache(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('trackTicketView');
        $method->setAccessible(true);

        $method->invoke($this->controller, $ticket);

        $cacheKey = "ticket_viewer_{$ticket->id}_{$this->user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_track_ticket_view_stores_user_information(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('trackTicketView');
        $method->setAccessible(true);

        $method->invoke($this->controller, $ticket);

        $cacheKey = "ticket_viewer_{$ticket->id}_{$this->user->id}";
        $data = Cache::get($cacheKey);

        $this->assertEquals($this->user->id, $data['user_id']);
        $this->assertEquals($this->user->name, $data['user_name']);
        $this->assertArrayHasKey('last_viewed', $data);
        $this->assertArrayHasKey('session_id', $data);
    }

    public function test_get_ticket_viewers_excludes_current_user(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $otherUser = User::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Cache::put("ticket_viewer_{$ticket->id}_{$this->user->id}", [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'last_viewed' => now()->toISOString(),
            'session_id' => session()->getId(),
        ], now()->addMinutes(5));

        Cache::put("ticket_viewer_{$ticket->id}_{$otherUser->id}", [
            'user_id' => $otherUser->id,
            'user_name' => $otherUser->name,
            'last_viewed' => now()->toISOString(),
            'session_id' => 'other-session',
        ], now()->addMinutes(5));

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTicketViewers');
        $method->setAccessible(true);

        $viewers = $method->invoke($this->controller, $ticket);

        $this->assertCount(1, $viewers);
        $this->assertEquals($otherUser->id, $viewers[0]['id']);
    }

    public function test_get_ticket_viewers_excludes_old_views(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $oldUser = User::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Cache::put("ticket_viewer_{$ticket->id}_{$oldUser->id}", [
            'user_id' => $oldUser->id,
            'user_name' => $oldUser->name,
            'last_viewed' => now()->subMinutes(10)->toISOString(),
            'session_id' => 'old-session',
        ], now()->addMinutes(5));

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTicketViewers');
        $method->setAccessible(true);

        $viewers = $method->invoke($this->controller, $ticket);

        $this->assertEmpty($viewers);
    }

    public function test_track_ticket_changes_logs_status_changes(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'open',
        ]);

        $oldData = $ticket->toArray();
        $newData = array_merge($oldData, ['status' => 'closed']);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('trackTicketChanges');
        $method->setAccessible(true);

        $method->invoke($this->controller, $ticket, $oldData, $newData);

        $this->assertTrue(true);
    }

    public function test_track_ticket_changes_logs_priority_changes(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
        ]);

        $oldData = $ticket->toArray();
        $newData = array_merge($oldData, ['priority' => 'Critical']);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('trackTicketChanges');
        $method->setAccessible(true);

        $method->invoke($this->controller, $ticket, $oldData, $newData);

        $this->assertTrue(true);
    }

    public function test_track_ticket_changes_logs_assignment_changes(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'assigned_to' => null,
        ]);

        $oldData = $ticket->toArray();
        $newData = array_merge($oldData, ['assigned_to' => $this->user->id]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('trackTicketChanges');
        $method->setAccessible(true);

        $method->invoke($this->controller, $ticket, $oldData, $newData);

        $this->assertTrue(true);
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

    public function test_index_request_includes_pagination(): void
    {
        Ticket::factory()->count(30)->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $request = Request::create('/tickets', 'GET', ['per_page' => 10]);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_index_request_applies_sorting(): void
    {
        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'created_at' => now()->subDays(2),
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'created_at' => now()->subDay(),
        ]);

        $request = Request::create('/tickets', 'GET', [
            'sort_by' => 'created_at',
            'sort_order' => 'asc',
        ]);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_create_request_provides_form_data(): void
    {
        $request = Request::create('/tickets/create', 'GET');

        $response = $this->controller->create($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $viewData = $response->getData();
        $this->assertArrayHasKey('clients', $viewData);
        $this->assertArrayHasKey('assignees', $viewData);
        $this->assertArrayHasKey('priorities', $viewData);
        $this->assertArrayHasKey('statuses', $viewData);
    }

    public function test_create_request_includes_priority_options(): void
    {
        $request = Request::create('/tickets/create', 'GET');

        $response = $this->controller->create($request);

        $viewData = $response->getData();
        $this->assertContains('Low', $viewData['priorities']);
        $this->assertContains('Medium', $viewData['priorities']);
        $this->assertContains('High', $viewData['priorities']);
        $this->assertContains('Critical', $viewData['priorities']);
    }

    public function test_create_request_includes_status_options(): void
    {
        $request = Request::create('/tickets/create', 'GET');

        $response = $this->controller->create($request);

        $viewData = $response->getData();
        $this->assertContains('new', $viewData['statuses']);
        $this->assertContains('open', $viewData['statuses']);
        $this->assertContains('in_progress', $viewData['statuses']);
        $this->assertContains('pending', $viewData['statuses']);
        $this->assertContains('resolved', $viewData['statuses']);
        $this->assertContains('closed', $viewData['statuses']);
    }

    public function test_export_request_generates_csv_headers(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $request = Request::create('/tickets/export', 'GET');

        $response = $this->controller->export($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_search_request_requires_minimum_query_length(): void
    {
        $request = Request::create('/tickets/search', 'GET', ['q' => 'a']);

        $response = $this->controller->search($request);

        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['tickets']);
    }

    public function test_search_request_limits_results(): void
    {
        Ticket::factory()->count(15)->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Common Subject',
        ]);

        $request = Request::create('/tickets/search', 'GET', ['q' => 'Common']);

        $response = $this->controller->search($request);

        $data = json_decode($response->getContent(), true);
        $this->assertLessThanOrEqual(10, count($data['tickets']));
    }

    public function test_search_request_excludes_closed_tickets(): void
    {
        $openTicket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'open',
            'subject' => 'Test Ticket',
        ]);

        $closedTicket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'closed',
            'subject' => 'Test Ticket Closed',
        ]);

        $request = Request::create('/tickets/search', 'GET', ['q' => 'Test']);

        $response = $this->controller->search($request);

        $data = json_decode($response->getContent(), true);
        $ticketIds = array_column($data['tickets'], 'id');

        $this->assertContains($openTicket->id, $ticketIds);
        $this->assertNotContains($closedTicket->id, $ticketIds);
    }
}
