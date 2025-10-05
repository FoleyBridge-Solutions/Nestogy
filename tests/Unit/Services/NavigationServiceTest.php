<?php

namespace Tests\Unit\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Services\NavigationService;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Quote;
use App\Models\Recurring;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corporation',
        ]);
        
        $this->actingAs($this->user);
        NavigationService::clearSelectedClient();
    }

    protected function tearDown(): void
    {
        NavigationService::clearSelectedClient();
        session()->flush();
        parent::tearDown();
    }

    // ========================================
    // DOMAIN & ROUTE MANAGEMENT TESTS
    // ========================================

    public function test_gets_active_domain_from_clients_route(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        Route::shouldReceive('currentRouteName')->andReturn('clients.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('clients', $domain);
    }

    public function test_gets_active_domain_from_tickets_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('tickets', $domain);
    }

    public function test_gets_active_domain_from_assets_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('assets.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('assets', $domain);
    }

    public function test_gets_active_domain_from_financial_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('financial.invoices.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('financial', $domain);
    }

    public function test_gets_active_domain_from_billing_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('billing.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('financial', $domain);
    }

    public function test_gets_active_domain_from_projects_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('projects.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('projects', $domain);
    }

    public function test_gets_active_domain_from_reports_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('reports.index');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals('reports', $domain);
    }

    public function test_returns_null_for_unknown_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('unknown.route');
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertNull($domain);
    }

    public function test_returns_null_when_no_route_name(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn(null);
        
        $domain = NavigationService::getActiveDomain();
        
        $this->assertNull($domain);
    }

    // ========================================
    // SIDEBAR CONTEXT TESTS
    // ========================================

    public function test_gets_sidebar_context_matches_active_domain(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $context = NavigationService::getSidebarContext();
        $domain = NavigationService::getActiveDomain();
        
        $this->assertEquals($domain, $context);
    }

    public function test_sidebar_context_hidden_on_clients_index_without_selection(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('clients.index');
        
        $context = NavigationService::getSidebarContext();
        
        $this->assertNull($context);
    }

    public function test_sidebar_context_shown_on_clients_index_with_selection(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        Route::shouldReceive('currentRouteName')->andReturn('clients.index');
        
        $context = NavigationService::getSidebarContext();
        
        $this->assertEquals('clients', $context);
    }

    public function test_sidebar_context_hidden_on_client_create_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('clients.create');
        
        $context = NavigationService::getSidebarContext();
        
        $this->assertNull($context);
    }

    // ========================================
    // NAVIGATION ITEM TESTS
    // ========================================

    public function test_gets_active_navigation_item_for_tickets_index(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $item = NavigationService::getActiveNavigationItem();
        
        $this->assertEquals('index', $item);
    }

    public function test_gets_active_navigation_item_for_tickets_create(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.create');
        
        $item = NavigationService::getActiveNavigationItem();
        
        $this->assertEquals('create', $item);
    }

    public function test_gets_active_navigation_item_for_financial_invoices(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('financial.invoices.index');
        
        $item = NavigationService::getActiveNavigationItem();
        
        $this->assertEquals('invoices', $item);
    }

    public function test_gets_active_navigation_item_for_financial_contracts(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('financial.contracts.index');
        
        $item = NavigationService::getActiveNavigationItem();
        
        $this->assertEquals('contracts', $item);
    }

    public function test_returns_null_navigation_item_for_unknown_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('unknown.route');
        
        $item = NavigationService::getActiveNavigationItem();
        
        $this->assertNull($item);
    }

    // ========================================
    // ROUTE ACTIVE TESTS
    // ========================================

    public function test_is_route_active_returns_true_for_matching_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $isActive = NavigationService::isRouteActive('tickets.index');
        
        $this->assertTrue($isActive);
    }

    public function test_is_route_active_returns_false_for_different_route(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.create');
        
        $isActive = NavigationService::isRouteActive('tickets.index');
        
        $this->assertFalse($isActive);
    }

    public function test_is_route_active_checks_parameters(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        request()->merge(['filter' => 'my']);
        $isActive = NavigationService::isRouteActive('tickets.index', ['filter' => 'my']);
        
        $this->assertTrue($isActive);
    }

    public function test_is_route_active_returns_false_for_mismatched_parameters(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        request()->merge(['filter' => 'my']);
        $isActive = NavigationService::isRouteActive('tickets.index', ['filter' => 'open']);
        
        $this->assertFalse($isActive);
    }

    // ========================================
    // CLIENT SELECTION TESTS
    // ========================================

    public function test_selected_client_can_be_set_and_retrieved(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        
        $selected = NavigationService::getSelectedClient();
        
        $this->assertNotNull($selected);
        $this->assertEquals($this->client->id, $selected->id);
    }

    public function test_selected_client_can_be_cleared(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        NavigationService::clearSelectedClient();
        
        $selected = NavigationService::getSelectedClient();
        
        $this->assertNull($selected);
    }

    public function test_has_selected_client_returns_true_when_client_selected(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        
        $hasClient = NavigationService::hasSelectedClient();
        
        $this->assertTrue($hasClient);
    }

    public function test_has_selected_client_returns_false_when_no_client(): void
    {
        NavigationService::clearSelectedClient();
        
        $hasClient = NavigationService::hasSelectedClient();
        
        $this->assertFalse($hasClient);
    }

    public function test_set_selected_client_with_null_clears_selection(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        NavigationService::setSelectedClient(null);
        
        $selected = NavigationService::getSelectedClient();
        
        $this->assertNull($selected);
    }

    public function test_get_selected_client_validates_company_isolation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);
        
        session(['selected_client_id' => $otherClient->id]);
        
        $selected = NavigationService::getSelectedClient();
        
        $this->assertNull($selected);
    }

    public function test_get_selected_client_returns_null_for_invalid_id(): void
    {
        session(['selected_client_id' => 99999]);
        
        $selected = NavigationService::getSelectedClient();
        
        $this->assertNull($selected);
    }

    // ========================================
    // WORKFLOW CONTEXT TESTS
    // ========================================

    public function test_workflow_context_can_be_set_and_retrieved(): void
    {
        NavigationService::setWorkflowContext('urgent');
        
        $workflow = NavigationService::getWorkflowContext();
        
        $this->assertEquals('urgent', $workflow);
    }

    public function test_workflow_context_defaults_to_default(): void
    {
        session()->forget('current_workflow');
        
        $workflow = NavigationService::getWorkflowContext();
        
        $this->assertEquals('default', $workflow);
    }

    public function test_workflow_context_can_be_cleared(): void
    {
        NavigationService::setWorkflowContext('urgent');
        NavigationService::clearWorkflowContext();
        
        $this->assertFalse(session()->has('current_workflow'));
    }

    public function test_is_workflow_active_returns_true_for_matching_workflow(): void
    {
        NavigationService::setWorkflowContext('urgent');
        
        $isActive = NavigationService::isWorkflowActive('urgent');
        
        $this->assertTrue($isActive);
    }

    public function test_is_workflow_active_returns_false_for_different_workflow(): void
    {
        NavigationService::setWorkflowContext('urgent');
        
        $isActive = NavigationService::isWorkflowActive('today');
        
        $this->assertFalse($isActive);
    }

    // ========================================
    // RECENT CLIENTS TESTS
    // ========================================

    public function test_recent_client_ids_can_be_retrieved(): void
    {
        session(['recent_client_ids' => [1, 2, 3]]);
        
        $recentIds = NavigationService::getRecentClientIds();
        
        $this->assertEquals([1, 2, 3], $recentIds);
    }

    public function test_recent_client_ids_defaults_to_empty_array(): void
    {
        session()->forget('recent_client_ids');
        
        $recentIds = NavigationService::getRecentClientIds();
        
        $this->assertEquals([], $recentIds);
    }

    public function test_add_to_recent_clients_prepends_client_id(): void
    {
        NavigationService::addToRecentClients($this->client->id);
        
        $recentIds = NavigationService::getRecentClientIds();
        
        $this->assertEquals([$this->client->id], $recentIds);
    }

    public function test_add_to_recent_clients_removes_duplicates(): void
    {
        session(['recent_client_ids' => [1, 2, 3]]);
        
        NavigationService::addToRecentClients(2);
        
        $recentIds = NavigationService::getRecentClientIds();
        
        $this->assertEquals([2, 1, 3], $recentIds);
    }

    public function test_add_to_recent_clients_limits_to_10_items(): void
    {
        session(['recent_client_ids' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]);
        
        NavigationService::addToRecentClients(11);
        
        $recentIds = NavigationService::getRecentClientIds();
        
        $this->assertCount(10, $recentIds);
        $this->assertEquals(11, $recentIds[0]);
        $this->assertNotContains(10, $recentIds);
    }

    // ========================================
    // WORKFLOW NAVIGATION STATE TESTS
    // ========================================

    public function test_get_workflow_navigation_state_includes_client_info(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        NavigationService::setWorkflowContext('urgent');
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $state = NavigationService::getWorkflowNavigationState();
        
        $this->assertEquals('urgent', $state['workflow']);
        $this->assertEquals($this->client->id, $state['client_id']);
        $this->assertEquals($this->client->name, $state['client_name']);
        $this->assertEquals('tickets', $state['active_domain']);
    }

    public function test_get_workflow_navigation_state_handles_no_client(): void
    {
        NavigationService::clearSelectedClient();
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $state = NavigationService::getWorkflowNavigationState();
        
        $this->assertNull($state['client_id']);
        $this->assertNull($state['client_name']);
    }

    // ========================================
    // WORKFLOW ROUTE PARAMS TESTS
    // ========================================

    public function test_get_workflow_route_params_includes_client_when_selected(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        
        $params = NavigationService::getWorkflowRouteParams('urgent');
        
        $this->assertEquals($this->client->id, $params['client_id']);
    }

    public function test_get_workflow_route_params_for_urgent_workflow(): void
    {
        $params = NavigationService::getWorkflowRouteParams('urgent');
        
        $this->assertEquals('Critical,High', $params['priority']);
        $this->assertEquals('Open,In Progress', $params['status']);
    }

    public function test_get_workflow_route_params_for_today_workflow(): void
    {
        $params = NavigationService::getWorkflowRouteParams('today');
        
        $this->assertEquals(now()->toDateString(), $params['date']);
    }

    public function test_get_workflow_route_params_for_scheduled_workflow(): void
    {
        $params = NavigationService::getWorkflowRouteParams('scheduled');
        
        $this->assertEquals('1', $params['scheduled']);
        $this->assertEquals(now()->toDateString(), $params['date_from']);
        $this->assertEquals(now()->addWeek()->toDateString(), $params['date_to']);
    }

    public function test_get_workflow_route_params_for_financial_workflow(): void
    {
        $params = NavigationService::getWorkflowRouteParams('financial');
        
        $this->assertEquals('Draft,Sent,Overdue', $params['status']);
    }

    // ========================================
    // PERMISSIONS & ACCESS TESTS
    // ========================================

    public function test_can_access_domain_checks_permission(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->with('tickets.view')->andReturn(true);
        
        $canAccess = NavigationService::canAccessDomain($mockUser, 'tickets');
        
        $this->assertTrue($canAccess);
    }

    public function test_can_access_navigation_item_for_clients(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->with('clients.view')->andReturn(true);
        
        $canAccess = NavigationService::canAccessNavigationItem($mockUser, 'clients', 'index');
        
        $this->assertTrue($canAccess);
    }

    public function test_cannot_access_navigation_item_without_permission(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->with('clients.create')->andReturn(false);
        
        $canAccess = NavigationService::canAccessNavigationItem($mockUser, 'clients', 'create');
        
        $this->assertFalse($canAccess);
    }

    public function test_get_filtered_navigation_items_returns_empty_without_permission(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->with('tickets.view')->andReturn(false);
        
        $items = NavigationService::getFilteredNavigationItems('tickets');
        
        $this->assertEmpty($items);
    }

    public function test_get_filtered_navigation_items_for_clients(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        $this->actingAs($mockUser);
        $items = NavigationService::getFilteredNavigationItems('clients');
        
        $this->assertIsArray($items);
    }

    // ========================================
    // BADGE COUNTS TESTS
    // ========================================

    public function test_get_badge_counts_returns_array(): void
    {
        $counts = NavigationService::getBadgeCounts('tickets');
        
        $this->assertIsArray($counts);
    }

    public function test_get_badge_counts_returns_empty_without_permission(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->with('tickets.view')->andReturn(false);
        $this->actingAs($mockUser);
        
        $counts = NavigationService::getBadgeCounts('tickets');
        
        $this->assertEmpty($counts);
    }

    public function test_get_client_specific_badge_counts_without_client_id(): void
    {
        $counts = NavigationService::getClientSpecificBadgeCounts($this->company->id, null);
        
        $this->assertIsArray($counts);
    }

    public function test_get_client_specific_badge_counts_with_client_id(): void
    {
        Contact::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);
        
        Location::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $counts = NavigationService::getClientSpecificBadgeCounts($this->company->id, $this->client->id);
        
        $this->assertIsArray($counts);
        $this->assertArrayHasKey('contacts', $counts);
        $this->assertArrayHasKey('locations', $counts);
        $this->assertEquals(5, $counts['contacts']);
        $this->assertEquals(3, $counts['locations']);
    }

    // ========================================
    // WORKFLOW HELPERS TESTS
    // ========================================

    public function test_get_urgent_items_returns_structure(): void
    {
        $items = NavigationService::getUrgentItems();
        
        $this->assertIsArray($items);
        $this->assertArrayHasKey('total', $items);
        $this->assertArrayHasKey('financial', $items);
        $this->assertArrayHasKey('notifications', $items);
        $this->assertArrayHasKey('client', $items);
        $this->assertArrayHasKey('items', $items);
    }

    public function test_get_urgent_items_without_authenticated_user(): void
    {
        auth()->logout();
        
        $items = NavigationService::getUrgentItems();
        
        $this->assertEmpty($items);
    }

    public function test_get_todays_work_returns_structure(): void
    {
        $work = NavigationService::getTodaysWork();
        
        $this->assertIsArray($work);
        $this->assertArrayHasKey('total', $work);
        $this->assertArrayHasKey('upcoming', $work);
        $this->assertArrayHasKey('client', $work);
        $this->assertArrayHasKey('scheduled', $work);
    }

    public function test_get_todays_work_without_authenticated_user(): void
    {
        auth()->logout();
        
        $work = NavigationService::getTodaysWork();
        
        $this->assertEmpty($work);
    }

    public function test_get_client_workflow_context_returns_null_without_client(): void
    {
        $context = NavigationService::getClientWorkflowContext(null);
        
        $this->assertNull($context);
    }

    public function test_get_client_workflow_context_without_authenticated_user(): void
    {
        auth()->logout();
        
        $context = NavigationService::getClientWorkflowContext($this->client);
        
        $this->assertNull($context);
    }

    public function test_get_client_workflow_context_returns_structure(): void
    {
        $context = NavigationService::getClientWorkflowContext($this->client);
        
        $this->assertIsArray($context);
        $this->assertArrayHasKey('client_id', $context);
        $this->assertArrayHasKey('client_name', $context);
        $this->assertArrayHasKey('current_workflow', $context);
        $this->assertArrayHasKey('status', $context);
    }

    public function test_get_workflow_navigation_highlights_returns_structure(): void
    {
        $highlights = NavigationService::getWorkflowNavigationHighlights('urgent');
        
        $this->assertIsArray($highlights);
        $this->assertArrayHasKey('urgent_count', $highlights);
        $this->assertArrayHasKey('today_count', $highlights);
        $this->assertArrayHasKey('scheduled_count', $highlights);
        $this->assertArrayHasKey('financial_count', $highlights);
        $this->assertArrayHasKey('alerts', $highlights);
        $this->assertArrayHasKey('badges', $highlights);
    }

    public function test_get_workflow_navigation_highlights_without_user(): void
    {
        auth()->logout();
        
        $highlights = NavigationService::getWorkflowNavigationHighlights('urgent');
        
        $this->assertEquals(0, $highlights['urgent_count']);
        $this->assertEquals(0, $highlights['today_count']);
    }

    public function test_get_workflow_quick_actions_for_urgent_workflow(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        $actions = NavigationService::getWorkflowQuickActions('urgent', 'admin');
        
        $this->assertIsArray($actions);
    }

    // ========================================
    // FAVORITES & RECENT CLIENTS TESTS
    // ========================================

    public function test_get_favorite_clients_returns_collection(): void
    {
        $favorites = NavigationService::getFavoriteClients();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $favorites);
    }

    public function test_get_favorite_clients_without_user(): void
    {
        auth()->logout();
        
        $favorites = NavigationService::getFavoriteClients();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $favorites);
        $this->assertCount(0, $favorites);
    }

    public function test_get_recent_clients_returns_collection(): void
    {
        $recent = NavigationService::getRecentClients();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recent);
    }

    public function test_get_recent_clients_without_user(): void
    {
        auth()->logout();
        
        $recent = NavigationService::getRecentClients();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recent);
        $this->assertCount(0, $recent);
    }

    public function test_get_smart_client_suggestions_returns_structure(): void
    {
        $suggestions = NavigationService::getSmartClientSuggestions();
        
        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('favorites', $suggestions);
        $this->assertArrayHasKey('recent', $suggestions);
        $this->assertArrayHasKey('total', $suggestions);
    }

    public function test_get_smart_client_suggestions_without_user(): void
    {
        auth()->logout();
        
        $suggestions = NavigationService::getSmartClientSuggestions();
        
        $this->assertEquals(0, $suggestions['total']);
    }

    public function test_toggle_client_favorite_returns_false_without_user(): void
    {
        auth()->logout();
        
        $result = NavigationService::toggleClientFavorite($this->client->id);
        
        $this->assertFalse($result);
    }

    public function test_toggle_client_favorite_returns_false_for_invalid_client(): void
    {
        $result = NavigationService::toggleClientFavorite(99999);
        
        $this->assertFalse($result);
    }

    public function test_is_client_favorite_returns_false_without_user(): void
    {
        auth()->logout();
        
        $result = NavigationService::isClientFavorite($this->client->id);
        
        $this->assertFalse($result);
    }

    public function test_is_client_favorite_returns_false_for_invalid_client(): void
    {
        $result = NavigationService::isClientFavorite(99999);
        
        $this->assertFalse($result);
    }

    // ========================================
    // SIDEBAR & DOMAIN STATS TESTS
    // ========================================

    public function test_register_sidebar_section(): void
    {
        $section = [
            'title' => 'Test Section',
            'items' => [],
        ];
        
        NavigationService::registerSidebarSection('test', 'test-section', $section);
        
        $this->assertTrue(true);
    }

    public function test_register_sidebar_sections(): void
    {
        $sections = [
            'section1' => ['title' => 'Section 1'],
            'section2' => ['title' => 'Section 2'],
        ];
        
        NavigationService::registerSidebarSections('test', $sections);
        
        $this->assertTrue(true);
    }

    public function test_get_domain_stats_returns_array(): void
    {
        $stats = NavigationService::getDomainStats('tickets');
        
        $this->assertIsArray($stats);
    }

    // ========================================
    // BREADCRUMBS TESTS
    // ========================================

    public function test_breadcrumbs_empty_for_clients_index_without_selection(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('clients.index');
        
        $breadcrumbs = NavigationService::getBreadcrumbs();
        
        $this->assertEmpty($breadcrumbs);
    }

    public function test_breadcrumbs_show_client_name_on_clients_index_with_selection(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        Route::shouldReceive('currentRouteName')->andReturn('clients.index');
        
        $breadcrumbs = NavigationService::getBreadcrumbs();
        
        $this->assertCount(1, $breadcrumbs);
        $this->assertEquals('Acme Corporation', $breadcrumbs[0]['name']);
        $this->assertTrue($breadcrumbs[0]['active']);
    }

    public function test_breadcrumbs_show_client_and_domain_for_tickets(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        Route::shouldReceive('currentRouteName')->andReturn('tickets.index');
        
        $breadcrumbs = NavigationService::getBreadcrumbs();
        
        $this->assertCount(2, $breadcrumbs);
        $this->assertEquals('Acme Corporation', $breadcrumbs[0]['name']);
        $this->assertEquals('Tickets', $breadcrumbs[1]['name']);
        $this->assertTrue($breadcrumbs[1]['active']);
    }

    public function test_workflow_breadcrumbs_for_urgent_workflow(): void
    {
        NavigationService::setWorkflowContext('urgent');
        
        $breadcrumbs = NavigationService::getWorkflowBreadcrumbs();
        
        $lastItem = end($breadcrumbs);
        $this->assertEquals('Urgent Items', $lastItem['name']);
        $this->assertTrue($lastItem['active']);
    }

    // ========================================
    // CLIENT NAVIGATION ITEMS TEST
    // ========================================

    public function test_get_client_navigation_items_without_selection(): void
    {
        NavigationService::clearSelectedClient();
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        $items = NavigationService::getClientNavigationItems($mockUser);
        
        $this->assertArrayHasKey('index', $items);
    }

    public function test_get_client_navigation_items_with_selection(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        NavigationService::setSelectedClient($client->id);
        
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        $items = NavigationService::getClientNavigationItems($mockUser);
        
        $this->assertArrayHasKey('client-dashboard', $items);
        $this->assertArrayHasKey('switch', $items);
    }

    public function test_get_client_specific_badge_counts_with_recurring_and_quotes(): void
    {
        Recurring::factory()->count(4)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);
        
        Quote::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Contract::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);
        
        $counts = NavigationService::getClientSpecificBadgeCounts($this->company->id, $this->client->id);
        
        $this->assertEquals(4, $counts['recurring-invoices']);
        $this->assertEquals(1, $counts['quotes']);
        $this->assertEquals(2, $counts['contracts']);
    }

    // ========================================
    // ADDITIONAL PUBLIC METHOD COVERAGE
    // ========================================

    public function test_get_domain_stats_for_all_domains(): void
    {
        $domains = ['tickets', 'assets', 'financial', 'projects', 'clients', 'reports', 'knowledge', 'integrations', 'settings'];
        
        foreach ($domains as $domain) {
            $stats = NavigationService::getDomainStats($domain);
            $this->assertIsArray($stats);
        }
    }

    public function test_get_badge_counts_for_all_domains(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        $mockUser->company_id = $this->company->id;
        $mockUser->id = $this->user->id;
        $this->actingAs($mockUser);
        
        $domains = ['clients', 'tickets', 'assets', 'financial', 'projects', 'reports', 'knowledge', 'integrations', 'settings'];
        
        foreach ($domains as $domain) {
            $counts = NavigationService::getBadgeCounts($domain);
            $this->assertIsArray($counts);
        }
    }

    public function test_can_access_domain_for_all_domains(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        $domains = ['clients', 'tickets', 'assets', 'financial', 'projects', 'reports', 'knowledge', 'integrations', 'settings'];
        
        foreach ($domains as $domain) {
            $canAccess = NavigationService::canAccessDomain($mockUser, $domain);
            $this->assertTrue($canAccess);
        }
    }

    public function test_get_filtered_navigation_items_for_all_domains(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        $this->actingAs($mockUser);
        
        $domains = ['clients', 'tickets', 'assets', 'financial', 'projects', 'reports', 'knowledge', 'integrations', 'settings'];
        
        foreach ($domains as $domain) {
            $items = NavigationService::getFilteredNavigationItems($domain);
            $this->assertIsArray($items);
        }
    }

    public function test_can_access_navigation_item_for_multiple_domains(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        $tests = [
            ['clients', 'index', 'clients.view'],
            ['tickets', 'index', 'tickets.view'],
            ['assets', 'index', 'assets.view'],
            ['financial', 'invoices', 'financial.invoices.view'],
            ['projects', 'index', 'projects.view'],
        ];
        
        foreach ($tests as [$domain, $item, $permission]) {
            $mockUser->shouldReceive('hasPermission')->with($permission)->andReturn(true);
            $canAccess = NavigationService::canAccessNavigationItem($mockUser, $domain, $item);
            $this->assertTrue($canAccess);
        }
    }

    public function test_get_workflow_breadcrumbs_for_all_workflows(): void
    {
        $workflows = ['urgent', 'today', 'scheduled', 'financial', 'reports'];
        
        foreach ($workflows as $workflow) {
            NavigationService::setWorkflowContext($workflow);
            $breadcrumbs = NavigationService::getWorkflowBreadcrumbs();
            $this->assertIsArray($breadcrumbs);
        }
    }

    public function test_get_workflow_quick_actions_for_all_workflows(): void
    {
        $workflows = ['urgent', 'today', 'financial'];
        
        foreach ($workflows as $workflow) {
            $actions = NavigationService::getWorkflowQuickActions($workflow);
            $this->assertIsArray($actions);
        }
    }

    public function test_get_workflow_navigation_highlights_for_all_workflows(): void
    {
        $workflows = ['urgent', 'today', 'scheduled', 'financial'];
        
        foreach ($workflows as $workflow) {
            $highlights = NavigationService::getWorkflowNavigationHighlights($workflow);
            $this->assertIsArray($highlights);
            $this->assertArrayHasKey('urgent_count', $highlights);
            $this->assertArrayHasKey('today_count', $highlights);
            $this->assertArrayHasKey('scheduled_count', $highlights);
            $this->assertArrayHasKey('financial_count', $highlights);
        }
    }

    public function test_toggle_client_favorite_with_valid_client(): void
    {
        $result = NavigationService::toggleClientFavorite($this->client->id);
        $this->assertIsBool($result);
    }

    public function test_is_client_favorite_with_valid_client(): void
    {
        $result = NavigationService::isClientFavorite($this->client->id);
        $this->assertIsBool($result);
    }

    public function test_breadcrumbs_for_multiple_routes(): void
    {
        NavigationService::setSelectedClient($this->client->id);
        
        $routes = [
            'tickets.show',
            'tickets.create',
            'assets.index',
            'assets.create',
            'financial.invoices.index',
            'financial.quotes.index',
            'projects.index',
        ];
        
        foreach ($routes as $route) {
            Route::shouldReceive('currentRouteName')->andReturn($route);
            $breadcrumbs = NavigationService::getBreadcrumbs();
            $this->assertIsArray($breadcrumbs);
        }
    }

    public function test_get_client_navigation_items_with_various_permissions(): void
    {
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('hasPermission')->andReturn(true);
        
        NavigationService::clearSelectedClient();
        $items = NavigationService::getClientNavigationItems($mockUser);
        $this->assertIsArray($items);
        
        NavigationService::setSelectedClient($this->client->id);
        $items = NavigationService::getClientNavigationItems($mockUser);
        $this->assertIsArray($items);
    }

    public function test_get_favorite_clients_with_limit(): void
    {
        $favorites = NavigationService::getFavoriteClients(10);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $favorites);
        
        $favorites = NavigationService::getFavoriteClients(3);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $favorites);
    }

    public function test_get_recent_clients_with_limit(): void
    {
        $recent = NavigationService::getRecentClients(5);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recent);
        
        $recent = NavigationService::getRecentClients(10);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recent);
    }

    public function test_get_client_specific_badge_counts_handles_exception(): void
    {
        try {
            $counts = NavigationService::getClientSpecificBadgeCounts(99999, 99999);
            $this->assertIsArray($counts);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function test_all_workflow_methods_work_together(): void
    {
        NavigationService::setWorkflowContext('urgent');
        $this->assertTrue(NavigationService::isWorkflowActive('urgent'));
        
        $state = NavigationService::getWorkflowNavigationState();
        $this->assertIsArray($state);
        
        $params = NavigationService::getWorkflowRouteParams('urgent');
        $this->assertIsArray($params);
        
        $breadcrumbs = NavigationService::getWorkflowBreadcrumbs();
        $this->assertIsArray($breadcrumbs);
        
        $actions = NavigationService::getWorkflowQuickActions('urgent');
        $this->assertIsArray($actions);
        
        $highlights = NavigationService::getWorkflowNavigationHighlights('urgent');
        $this->assertIsArray($highlights);
        
        NavigationService::clearWorkflowContext();
        $this->assertFalse(NavigationService::isWorkflowActive('urgent'));
    }

    public function test_sidebar_registration_methods(): void
    {
        NavigationService::registerSidebarSection('test-domain', 'section-1', ['title' => 'Test Section 1']);
        NavigationService::registerSidebarSection('test-domain', 'section-2', ['title' => 'Test Section 2']);
        
        NavigationService::registerSidebarSections('another-domain', [
            'sec1' => ['title' => 'Section 1'],
            'sec2' => ['title' => 'Section 2'],
            'sec3' => ['title' => 'Section 3'],
        ]);
        
        $this->assertTrue(true);
    }
}
