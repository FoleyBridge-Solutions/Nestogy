<?php

namespace Tests\Feature\Integration;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\DeviceMapping;
use App\Jobs\SyncDeviceInventory;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @group contract
 * @group integration
 * @group assets
 */
class DeviceMappingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Client $client;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->integration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'connectwise',
            'api_endpoint' => 'https://api.connectwise.com',
            'credentials_encrypted' => encrypt(json_encode(['api_key' => 'test-api-key'])),
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_creates_device_mapping_from_webhook_payload()
    {
        $mapping = DeviceMapping::updateOrCreateMapping(
            $this->integration->id,
            'CW-12345',
            $this->client->id,
            'Test-Workstation-01',
            [
                'ip_address' => '192.168.1.100',
                'os_version' => 'Windows 11',
                'last_seen' => now()->toISOString(),
            ]
        );

        $this->assertInstanceOf(DeviceMapping::class, $mapping);
        $this->assertEquals($this->integration->id, $mapping->integration_id);
        $this->assertEquals('CW-12345', $mapping->rmm_device_id);
        $this->assertEquals($this->client->id, $mapping->client_id);
        $this->assertEquals('Test-Workstation-01', $mapping->device_name);
        $this->assertTrue($mapping->is_active);
        $this->assertEquals('192.168.1.100', $mapping->getSyncDataField('ip_address'));
        $this->assertEquals('Windows 11', $mapping->getSyncDataField('os_version'));
    }

    /** @test */
    public function it_updates_existing_device_mapping()
    {
        // Create initial mapping
        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'CW-12345',
            'client_id' => $this->client->id,
            'device_name' => 'Old-Name',
            'sync_data' => ['version' => '1.0'],
        ]);

        $originalId = $mapping->id;

        // Update mapping
        $updatedMapping = DeviceMapping::updateOrCreateMapping(
            $this->integration->id,
            'CW-12345',
            $this->client->id,
            'New-Name',
            [
                'version' => '2.0',
                'updated_field' => 'new_value',
            ]
        );

        // Should be the same record, updated
        $this->assertEquals($originalId, $updatedMapping->id);
        $this->assertEquals('New-Name', $updatedMapping->device_name);
        $this->assertEquals('2.0', $updatedMapping->getSyncDataField('version'));
        $this->assertEquals('new_value', $updatedMapping->getSyncDataField('updated_field'));
    }

    /** @test */
    public function it_can_link_device_to_asset()
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'client_id' => $this->client->id,
        ]);

        $mapping->linkToAsset($asset->id);

        $this->assertEquals($asset->id, $mapping->asset_id);
        $this->assertTrue($mapping->hasAsset());
    }

    /** @test */
    public function it_can_unlink_device_from_asset()
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'client_id' => $this->client->id,
            'asset_id' => $asset->id,
        ]);

        $mapping->unlinkFromAsset();

        $this->assertNull($mapping->asset_id);
        $this->assertFalse($mapping->hasAsset());
    }

    /** @test */
    public function it_can_update_sync_data()
    {
        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'sync_data' => ['existing' => 'value'],
        ]);

        $oldLastUpdated = $mapping->last_updated;

        // Wait a moment to ensure timestamp changes
        sleep(1);

        $mapping->updateSyncData([
            'new_field' => 'new_value',
            'updated_field' => 'updated_value',
        ]);

        $mapping->refresh();

        // Should merge with existing data
        $this->assertEquals('value', $mapping->getSyncDataField('existing'));
        $this->assertEquals('new_value', $mapping->getSyncDataField('new_field'));
        $this->assertEquals('updated_value', $mapping->getSyncDataField('updated_field'));
        $this->assertTrue($mapping->last_updated->gt($oldLastUpdated));
    }

    /** @test */
    public function it_can_detect_stale_mappings()
    {
        // Create fresh mapping
        $freshMapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'last_updated' => now()->subHours(12),
        ]);

        // Create stale mapping
        $staleMapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'last_updated' => now()->subHours(48),
        ]);

        $this->assertFalse($freshMapping->isStale(24));
        $this->assertTrue($staleMapping->isStale(24));
    }

    /** @test */
    public function it_provides_proper_scoping_methods()
    {
        $otherIntegration = Integration::factory()->create();
        $otherClient = Client::factory()->create();

        // Create mappings with different attributes
        $activeMapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'client_id' => $this->client->id,
            'asset_id' => Asset::factory()->create()->id,
            'is_active' => true,
        ]);

        $inactiveMapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'is_active' => false,
        ]);

        $unmappedMapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'asset_id' => null,
        ]);

        $otherIntegrationMapping = DeviceMapping::factory()->create([
            'integration_id' => $otherIntegration->id,
        ]);

        // Test scopes
        $this->assertEquals(3, DeviceMapping::forIntegration($this->integration->id)->count());
        $this->assertEquals(1, DeviceMapping::forClient($this->client->id)->count());
        $this->assertEquals(1, DeviceMapping::active()->where('integration_id', $this->integration->id)->count());
        $this->assertEquals(1, DeviceMapping::inactive()->where('integration_id', $this->integration->id)->count());
        $this->assertEquals(1, DeviceMapping::mapped()->where('integration_id', $this->integration->id)->count());
        $this->assertEquals(2, DeviceMapping::unmapped()->where('integration_id', $this->integration->id)->count());
    }

    /** @test */
    public function it_can_sync_from_rmm_payload()
    {
        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'device_name' => 'Old-Name',
        ]);

        $payload = [
            'ComputerID' => 'CW-12345',
            'ComputerName' => 'Updated-Name',
            'IP' => '192.168.1.150',
            'OS' => 'Windows 11 Pro',
        ];

        $fieldMappings = [
            'device_id' => 'ComputerID',
            'device_name' => 'ComputerName',
        ];

        $mapping->syncFromPayload($payload, $fieldMappings);

        $this->assertEquals('Updated-Name', $mapping->device_name);
        $this->assertEquals($payload, $mapping->getSyncDataField('last_payload'));
        $this->assertNotNull($mapping->getSyncDataField('last_sync'));
    }

    /** @test */
    public function it_processes_device_inventory_sync_job()
    {
        Http::fake([
            'api.connectwise.com/computers/*' => Http::response([
                'ComputerID' => 'CW-12345',
                'ComputerName' => 'Test-Device',
                'ClientID' => 'CLIENT-001',
                'Status' => 'Online',
                'LastSeen' => now()->toISOString(),
            ], 200),
        ]);

        Queue::fake();

        $job = new SyncDeviceInventory($this->integration, 'CW-12345');
        $job->handle();

        $this->integration->refresh();
        $this->assertNotNull($this->integration->last_sync);
    }

    /** @test */
    public function it_handles_device_sync_job_for_inactive_integration()
    {
        $this->integration->update(['is_active' => false]);

        $job = new SyncDeviceInventory($this->integration, 'CW-12345');
        $job->handle();

        // Should complete without error but not update last_sync
        $this->integration->refresh();
        $this->assertNull($this->integration->last_sync);
    }

    /** @test */
    public function it_can_search_devices_by_name()
    {
        DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'device_name' => 'Production-Server-01',
        ]);

        DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'device_name' => 'Test-Workstation-02',
        ]);

        DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'device_name' => 'Development-Server-03',
        ]);

        $results = DeviceMapping::searchByName('Server')->get();
        $this->assertEquals(2, $results->count());

        $results = DeviceMapping::searchByName('Production')->get();
        $this->assertEquals(1, $results->count());

        $results = DeviceMapping::searchByName('Workstation')->get();
        $this->assertEquals(1, $results->count());
    }

    /** @test */
    public function it_maintains_unique_constraint_on_integration_and_device_id()
    {
        DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'UNIQUE-001',
        ]);

        // Creating second mapping with same integration_id and rmm_device_id should update, not create new
        $secondMapping = DeviceMapping::updateOrCreateMapping(
            $this->integration->id,
            'UNIQUE-001',
            $this->client->id,
            'Updated Device Name'
        );

        $this->assertEquals(1, DeviceMapping::where('rmm_device_id', 'UNIQUE-001')->count());
        $this->assertEquals('Updated Device Name', $secondMapping->device_name);
    }

    /** @test */
    public function it_handles_missing_field_mappings_gracefully()
    {
        $payload = [
            'DeviceID' => 'DEV-001', // Different field name
            'Name' => 'Test Device',
        ];

        $fieldMappings = [
            'device_id' => 'ComputerID', // Mapping expects ComputerID but payload has DeviceID
            'device_name' => 'ComputerName', // Mapping expects ComputerName but payload has Name
        ];

        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'device_name' => 'Original Name',
        ]);

        $mapping->syncFromPayload($payload, $fieldMappings);

        // Should keep original name since mapping didn't match
        $this->assertEquals('Original Name', $mapping->device_name);
        // But should still update sync data with raw payload
        $this->assertEquals($payload, $mapping->getSyncDataField('last_payload'));
    }
}