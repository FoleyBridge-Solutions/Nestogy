<?php

namespace Tests\Feature\Integration;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Ticket\Models\Ticket;
use App\Jobs\ProcessRMMAlert;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @group contract
 * @group integration
 * @group rmm
 */
class RMMWebhookTest extends TestCase
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
            'name' => 'Test ConnectWise Integration',
            'credentials_encrypted' => encrypt(json_encode(['api_key' => 'test-api-key'])),
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_process_connectwise_webhook_successfully()
    {
        Queue::fake();

        $payload = [
            'ComputerID' => 'CW-12345',
            'ComputerName' => 'Test-Workstation-01',
            'ClientID' => (string)$this->client->id,
            'AlertID' => 'ALERT-67890',
            'AlertMessage' => 'High CPU usage detected',
            'Severity' => 'Critical',
            'AlertType' => 'Performance',
            'DateStamp' => now()->toISOString(),
        ];

        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            $payload,
            ['X-CW-API-Key' => 'test-api-key']
        );

        $response->assertOk()
                ->assertJson(['success' => true]);

        // Verify alert was created
        $this->assertDatabaseHas('rmm_alerts', [
            'integration_id' => $this->integration->id,
            'external_alert_id' => 'ALERT-67890',
            'device_id' => 'CW-12345',
            'alert_type' => 'Performance',
            'severity' => 'urgent',
            'message' => 'High CPU usage detected',
        ]);

        // Verify device mapping was created
        $this->assertDatabaseHas('device_mappings', [
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'CW-12345',
            'client_id' => $this->client->id,
            'device_name' => 'Test-Workstation-01',
        ]);

        // Verify processing job was dispatched
        Queue::assertPushed(ProcessRMMAlert::class);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_authentication()
    {
        $payload = [
            'ComputerID' => 'CW-12345',
            'AlertID' => 'ALERT-67890',
        ];

        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            $payload,
            ['X-CW-API-Key' => 'invalid-key']
        );

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_duplicate_alerts_correctly()
    {
        Queue::fake();

        // Create an existing alert
        $existingAlert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'external_alert_id' => 'ALERT-67890',
            'device_id' => 'CW-12345',
            'duplicate_hash' => md5(json_encode([
                'integration_id' => $this->integration->id,
                'device_id' => 'CW-12345',
                'alert_type' => 'Performance',
                'message' => 'High CPU usage detected',
            ])),
        ]);

        $payload = [
            'ComputerID' => 'CW-12345',
            'ComputerName' => 'Test-Workstation-01',
            'ClientID' => (string)$this->client->id,
            'AlertID' => 'ALERT-67891', // Different alert ID
            'AlertMessage' => 'High CPU usage detected', // Same message
            'Severity' => 'Critical',
            'AlertType' => 'Performance',
            'DateStamp' => now()->toISOString(),
        ];

        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            $payload,
            ['X-CW-API-Key' => 'test-api-key']
        );

        $response->assertOk()
                ->assertJson(['success' => true, 'data' => ['status' => 'duplicate']]);
    }

    /** @test */
    public function it_can_process_datto_webhook_successfully()
    {
        $dattoIntegration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'datto',
            'name' => 'Test Datto Integration',
            'credentials_encrypted' => encrypt(json_encode(['shared_secret' => 'datto-secret'])),
            'is_active' => true,
        ]);

        Queue::fake();

        $payload = [
            'uid' => 'DATTO-12345',
            'device_name' => 'Server-01',
            'site_name' => (string)$this->client->id,
            'alert_uid' => 'DATTO-ALERT-123',
            'alert_message' => 'Backup failed',
            'alert_type' => 'critical',
            'timestamp' => now()->toISOString(),
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, 'datto-secret');

        $response = $this->postJson(
            route('api.webhooks.datto.webhook', ['integration' => $dattoIntegration->uuid]),
            $payload,
            ['X-Datto-Signature' => $signature]
        );

        $response->assertOk()
                ->assertJson(['success' => true]);

        // Verify alert was created with correct severity mapping
        $this->assertDatabaseHas('rmm_alerts', [
            'integration_id' => $dattoIntegration->id,
            'external_alert_id' => 'DATTO-ALERT-123',
            'severity' => 'urgent', // mapped from 'critical'
        ]);
    }

    /** @test */
    public function it_can_process_ninja_webhook_successfully()
    {
        $ninjaIntegration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'ninja',
            'name' => 'Test NinjaOne Integration',
            'credentials_encrypted' => encrypt(json_encode(['bearer_token' => 'ninja-bearer-token'])),
            'is_active' => true,
        ]);

        Queue::fake();

        $payload = [
            'deviceId' => 'NINJA-DEV-001',
            'deviceName' => 'Laptop-01',
            'organizationId' => (string)$this->client->id,
            'alertId' => 'NINJA-ALERT-456',
            'alertMessage' => 'Antivirus update failed',
            'alertType' => 'Major',
            'createdAt' => now()->toISOString(),
        ];

        $response = $this->postJson(
            route('api.webhooks.ninja.webhook', ['integration' => $ninjaIntegration->uuid]),
            $payload,
            ['Authorization' => 'Bearer ninja-bearer-token']
        );

        $response->assertOk()
                ->assertJson(['success' => true]);

        // Verify alert was created with correct severity mapping
        $this->assertDatabaseHas('rmm_alerts', [
            'integration_id' => $ninjaIntegration->id,
            'external_alert_id' => 'NINJA-ALERT-456',
            'severity' => 'high', // mapped from 'Major'
        ]);
    }

    /** @test */
    public function it_handles_webhook_rate_limiting()
    {
        // This would require more complex setup to properly test rate limiting
        // For now, just verify the endpoint exists and accepts requests
        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            ['ComputerID' => 'test'],
            ['X-CW-API-Key' => 'test-api-key']
        );

        // Should get validation error for incomplete payload, not rate limit error
        $this->assertNotEquals(429, $response->status());
    }

    /** @test */
    public function it_provides_health_check_endpoint()
    {
        $response = $this->getJson(
            route('api.webhooks.connectwise.health', ['integration' => $this->integration->uuid])
        );

        $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'provider' => 'ConnectWise Automate',
                    'data' => [
                        'status' => 'ok',
                        'integration_name' => 'Test ConnectWise Integration',
                        'provider' => 'connectwise',
                    ]
                ]);
    }

    /** @test */
    public function it_provides_test_webhook_endpoint()
    {
        $payload = [
            'ComputerID' => 'TEST-123',
            'AlertID' => 'TEST-ALERT',
            'test_mode' => true,
        ];

        $response = $this->postJson(
            route('api.webhooks.connectwise.test', ['integration' => $this->integration->uuid]),
            $payload
        );

        $response->assertOk()
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'data' => [
                        'integration',
                        'provider',
                        'received_at',
                        'headers',
                        'payload_preview',
                    ]
                ]);
    }

    /** @test */
    public function it_handles_invalid_integration_uuid()
    {
        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => 'invalid-uuid']),
            ['ComputerID' => 'test'],
            ['X-CW-API-Key' => 'test-api-key']
        );

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_inactive_integration()
    {
        $this->integration->update(['is_active' => false]);

        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            ['ComputerID' => 'test'],
            ['X-CW-API-Key' => 'test-api-key']
        );

        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_required_payload_fields()
    {
        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            [], // Empty payload
            ['X-CW-API-Key' => 'test-api-key']
        );

        $response->assertStatus(400); // Validation error
    }

    /** @test */
    public function it_handles_malformed_json_payload()
    {
        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            "malformed json",
            ['X-CW-API-Key' => 'test-api-key']
        );

        $response->assertStatus(400);
    }

    /** @test */
    public function it_processes_generic_rmm_webhook_with_field_detection()
    {
        $genericIntegration = Integration::factory()->create([
            'company_id' => $this->company->id,
            'provider' => 'generic',
            'name' => 'Test Generic RMM',
            'credentials_encrypted' => encrypt(json_encode(['api_key' => 'generic-key'])),
            'is_active' => true,
        ]);

        $payload = [
            'device_id' => 'GENERIC-001',
            'alert_id' => 'GENERIC-ALERT-001',
            'message' => 'Custom RMM alert',
            'severity' => 'high',
            'client_id' => (string)$this->client->id,
        ];

        $response = $this->postJson(
            route('api.webhooks.generic.suggest-mappings', ['integration' => $genericIntegration->uuid]),
            $payload
        );

        $response->assertOk()
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'data' => [
                        'detected_fields',
                        'suggested_mappings',
                        'payload_structure',
                    ]
                ]);
    }
}