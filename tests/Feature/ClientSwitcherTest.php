<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Services\ClientFavoriteService;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ClientSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and client
        $this->user = User::factory()->create([
            'company_id' => 1,
        ]);
        
        $this->client = Client::factory()->create([
            'company_id' => 1,
            'status' => 'active',
            'name' => 'Test Client',
        ]);
    }

    public function test_user_can_favorite_client()
    {
        $service = new ClientFavoriteService();
        
        // Add client to favorites
        $result = $service->addFavorite($this->user, $this->client);
        
        $this->assertTrue($result);
        $this->assertTrue($service->isFavorite($this->user, $this->client));
    }

    public function test_user_can_toggle_favorite_client()
    {
        $service = new ClientFavoriteService();
        
        // Toggle favorite (should add)
        $result1 = $service->toggle($this->user, $this->client);
        $this->assertTrue($result1); // true means added to favorites
        $this->assertTrue($service->isFavorite($this->user, $this->client));
        
        // Toggle again (should remove)
        $result2 = $service->toggle($this->user, $this->client);
        $this->assertFalse($result2); // false means removed from favorites
        $this->assertFalse($service->isFavorite($this->user, $this->client));
    }

    public function test_favorite_clients_limited_to_five()
    {
        $service = new ClientFavoriteService();
        
        // Create 6 clients
        $clients = Client::factory()->count(6)->create([
            'company_id' => 1,
            'status' => 'active',
        ]);
        
        // Add 5 clients to favorites
        foreach ($clients->take(5) as $client) {
            $service->addFavorite($this->user, $client);
        }
        
        $this->assertEquals(5, $service->getFavoriteCount($this->user));
        
        // Try to add 6th client
        $result = $service->addFavorite($this->user, $clients->last());
        $this->assertFalse($result); // Should fail due to limit
        $this->assertEquals(5, $service->getFavoriteCount($this->user));
    }

    public function test_navigation_service_tracks_client_access()
    {
        $this->actingAs($this->user);
        
        $originalAccessTime = $this->client->accessed_at;
        
        // Set selected client
        NavigationService::setSelectedClient($this->client->id);
        
        // Refresh client from database
        $this->client->refresh();
        
        // Should have updated accessed_at time
        $this->assertNotEquals($originalAccessTime, $this->client->accessed_at);
        $this->assertNotNull($this->client->accessed_at);
    }

    public function test_get_smart_client_suggestions()
    {
        $this->actingAs($this->user);
        
        $service = new ClientFavoriteService();
        
        // Add client to favorites
        $service->addFavorite($this->user, $this->client);
        
        // Mark as accessed
        $this->client->markAsAccessed();
        
        $suggestions = NavigationService::getSmartClientSuggestions();
        
        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('favorites', $suggestions);
        $this->assertArrayHasKey('recent', $suggestions);
        $this->assertArrayHasKey('total', $suggestions);
        
        // Should have our client in favorites
        $this->assertGreaterThan(0, $suggestions['favorites']->count());
        $this->assertEquals($this->client->id, $suggestions['favorites']->first()->id);
    }

    public function test_client_relationship_methods()
    {
        // Test User -> favoriteClients relationship
        $favoriteClients = $this->user->favoriteClients();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $favoriteClients);
        
        // Test Client -> favoritedByUsers relationship
        $favoritedBy = $this->client->favoritedByUsers();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $favoritedBy);
    }
}