<?php

namespace Tests\Unit\Controllers;

use App\Domains\Ticket\Controllers\TicketSearchController;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Tests\RefreshesDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TicketSearchControllerTest extends TestCase
{
    use RefreshesDatabase;

    protected TicketSearchController $controller;

    protected Company $company;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->controller = new TicketSearchController;
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
        $this->assertEquals($otherUser->id, $viewers[0]['user_id']);
    }

    public function test_get_ticket_viewers_returns_all_active_viewers(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $anotherUser = User::factory()->create([
            'company_id' => $this->user->company_id,
        ]);

        Cache::put("ticket_viewer_{$ticket->id}_{$anotherUser->id}", [
            'user_id' => $anotherUser->id,
            'user_name' => $anotherUser->name,
            'last_viewed' => now()->toISOString(),
            'session_id' => 'another-session',
        ], now()->addMinutes(5));

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTicketViewers');
        $method->setAccessible(true);

        $viewers = $method->invoke($this->controller, $ticket);

        $this->assertCount(1, $viewers);
        $this->assertEquals($anotherUser->id, $viewers[0]['user_id']);
    }

    public function test_search_request_requires_minimum_query_length(): void
    {
        $request = \Illuminate\Http\Request::create('/tickets/search', 'GET', ['q' => 'a']);
        $request->setUserResolver(fn() => $this->user);

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

        $request = \Illuminate\Http\Request::create('/tickets/search', 'GET', ['q' => 'Common']);
        $request->setUserResolver(fn() => $this->user);

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

        $request = \Illuminate\Http\Request::create('/tickets/search', 'GET', ['q' => 'Test']);
        $request->setUserResolver(fn() => $this->user);

        $response = $this->controller->search($request);

        $data = json_decode($response->getContent(), true);
        $ticketIds = array_column($data['tickets'], 'id');

        $this->assertContains($openTicket->id, $ticketIds);
        $this->assertNotContains($closedTicket->id, $ticketIds);
    }
}
