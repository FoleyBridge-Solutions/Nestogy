<?php

namespace Tests\Unit\Integration;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Services\RMMIntegrationService;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group unit
 * @group integration
 * @group services
 */
class RMMIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RMMIntegrationService $service;
    private Company $company;
    private Client $client;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RMMIntegrationService();
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->integration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'connectwise',
        ]);
    }

    /** @test */
    public function it_standardizes_connectwise_payload_correctly()
    {
        $rawPayload = [
            'ComputerID' => 'CW-12345',
            'ComputerName' => 'Test-Server',
            'ClientID' => (string)$this->client->id,
            'AlertID' => 'ALERT-67890',
            'AlertMessage' => 'Service stopped',
            'Severity' => 'Critical',
            'AlertType' => 'Service',
            'DateStamp' => '2023-01-15T10:30:00Z',
            'ExtraField' => 'extra_value',
        ];

        $standardized = $this->service->standardizePayload($this->integration, $rawPayload);

        $this->assertEquals('CW-12345', $standardized['device_id']);
        $this->assertEquals('Test-Server', $standardized['device_name']);
        $this->assertEquals((string)$this->client->id, $standardized['client_id']);
        $this->assertEquals('ALERT-67890', $standardized['alert_id']);
        $this->assertEquals('Service stopped', $standardized['message']);
        $this->assertEquals('urgent', $standardized['severity']); // Mapped from Critical
        $this->assertEquals('Service', $standardized['alert_type']);
        $this->assertEquals($rawPayload, $standardized['raw_payload']);
    }

    /** @test */
    public function it_standardizes_datto_payload_correctly()
    {
        $dattoIntegration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'datto',
        ]);

        $rawPayload = [
            'uid' => 'DATTO-12345',
            'device_name' => 'Backup-Server',
            'site_name' => (string)$this->client->id,
            'alert_uid' => 'DATTO-ALERT-123',
            'alert_message' => 'Backup failed',
            'alert_type' => 'critical',
            'timestamp' => '2023-01-15T10:30:00Z',
        ];

        $standardized = $this->service->standardizePayload($dattoIntegration, $rawPayload);

        $this->assertEquals('DATTO-12345', $standardized['device_id']);
        $this->assertEquals('Backup-Server', $standardized['device_name']);
        $this->assertEquals((string)$this->client->id, $standardized['client_id']);
        $this->assertEquals('DATTO-ALERT-123', $standardized['alert_id']);
        $this->assertEquals('Backup failed', $standardized['message']);
        $this->assertEquals('urgent', $standardized['severity']); // Mapped from critical
    }

    /** @test */
    public function it_standardizes_ninja_payload_correctly()
    {
        $ninjaIntegration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'ninja',
        ]);

        $rawPayload = [
            'deviceId' => 'NINJA-001',
            'deviceName' => 'Office-PC',
            'organizationId' => (string)$this->client->id,
            'alertId' => 'NINJA-ALERT-456',
            'alertMessage' => 'Update failed',
            'alertType' => 'Minor',
            'createdAt' => '2023-01-15T10:30:00Z',
        ];

        $standardized = $this->service->standardizePayload($ninjaIntegration, $rawPayload);

        $this->assertEquals('NINJA-001', $standardized['device_id']);
        $this->assertEquals('Office-PC', $standardized['device_name']);
        $this->assertEquals((string)$this->client->id, $standardized['client_id']);
        $this->assertEquals('NINJA-ALERT-456', $standardized['alert_id']);
        $this->assertEquals('Update failed', $standardized['message']);
        $this->assertEquals('normal', $standardized['severity']); // Mapped from Minor
    }

    /** @test */
    public function it_handles_missing_payload_fields_gracefully()
    {
        $incompletePayload = [
            'ComputerID' => 'CW-12345',
            // Missing other required fields
        ];

        $standardized = $this->service->standardizePayload($this->integration, $incompletePayload);

        // Should provide defaults for missing fields
        $this->assertEquals('CW-12345', $standardized['device_id']);
        $this->assertEquals('Unknown Device', $standardized['device_name']);
        $this->assertEquals('normal', $standardized['severity']); // Default severity
        $this->assertEquals('RMM Alert', $standardized['message']); // Default message
        $this->assertStringStartsWith('alert_', $standardized['alert_id']); // Generated ID
    }

    /** @test */
    public function it_creates_alert_with_correct_attributes()
    {
        $deviceMapping = DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'asset_id' => 123,
        ]);

        $standardizedPayload = [
            'device_id' => 'DEV-001',
            'alert_id' => 'ALERT-123',
            'alert_type' => 'Performance',
            'severity' => 'high',
            'message' => 'High CPU usage',
            'raw_payload' => ['test' => 'data'],
        ];

        $alert = $this->service->createAlert($this->integration, $standardizedPayload, $deviceMapping);

        $this->assertInstanceOf(RMMAlert::class, $alert);
        $this->assertEquals($this->integration->id, $alert->integration_id);
        $this->assertEquals('ALERT-123', $alert->external_alert_id);
        $this->assertEquals('DEV-001', $alert->device_id);
        $this->assertEquals(123, $alert->asset_id);
        $this->assertEquals('Performance', $alert->alert_type);
        $this->assertEquals('high', $alert->severity);
        $this->assertEquals('High CPU usage', $alert->message);
        $this->assertEquals(['test' => 'data'], $alert->raw_payload);
    }

    /** @test */
    public function it_creates_alert_without_device_mapping()
    {
        $standardizedPayload = [
            'device_id' => 'DEV-001',
            'alert_id' => 'ALERT-123',
            'alert_type' => 'Performance',
            'severity' => 'high',
            'message' => 'High CPU usage',
            'raw_payload' => ['test' => 'data'],
        ];

        $alert = $this->service->createAlert($this->integration, $standardizedPayload);

        $this->assertInstanceOf(RMMAlert::class, $alert);
        $this->assertNull($alert->asset_id);
    }

    /** @test */
    public function it_detects_duplicate_alerts()
    {
        $duplicateHash = 'test-duplicate-hash';

        // Create existing alert
        $existingAlert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'duplicate_hash' => $duplicateHash,
            'created_at' => now()->subMinutes(30),
        ]);

        // Create new alert with same hash
        $newAlert = RMMAlert::factory()->make([
            'integration_id' => $this->integration->id,
            'duplicate_hash' => $duplicateHash,
        ]);
        $newAlert->save();

        $isDuplicate = $this->service->isDuplicateAlert($newAlert);
        
        $this->assertTrue($isDuplicate);
    }

    /** @test */
    public function it_does_not_flag_old_alerts_as_duplicates()
    {
        $duplicateHash = 'test-duplicate-hash';

        // Create old alert (outside the duplicate window)
        $oldAlert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'duplicate_hash' => $duplicateHash,
            'created_at' => now()->subHours(2), // Older than 1 hour window
        ]);

        // Create new alert with same hash
        $newAlert = RMMAlert::factory()->make([
            'integration_id' => $this->integration->id,
            'duplicate_hash' => $duplicateHash,
        ]);
        $newAlert->save();

        $isDuplicate = $this->service->isDuplicateAlert($newAlert);
        
        $this->assertFalse($isDuplicate);
    }

    /** @test */
    public function it_handles_device_mapping_creation()
    {
        $standardizedPayload = [
            'device_id' => 'DEV-001',
            'device_name' => 'Test Device',
            'client_id' => (string)$this->client->id,
            'raw_payload' => ['ip' => '192.168.1.100'],
        ];

        $deviceMapping = $this->service->handleDeviceMapping($this->integration, $standardizedPayload);

        $this->assertInstanceOf(DeviceMapping::class, $deviceMapping);
        $this->assertEquals($this->integration->id, $deviceMapping->integration_id);
        $this->assertEquals('DEV-001', $deviceMapping->rmm_device_id);
        $this->assertEquals($this->client->id, $deviceMapping->client_id);
        $this->assertEquals('Test Device', $deviceMapping->device_name);
        $this->assertTrue($deviceMapping->is_active);
        $this->assertArrayHasKey('last_alert', $deviceMapping->sync_data);
        $this->assertArrayHasKey('provider_data', $deviceMapping->sync_data);
    }

    /** @test */
    public function it_handles_device_mapping_with_invalid_client()
    {
        $standardizedPayload = [
            'device_id' => 'DEV-001',
            'device_name' => 'Test Device',
            'client_id' => 'invalid-client-id',
            'raw_payload' => [],
        ];

        $deviceMapping = $this->service->handleDeviceMapping($this->integration, $standardizedPayload);

        // Should return null when client cannot be resolved
        $this->assertNull($deviceMapping);
    }

    /** @test */
    public function it_handles_device_mapping_without_device_info()
    {
        $standardizedPayload = [
            'device_id' => null,
            'client_id' => null,
            'raw_payload' => [],
        ];

        $deviceMapping = $this->service->handleDeviceMapping($this->integration, $standardizedPayload);

        // Should return null when device info is missing
        $this->assertNull($deviceMapping);
    }

    /** @test */
    public function it_uses_custom_field_mappings_when_provided()
    {
        $this->integration->update([
            'field_mappings' => [
                'device_id' => 'CustomDeviceId',
                'device_name' => 'CustomDeviceName',
                'client_id' => 'CustomClientId',
                'alert_id' => 'CustomAlertId',
                'message' => 'CustomMessage',
                'severity' => 'CustomSeverity',
                'timestamp' => 'CustomTimestamp',
            ]
        ]);

        $rawPayload = [
            'CustomDeviceId' => 'CUSTOM-001',
            'CustomDeviceName' => 'Custom Device',
            'CustomClientId' => (string)$this->client->id,
            'CustomAlertId' => 'CUSTOM-ALERT-001',
            'CustomMessage' => 'Custom alert message',
            'CustomSeverity' => 'high',
            'CustomTimestamp' => '2023-01-15T10:30:00Z',
        ];

        $standardized = $this->service->standardizePayload($this->integration, $rawPayload);

        $this->assertEquals('CUSTOM-001', $standardized['device_id']);
        $this->assertEquals('Custom Device', $standardized['device_name']);
        $this->assertEquals((string)$this->client->id, $standardized['client_id']);
        $this->assertEquals('CUSTOM-ALERT-001', $standardized['alert_id']);
        $this->assertEquals('Custom alert message', $standardized['message']);
        $this->assertEquals('high', $standardized['severity']);
    }

    /** @test */
    public function it_resolves_client_by_different_identifiers()
    {
        // Test direct ID resolution
        $directIdPayload = ['client_id' => (string)$this->client->id];
        $resolvedClientId = $this->invokePrivateMethod(
            $this->service, 
            'resolveClientId', 
            [$this->integration, (string)$this->client->id]
        );
        $this->assertEquals($this->client->id, $resolvedClientId);

        // Test name resolution
        $this->client->update(['name' => 'Test Client Name']);
        $resolvedByName = $this->invokePrivateMethod(
            $this->service, 
            'resolveClientId', 
            [$this->integration, 'Test Client Name']
        );
        $this->assertEquals($this->client->id, $resolvedByName);

        // Test company name resolution
        $this->client->update(['company_name' => 'Test Company']);
        $resolvedByCompany = $this->invokePrivateMethod(
            $this->service, 
            'resolveClientId', 
            [$this->integration, 'Test Company']
        );
        $this->assertEquals($this->client->id, $resolvedByCompany);

        // Test RMM ID resolution
        $this->client->update(['rmm_id' => 'RMM-123']);
        $resolvedByRmmId = $this->invokePrivateMethod(
            $this->service, 
            'resolveClientId', 
            [$this->integration, 'RMM-123']
        );
        $this->assertEquals($this->client->id, $resolvedByRmmId);
    }

    /** @test */
    public function it_determines_alert_type_by_provider()
    {
        $testCases = [
            [
                'provider' => 'connectwise',
                'payload' => ['AlertType' => 'System Alert'],
                'expected' => 'System Alert'
            ],
            [
                'provider' => 'datto',
                'payload' => ['alert_type' => 'backup_failed'],
                'expected' => 'backup_failed'
            ],
            [
                'provider' => 'ninja',
                'payload' => ['alertType' => 'Security'],
                'expected' => 'Security'
            ],
            [
                'provider' => 'generic',
                'payload' => ['alert_type' => 'Custom Alert'],
                'expected' => 'Custom Alert'
            ],
        ];

        foreach ($testCases as $case) {
            $alertType = $this->invokePrivateMethod(
                $this->service,
                'determineAlertType',
                [$case['payload'], $case['provider']]
            );
            
            $this->assertEquals($case['expected'], $alertType, 
                "Failed for provider: {$case['provider']}");
        }
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}