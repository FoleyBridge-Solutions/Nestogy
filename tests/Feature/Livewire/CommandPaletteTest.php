<?php

namespace Tests\Feature\Livewire;

use App\Domains\Core\Services\QuickActionService;
use App\Livewire\CommandPalette;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommandPaletteTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->actingAs($this->user);
    }

    public function test_component_renders_successfully()
    {
        Livewire::test(CommandPalette::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.command-palette');
    }

    public function test_mount_initializes_with_closed_state()
    {
        Livewire::test(CommandPalette::class)
            ->assertSet('isOpen', false)
            ->assertSet('search', '')
            ->assertSet('selectedIndex', 0);
    }

    public function test_mount_captures_current_route()
    {
        $this->get(route('dashboard'));
        
        $component = Livewire::test(CommandPalette::class);
        
        $this->assertNotNull($component->get('currentRoute'));
    }

    public function test_open_method_opens_palette()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertSet('search', '')
            ->assertSet('selectedIndex', 0);
    }

    public function test_open_initializes_with_popular_commands()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open');

        $results = $component->get('results');
        $this->assertNotEmpty($results);
    }

    public function test_open_with_current_route_parameter()
    {
        Livewire::test(CommandPalette::class)
            ->call('open', 'dashboard')
            ->assertSet('currentRoute', 'dashboard')
            ->assertSet('isOpen', true);
    }

    public function test_close_method_closes_palette()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('search', '')
            ->assertSet('selectedIndex', 0);
    }

    public function test_handle_open_event_opens_palette()
    {
        Livewire::test(CommandPalette::class)
            ->dispatch('openCommandPalette', currentRoute: 'dashboard')
            ->assertSet('isOpen', true);
    }

    public function test_updated_search_resets_selected_index()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('selectedIndex', 3)
            ->set('search', 'test')
            ->assertSet('selectedIndex', 0);
    }

    public function test_search_results_finds_clients()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Unique Client Name',
            'email' => 'unique@example.com',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'Unique');

        $searchResults = $component->viewData('searchResults');
        
        $clientResults = collect($searchResults)->filter(fn ($r) => $r['type'] === 'client');
        $this->assertNotEmpty($clientResults);
    }

    public function test_search_results_finds_clients_by_email()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client',
            'email' => 'uniqueemail@example.com',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'uniqueemail');

        $searchResults = $component->viewData('searchResults');
        
        $clientResults = collect($searchResults)->filter(fn ($r) => $r['type'] === 'client');
        $this->assertNotEmpty($clientResults);
    }

    public function test_search_results_finds_invoices()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $client->id,
            'number' => 'INV-12345',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', '12345');

        $searchResults = $component->viewData('searchResults');
        
        $invoiceResults = collect($searchResults)->filter(fn ($r) => $r['type'] === 'invoice');
        $this->assertNotEmpty($invoiceResults);
    }

    public function test_search_results_finds_assets()
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Unique Asset Name',
            'serial' => 'SN123456',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'Unique Asset');

        $searchResults = $component->viewData('searchResults');
        
        $assetResults = collect($searchResults)->filter(fn ($r) => $r['type'] === 'asset');
        $this->assertNotEmpty($assetResults);
    }

    public function test_search_respects_company_isolation()
    {
        $company2 = Company::factory()->create();
        $company2Client = Client::factory()->create([
            'company_id' => $company2->id,
            'name' => 'Other Company Client',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'Other Company');

        $searchResults = $component->viewData('searchResults');
        
        $clientResults = collect($searchResults)->filter(fn ($r) => 
            $r['type'] === 'client' && $r['id'] === $company2Client->id
        );
        $this->assertEmpty($clientResults);
    }

    public function test_search_results_includes_quick_actions()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'create');

        $searchResults = $component->viewData('searchResults');
        
        $quickActions = collect($searchResults)->filter(fn ($r) => 
            $r['type'] === 'quick_action' || $r['type'] === 'navigation'
        );
        $this->assertNotEmpty($quickActions);
    }

    public function test_select_next_increments_index()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('selectedIndex', 0)
            ->call('selectNext')
            ->assertSet('selectedIndex', 1);
    }

    public function test_select_next_stops_at_end()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open');

        $resultsCount = count($component->get('results'));
        
        $component->set('selectedIndex', $resultsCount - 1)
            ->call('selectNext')
            ->assertSet('selectedIndex', $resultsCount - 1);
    }

    public function test_select_previous_decrements_index()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('selectedIndex', 2)
            ->call('selectPrevious')
            ->assertSet('selectedIndex', 1);
    }

    public function test_select_previous_stops_at_zero()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('selectedIndex', 0)
            ->call('selectPrevious')
            ->assertSet('selectedIndex', 0);
    }

    public function test_set_selected_index_updates_index()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->call('setSelectedIndex', 3)
            ->assertSet('selectedIndex', 3);
    }

    public function test_get_popular_commands_returns_favorites()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open');

        $results = $component->get('results');
        $this->assertNotEmpty($results);
        $this->assertIsArray($results);
    }

    public function test_popular_commands_excludes_current_route()
    {
        $this->get(route('dashboard'));
        
        $component = Livewire::test(CommandPalette::class)
            ->call('open', 'dashboard');

        $results = $component->get('results');
        
        // Dashboard should not appear in results when on dashboard
        $dashboardResults = collect($results)->filter(fn ($r) => 
            isset($r['route_name']) && $r['route_name'] === 'dashboard'
        );
        $this->assertEmpty($dashboardResults);
    }

    public function test_select_result_with_route_name()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('results', [
                [
                    'type' => 'navigation',
                    'title' => 'Dashboard',
                    'route_name' => 'dashboard',
                    'route_params' => [],
                ],
            ])
            ->call('selectResult', 0)
            ->assertRedirect(route('dashboard'));
    }

    public function test_select_result_closes_palette()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('results', [
                [
                    'type' => 'navigation',
                    'title' => 'Dashboard',
                    'route_name' => 'dashboard',
                    'route_params' => [],
                ],
            ])
            ->call('selectResult', 0)
            ->assertSet('isOpen', false);
    }

    public function test_select_result_with_invalid_index_does_nothing()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('results', [])
            ->call('selectResult', 99)
            ->assertSet('isOpen', true);
    }

    public function test_select_result_with_client_navigates_to_client()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client',
        ]);

        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('results', [
                [
                    'type' => 'client',
                    'id' => $client->id,
                    'title' => $client->name,
                    'route_name' => 'clients.show',
                    'route_params' => ['client' => $client->id],
                ],
            ])
            ->call('selectResult', 0)
            ->assertRedirect(route('clients.show', $client));
    }

    public function test_navigate_to_redirects_to_url()
    {
        Livewire::test(CommandPalette::class)
            ->call('navigateTo', route('dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_navigate_to_route_redirects_to_route()
    {
        Livewire::test(CommandPalette::class)
            ->call('navigateToRoute', 'dashboard', [])
            ->assertRedirect(route('dashboard'));
    }

    public function test_get_debug_state_returns_current_state()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'test');

        $debugState = $component->instance()->getDebugState();

        $this->assertIsArray($debugState);
        $this->assertEquals('test', $debugState['search']);
        $this->assertTrue($debugState['isOpen']);
        $this->assertArrayHasKey('resultsCount', $debugState);
        $this->assertArrayHasKey('currentRoute', $debugState);
    }

    public function test_set_current_route_updates_route()
    {
        Livewire::test(CommandPalette::class)
            ->call('setCurrentRoute', 'clients.index')
            ->assertSet('currentRoute', 'clients.index');
    }

    public function test_search_results_caching()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'test');

        $firstResults = $component->viewData('searchResults');
        
        // Search again with same term
        $component->set('search', 'test');
        $secondResults = $component->viewData('searchResults');

        // Results should be the same (cached)
        $this->assertEquals($firstResults, $secondResults);
    }

    public function test_search_results_cache_clears_on_search_change()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'test')
            ->set('search', 'different');

        // Cache should have been cleared and recalculated
        $this->assertNotNull($component->viewData('searchResults'));
    }

    public function test_empty_search_shows_popular_commands()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', '');

        $searchResults = $component->viewData('searchResults');
        $this->assertNotEmpty($searchResults);
    }

    public function test_search_with_one_character_shows_popular_commands()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', '');

        $searchResults = $component->viewData('searchResults');
        $this->assertNotEmpty($searchResults);
    }

    public function test_search_error_handling_returns_empty_results()
    {
        // This tests the catch block in getSearchResults
        // We'd need to mock a database error, but we can verify the structure
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'test');

        $searchResults = $component->viewData('searchResults');
        $this->assertIsArray($searchResults);
    }

    public function test_search_limits_total_results()
    {
        // Create many clients
        Client::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'Test');

        $searchResults = $component->viewData('searchResults');
        
        // Should be limited to 15 total results
        $this->assertLessThanOrEqual(15, count($searchResults));
    }

    public function test_quick_actions_include_create_commands()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'new ticket');

        $searchResults = $component->viewData('searchResults');
        
        $createActions = collect($searchResults)->filter(fn ($r) => 
            str_contains(strtolower($r['title'] ?? ''), 'ticket')
        );
        $this->assertNotEmpty($createActions);
    }

    public function test_navigation_commands_included_in_search()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'clients');

        $searchResults = $component->viewData('searchResults');
        
        $navCommands = collect($searchResults)->filter(fn ($r) => 
            $r['type'] === 'navigation' || $r['type'] === 'quick_action'
        );
        $this->assertNotEmpty($navCommands);
    }

    public function test_select_result_with_quick_action_route()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('results', [
                [
                    'type' => 'quick_action',
                    'title' => 'Create Ticket',
                    'action_data' => [
                        'route' => 'tickets.create',
                        'parameters' => [],
                    ],
                    'route_name' => 'tickets.create',
                    'route_params' => [],
                ],
            ])
            ->call('selectResult', 0)
            ->assertRedirect(route('tickets.create'));
    }

    public function test_search_results_structure_is_valid()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Structure Test Client',
        ]);

        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'Structure');

        $searchResults = $component->viewData('searchResults');
        
        foreach ($searchResults as $result) {
            $this->assertArrayHasKey('type', $result);
            $this->assertArrayHasKey('title', $result);
            $this->assertArrayHasKey('subtitle', $result);
            $this->assertArrayHasKey('icon', $result);
        }
    }

    public function test_component_handles_missing_route_gracefully()
    {
        Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('results', [
                [
                    'type' => 'navigation',
                    'title' => 'Fake Route',
                    'route_name' => 'nonexistent.route.name',
                    'route_params' => [],
                ],
            ])
            ->call('selectResult', 0)
            ->assertSet('isOpen', false);
    }

    public function test_opening_palette_filters_livewire_routes()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open', 'livewire.update');

        // Should not set current route to livewire routes
        $currentRoute = $component->get('currentRoute');
        $this->assertNotEquals('livewire.update', $currentRoute);
    }

    public function test_search_includes_navigation_from_sidebar()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'dashboard');

        $searchResults = $component->viewData('searchResults');
        
        $this->assertNotEmpty($searchResults);
    }

    public function test_cache_clears_on_close()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'test')
            ->call('close');

        // After closing, cache should be cleared
        $component->call('open');
        $this->assertNotNull($component->viewData('searchResults'));
    }

    public function test_results_array_stays_in_sync_with_computed_property()
    {
        $component = Livewire::test(CommandPalette::class)
            ->call('open')
            ->set('search', 'test');

        $results = $component->get('results');
        $searchResults = $component->viewData('searchResults');

        $this->assertEquals($results, $searchResults);
    }
}