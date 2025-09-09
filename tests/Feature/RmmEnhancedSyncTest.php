<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Asset;
use App\Models\Client;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Services\AssetSyncService;
use App\Domains\Integration\Services\TacticalRmm\TacticalRmmService;
use App\Domains\Integration\Services\TacticalRmm\TacticalRmmDataMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;

/**
 * Enhanced RMM Sync Feature Tests
 * 
 * Tests the comprehensive asset synchronization and remote management capabilities.
 */
class RmmEnhancedSyncTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Company $company;
    protected Client $client;
    protected RmmIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->integration = RmmIntegration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'tactical_rmm',
            'name' => 'Test TacticalRMM',
            'api_url' => 'https://tactical.example.com',
            'api_key' => 'test-api-key',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_comprehensive_asset_from_rmm_data(): void
    {
        $mockRmmService = Mockery::mock(TacticalRmmService::class);
        $syncService = new AssetSyncService(app(\App\Domains\Integration\Services\RmmServiceFactory::class));
        
        // Mock comprehensive inventory data
        $inventoryData = [
            'agent' => [
                'id' => 'agent-123',
                'hostname' => 'TEST-WORKSTATION',
                'operating_system' => 'Windows 10 Pro',
                'online' => true,
                'local_ip' => '192.168.1.100',
                'public_ip' => '203.0.113.1',
                'mac_address' => '00:11:22:33:44:55',
            ],
            'hardware' => [
                'cpu' => [
                    'model' => 'Intel Core i7-10700K',
                    'cores' => 8,
                    'logical_cores' => 16,
                ],
                'memory' => [
                    'total_gb' => 32,
                    'available_gb' => 24,
                ],
                'storage' => [
                    [
                        'device' => 'C:',
                        'size_gb' => 500,
                        'free_gb' => 250,
                        'used_percent' => 50,
                    ],
                ],
            ],
            'performance' => [
                'cpu' => ['usage_percent' => 25],
                'memory' => ['usage_percent' => 75],
                'uptime' => ['uptime_seconds' => 86400],
            ],
            'software' => [
                ['name' => 'Microsoft Office', 'version' => '2021'],
                ['name' => 'Chrome', 'version' => '120.0'],
            ],
            'services' => [
                ['name' => 'Spooler', 'status' => 'running'],
                ['name' => 'BITS', 'status' => 'stopped'],
            ],
        ];
        
        $mockRmmService->shouldReceive('getFullDeviceInventory')
            ->with('agent-123')
            ->andReturn([
                'success' => true,
                'data' => $inventoryData,
            ]);
        
        $agentData = $inventoryData['agent'];
        
        // Test the sync
        $asset = $syncService->syncSingleAsset($this->integration, $mockRmmService, $agentData);
        
        // Verify asset was created correctly
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('TEST-WORKSTATION', $asset->name);
        $this->assertEquals('Windows 10 Pro', $asset->os);
        $this->assertEquals('192.168.1.100', $asset->ip);
        $this->assertEquals('203.0.113.1', $asset->nat_ip);
        $this->assertEquals('00:11:22:33:44:55', $asset->mac);
        $this->assertEquals('Deployed', $asset->status);
        $this->assertEquals('agent-123', $asset->rmm_id);
        
        // Verify device mapping was created
        $mapping = DeviceMapping::where('rmm_device_id', 'agent-123')->first();
        $this->assertNotNull($mapping);
        $this->assertEquals($asset->id, $mapping->asset_id);
        $this->assertEquals($this->integration->id, $mapping->integration_id);
        $this->assertTrue($mapping->is_active);
        
        // Verify sync data contains inventory
        $syncData = $mapping->sync_data;
        $this->assertArrayHasKey('last_full_sync', $syncData);
        $this->assertArrayHasKey('inventory_data', $syncData);
        $this->assertEquals($inventoryData, $syncData['inventory_data']);
    }

    /** @test */
    public function it_can_update_existing_asset_with_new_inventory_data(): void
    {
        // Create existing asset and mapping
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'OLD-NAME',
            'rmm_id' => 'agent-123',
        ]);
        
        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'agent-123',
            'asset_id' => $asset->id,
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);
        
        $mockRmmService = Mockery::mock(TacticalRmmService::class);
        $syncService = new AssetSyncService(app(\App\Domains\Integration\Services\RmmServiceFactory::class));
        
        // Mock updated inventory data
        $inventoryData = [
            'agent' => [
                'id' => 'agent-123',
                'hostname' => 'UPDATED-NAME',
                'operating_system' => 'Windows 11 Pro',
                'online' => true,
                'local_ip' => '192.168.1.101',
            ],
            'hardware' => ['cpu' => ['model' => 'Intel Core i9-12700K']],
            'performance' => ['cpu' => ['usage_percent' => 15]],
            'software' => [],
            'services' => [],
        ];
        
        $mockRmmService->shouldReceive('getFullDeviceInventory')
            ->with('agent-123')
            ->andReturn([
                'success' => true,
                'data' => $inventoryData,
            ]);
        
        // Test the sync update
        $updatedAsset = $syncService->syncSingleAsset($this->integration, $mockRmmService, $inventoryData['agent']);
        
        // Verify asset was updated
        $this->assertEquals($asset->id, $updatedAsset->id);
        $this->assertEquals('UPDATED-NAME', $updatedAsset->name);
        $this->assertEquals('Windows 11 Pro', $updatedAsset->os);
        $this->assertEquals('192.168.1.101', $updatedAsset->ip);
        
        // Verify mapping was updated
        $mapping->refresh();
        $syncData = $mapping->sync_data;
        $this->assertArrayHasKey('last_full_sync', $syncData);
        $this->assertEquals($inventoryData, $syncData['inventory_data']);
    }

    /** @test */
    public function it_handles_rmm_service_errors_gracefully(): void
    {
        $mockRmmService = Mockery::mock(TacticalRmmService::class);
        $syncService = new AssetSyncService(app(\App\Domains\Integration\Services\RmmServiceFactory::class));
        
        // Mock service error
        $mockRmmService->shouldReceive('getFullDeviceInventory')
            ->with('agent-123')
            ->andReturn([
                'success' => false,
                'error' => 'Connection timeout',
                'errors' => ['agent' => 'Failed to connect to device'],
            ]);
        
        $agentData = ['id' => 'agent-123', 'hostname' => 'test-device'];
        
        // Test that sync throws exception on error
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get device inventory: Failed to connect to device');
        
        $syncService->syncSingleAsset($this->integration, $mockRmmService, $agentData);
    }

    /** @test */
    public function it_can_execute_remote_commands_through_sync_service(): void
    {
        // Create asset with RMM connection
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);
        
        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'agent-123',
            'asset_id' => $asset->id,
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);
        
        $mockRmmService = Mockery::mock(TacticalRmmService::class);
        $this->app->instance(\App\Domains\Integration\Services\RmmServiceFactory::class, 
            Mockery::mock(\App\Domains\Integration\Services\RmmServiceFactory::class, function ($mock) use ($mockRmmService) {
                $mock->shouldReceive('create')->andReturn($mockRmmService);
            })
        );
        
        $syncService = new AssetSyncService(app(\App\Domains\Integration\Services\RmmServiceFactory::class));
        
        // Mock command execution
        $mockRmmService->shouldReceive('runCommand')
            ->with('agent-123', 'ipconfig /all', ['shell' => 'cmd', 'timeout' => 30])
            ->andReturn([
                'success' => true,
                'task_id' => 'task-456',
                'message' => 'Command executed successfully',
            ]);
        
        // Test command execution
        $result = $syncService->executeRemoteCommand($asset, 'ipconfig /all', ['shell' => 'cmd', 'timeout' => 30]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('task-456', $result['task_id']);
    }

    /** @test */
    public function it_can_manage_windows_services_remotely(): void
    {
        // Create asset with RMM connection
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);
        
        $mapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'agent-123',
            'asset_id' => $asset->id,
            'client_id' => $this->client->id,
            'is_active' => true,
        ]);
        
        $mockRmmService = Mockery::mock(TacticalRmmService::class);
        $this->app->instance(\App\Domains\Integration\Services\RmmServiceFactory::class, 
            Mockery::mock(\App\Domains\Integration\Services\RmmServiceFactory::class, function ($mock) use ($mockRmmService) {
                $mock->shouldReceive('create')->andReturn($mockRmmService);
            })
        );
        
        $syncService = new AssetSyncService(app(\App\Domains\Integration\Services\RmmServiceFactory::class));
        
        // Mock service restart
        $mockRmmService->shouldReceive('restartService')
            ->with('agent-123', 'Spooler')
            ->andReturn([
                'success' => true,
                'message' => "Service 'Spooler' restarted successfully",
            ]);
        
        // Test service management
        $result = $syncService->manageService($asset, 'Spooler', 'restart');
        
        $this->assertTrue($result['success']);
        $this->assertStringContains('restarted successfully', $result['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}