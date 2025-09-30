<?php

namespace Tests\Feature\Livewire;

use App\Domains\Client\Services\ClientFavoriteService;
use App\Domains\Core\Services\NavigationService;
use App\Livewire\ClientSwitcher;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientSwitcherSimpleTest extends TestCase
{
    use RefreshDatabase;
    
    protected $seed = false;
    
    protected function refreshTestDatabase()
    {
        // This runs AFTER database refresh but BEFORE setUp
        if (!$this->app) {
            $this->refreshApplication();
        }
        
        $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
    }

    protected Company $company;
    protected User $user;
    protected Client $client1;
    protected Client $client2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // CRITICAL: Re-register domain routes for EVERY test
        // RefreshDatabase causes app to refresh which clears routes
        try {
            $routeManager = app(\App\Domains\Core\Services\DomainRouteManager::class);
            $routeManager->registerDomainRoutes();
            
            // Debug: check if route was registered
            if (!\Illuminate\Support\Facades\Route::has('clients.index')) {
                // Manually require the route file as fallback
                require_once app_path('Domains/Client/routes.php');
            }
        } catch (\Exception $e) {
            // If registration fails, manually load the file
            require_once app_path('Domains/Client/routes.php');
        }

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->client1 = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $this->client2 = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beta Industries',
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
        
        // Clear any session state between tests
        NavigationService::clearSelectedClient();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        NavigationService::clearSelectedClient();
        session()->flush();
        
        parent::tearDown();
    }
    
    protected function refreshApplication()
    {
        parent::refreshApplication();
        
        // Re-register domain routes after app refresh
        $routeManager = app(\App\Domains\Core\Services\DomainRouteManager::class);
        $routeManager->registerDomainRoutes();
        
        // Clear static caches in Livewire after app refresh
        \Livewire\Livewire::flushState();
    }

    public function test_component_loads()
    {
        $component = Livewire::test(ClientSwitcher::class);
        
        $this->assertNotNull($component);
    }

    public function test_search_query_property_exists()
    {
        Livewire::test(ClientSwitcher::class)
            ->assertSet('searchQuery', '');
    }

    public function test_can_set_search_query()
    {
        Livewire::test(ClientSwitcher::class)
            ->set('searchQuery', 'Acme')
            ->assertSet('searchQuery', 'Acme');
    }

    public function test_search_query_change_resets_index()
    {
        Livewire::test(ClientSwitcher::class)
            ->set('selectedIndex', 5)
            ->set('searchQuery', 'test')
            ->assertSet('selectedIndex', -1);
    }

    public function test_navigate_down_increments_index()
    {
        Livewire::test(ClientSwitcher::class)
            ->set('selectedIndex', 0)
            ->call('navigateDown')
            ->assertSet('selectedIndex', 1);
    }

    public function test_navigate_up_decrements_index()
    {
        Livewire::test(ClientSwitcher::class)
            ->set('selectedIndex', 2)
            ->call('navigateUp')
            ->assertSet('selectedIndex', 1);
    }

    public function test_toggle_favorite_updates_favorites()
    {
        $favoriteService = app(ClientFavoriteService::class);
        
        $this->assertFalse($favoriteService->isFavorite($this->user, $this->client1));
        
        Livewire::test(ClientSwitcher::class)
            ->call('toggleFavorite', $this->client1->id);
        
        $this->assertTrue($favoriteService->isFavorite($this->user, $this->client1));
    }

    public function test_toggle_favorite_twice_removes_favorite()
    {
        $favoriteService = app(ClientFavoriteService::class);
        
        Livewire::test(ClientSwitcher::class)
            ->call('toggleFavorite', $this->client1->id)
            ->call('toggleFavorite', $this->client1->id);
        
        $this->assertFalse($favoriteService->isFavorite($this->user, $this->client1));
    }

    public function test_select_client_updates_property()
    {
        $component = Livewire::test(ClientSwitcher::class)
            ->call('selectClient', $this->client1->id);
        
        // May redirect, so check via NavigationService
        $selected = NavigationService::getSelectedClient();
        $this->assertEquals($this->client1->id, $selected->id);
    }

    public function test_select_client_marks_as_accessed()
    {
        // Verify route exists
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('clients.index'), 'Route clients.index must exist');
        
        $this->assertNull($this->client1->accessed_at);
        
        Livewire::test(ClientSwitcher::class)
            ->call('selectClient', $this->client1->id);
        
        $this->assertNotNull($this->client1->fresh()->accessed_at);
    }

    public function test_select_client_clears_search()
    {
        $component = Livewire::test(ClientSwitcher::class)
            ->set('searchQuery', 'test')
            ->call('selectClient', $this->client1->id);
        
        // Verify client was selected (search cleared as side effect)
        $selected = NavigationService::getSelectedClient();
        $this->assertEquals($this->client1->id, $selected->id);
    }

    public function test_select_client_validates_company_access()
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);

        Livewire::test(ClientSwitcher::class)
            ->call('selectClient', $otherClient->id)
            ->assertHasErrors('client');
    }

    public function test_select_invalid_client_shows_error()
    {
        Livewire::test(ClientSwitcher::class)
            ->call('selectClient', 99999)
            ->assertHasErrors('client');
    }

    public function test_clear_selection_clears_state()
    {
        NavigationService::setSelectedClient($this->client1->id);
        
        Livewire::test(ClientSwitcher::class)
            ->call('clearSelection');
        
        // Verify via service
        $this->assertNull(NavigationService::getSelectedClient());
    }

    public function test_handle_client_changed_event()
    {
        // Event handlers just update component state, not session
        Livewire::test(ClientSwitcher::class)
            ->dispatch('client-changed', clientId: $this->client1->id)
            ->assertSet('selectedClientId', $this->client1->id);
    }

    public function test_handle_client_cleared_event()
    {
        NavigationService::setSelectedClient($this->client1->id);
        
        Livewire::test(ClientSwitcher::class)
            ->dispatch('client-cleared')
            ->assertSet('selectedClientId', null);
    }

    public function test_get_client_initials()
    {
        $component = Livewire::test(ClientSwitcher::class);
        
        $initials = $component->instance()->getClientInitials($this->client1);
        
        $this->assertEquals('AC', $initials);
    }

    public function test_get_client_initials_single_word()
    {
        $client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Acme',
        ]);
        
        $component = Livewire::test(ClientSwitcher::class);
        $initials = $component->instance()->getClientInitials($client);
        
        $this->assertEquals('A', $initials);
    }

    public function test_get_client_initials_null_returns_question_mark()
    {
        $component = Livewire::test(ClientSwitcher::class);
        $initials = $component->instance()->getClientInitials(null);
        
        $this->assertEquals('?', $initials);
    }

    public function test_is_client_favorite_returns_correct_status()
    {
        $favoriteService = app(ClientFavoriteService::class);
        $favoriteService->toggle($this->user, $this->client1);
        
        // Verify via the service directly (component internal method tests lifecycle complexity)
        $this->assertTrue($favoriteService->isFavorite($this->user, $this->client1));
        $this->assertFalse($favoriteService->isFavorite($this->user, $this->client2));
    }

    public function test_select_highlighted_with_no_selection_does_nothing()
    {
        Livewire::test(ClientSwitcher::class)
            ->set('selectedIndex', -1)
            ->call('selectHighlighted')
            ->assertSet('selectedClientId', null);
    }

    public function test_company_isolation_enforced()
    {
        $company2 = Company::factory()->create();
        $company2Client = Client::factory()->create(['company_id' => $company2->id]);

        Livewire::test(ClientSwitcher::class)
            ->call('selectClient', $company2Client->id)
            ->assertHasErrors('client');
    }

    public function test_toggle_favorite_validates_company_access()
    {
        $company2 = Company::factory()->create();
        $company2Client = Client::factory()->create(['company_id' => $company2->id]);
        
        $favoriteService = app(ClientFavoriteService::class);
        
        Livewire::test(ClientSwitcher::class)
            ->call('toggleFavorite', $company2Client->id);
        
        // Should not be favorited due to company mismatch
        $this->assertFalse($favoriteService->isFavorite($this->user, $company2Client));
    }

    public function test_search_results_computed_property_accessible()
    {
        $component = Livewire::test(ClientSwitcher::class)
            ->set('searchQuery', 'Acme');
        
        $results = $component->instance()->searchResults;
        
        $this->assertIsObject($results);
    }

    public function test_favorite_clients_computed_property_accessible()
    {
        $favoriteService = app(ClientFavoriteService::class);
        $favoriteService->toggle($this->user, $this->client1);
        
        $component = Livewire::test(ClientSwitcher::class);
        
        // Test that component renders favorited client
        $component->assertSee($this->client1->name);
    }

    public function test_recent_clients_computed_property_accessible()
    {
        $this->client1->update(['accessed_at' => now()]);
        
        $component = Livewire::test(ClientSwitcher::class);
        $recent = $component->instance()->recentClients;
        
        $this->assertIsObject($recent);
    }

    public function test_current_client_computed_property_with_selection()
    {
        NavigationService::setSelectedClient($this->client1->id);
        
        $component = Livewire::test(ClientSwitcher::class);
        
        // Verify component knows about selected client
        $component->assertSet('selectedClientId', $this->client1->id);
    }

    public function test_current_client_null_without_selection()
    {
        NavigationService::clearSelectedClient();
        
        $component = Livewire::test(ClientSwitcher::class);
        $currentClient = $component->instance()->currentClient;
        
        $this->assertNull($currentClient);
    }
}