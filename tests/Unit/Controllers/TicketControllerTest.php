<?php

namespace Tests\Unit\Controllers;

use App\Domains\Ticket\Controllers\TicketController;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Domains\Ticket\Services\TicketQueryService;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Tests\RefreshesDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshesDatabase;

    protected TicketController $controller;

    protected TicketQueryService $queryService;

    protected Company $company;

    protected User $user;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->queryService = new TicketQueryService;
        $ticketRepository = app(\App\Domains\Ticket\Repositories\TicketRepository::class);
        $this->controller = new TicketController($this->queryService, $ticketRepository);
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

        $number = $method->invoke($this->controller, $this->company->id);

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

        $number = $method->invoke($this->controller, $this->company->id);

        $this->assertIsInt($number);
        $this->assertEquals(5001, $number);
    }

    public function test_get_filter_options_returns_correct_structure(): void
    {
        $options = $this->queryService->getFilterOptions($this->company->id);

        $this->assertIsArray($options);
        $this->assertArrayHasKey('statuses', $options);
        $this->assertArrayHasKey('priorities', $options);
        $this->assertArrayHasKey('clients', $options);
        $this->assertArrayHasKey('assignees', $options);
    }

    public function test_get_filter_options_includes_valid_statuses(): void
    {
        $options = $this->queryService->getFilterOptions($this->company->id);

        $this->assertIsArray($options['statuses']);
        $this->assertContains('new', $options['statuses']);
        $this->assertContains('open', $options['statuses']);
        $this->assertContains('closed', $options['statuses']);
    }

    public function test_get_filter_options_includes_valid_priorities(): void
    {
        $options = $this->queryService->getFilterOptions($this->company->id);

        $this->assertContains('Low', $options['priorities']);
        $this->assertContains('Medium', $options['priorities']);
        $this->assertContains('High', $options['priorities']);
        $this->assertContains('Critical', $options['priorities']);
    }



    public function test_index_request_includes_pagination(): void
    {
        Ticket::factory()->count(30)->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $request = Request::create('/tickets', 'GET', ['per_page' => 10]);
        $request->setUserResolver(fn() => $this->user);

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
        $request->setUserResolver(fn() => $this->user);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_create_request_provides_form_data(): void
    {
        $request = Request::create('/tickets/create', 'GET');
        $request->setUserResolver(fn() => $this->user);

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
        $request->setUserResolver(fn() => $this->user);

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
        $request->setUserResolver(fn() => $this->user);

        $response = $this->controller->create($request);

        $viewData = $response->getData();
        $this->assertContains('new', $viewData['statuses']);
        $this->assertContains('open', $viewData['statuses']);
        $this->assertContains('in_progress', $viewData['statuses']);
        $this->assertContains('pending', $viewData['statuses']);
        $this->assertContains('resolved', $viewData['statuses']);
        $this->assertContains('closed', $viewData['statuses']);
    }


}
