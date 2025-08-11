<?php

namespace Tests\Feature\Integration;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Services\RMMIntegrationService;
use App\Domains\Ticket\Models\Ticket;
use App\Jobs\ProcessRMMAlert;
use App\Jobs\AutoAssignTicket;
use App\Jobs\NotifyClientOfRMMAlert;
use App\Jobs\CheckTicketEscalation;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @group contract
 * @group integration
 * @group tickets
 */
class AlertToTicketTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Client $client;
    private Integration $integration;
    private RMMIntegrationService $rmmService;

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
            'name' => 'Test Integration',
            'alert_rules' => [
                'auto_create_tickets' => true,
                'auto_assign_technician' => true,
                'notify_client' => true,
                'severity_mapping' => [
                    'Critical' => 'urgent',
                    'High' => 'high',
                    'Medium' => 'normal',
                    'Low' => 'low',
                ],
            ],
            'is_active' => true,
        ]);

        $this->rmmService = app(RMMIntegrationService::class);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_creates_ticket_from_rmm_alert()
    {
        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'external_alert_id' => 'ALERT-123',
            'device_id' => 'DEV-001',
            'alert_type' => 'Performance',
            'severity' => 'urgent',
            'message' => 'High CPU usage detected',
        ]);

        $ticket = $this->rmmService->convertAlertToTicket($alert);

        $this->assertNotNull($ticket);
        $this->assertInstanceOf(Ticket::class, $ticket);
        
        // Verify ticket properties
        $this->assertEquals($this->integration->company_id, $ticket->company_id);
        $this->assertEquals('Urgent', $ticket->priority);
        $this->assertEquals('Open', $ticket->status);
        $this->assertEquals('RMM Integration', $ticket->source);
        $this->assertEquals('Infrastructure', $ticket->category);
        $this->assertStringContains('Performance', $ticket->title);
        $this->assertStringContains('High CPU usage detected', $ticket->description);

        // Verify alert is linked to ticket
        $alert->refresh();
        $this->assertEquals($ticket->id, $alert->ticket_id);
    }

    /** @test */
    public function it_maps_severity_to_priority_correctly()
    {
        $testCases = [
            'urgent' => 'Urgent',
            'high' => 'High',
            'normal' => 'Normal',
            'low' => 'Low',
        ];

        foreach ($testCases as $severity => $expectedPriority) {
            $alert = RMMAlert::factory()->create([
                'integration_id' => $this->integration->id,
                'severity' => $severity,
            ]);

            $ticket = $this->rmmService->convertAlertToTicket($alert);
            
            $this->assertEquals($expectedPriority, $ticket->priority);
        }
    }

    /** @test */
    public function it_links_alert_to_existing_asset()
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'asset_id' => $asset->id,
        ]);

        $ticket = $this->rmmService->convertAlertToTicket($alert);

        $this->assertEquals($asset->id, $ticket->asset_id);
    }

    /** @test */
    public function it_dispatches_follow_up_jobs_when_processing_alert()
    {
        Queue::fake();

        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'severity' => 'urgent',
        ]);

        $job = new ProcessRMMAlert($alert);
        $job->handle($this->rmmService);

        // Verify follow-up jobs were dispatched
        Queue::assertPushed(AutoAssignTicket::class);
        Queue::assertPushed(NotifyClientOfRMMAlert::class);
        Queue::assertPushed(CheckTicketEscalation::class);
    }

    /** @test */
    public function it_skips_ticket_creation_when_auto_create_is_disabled()
    {
        $this->integration->update([
            'alert_rules' => [
                'auto_create_tickets' => false,
            ]
        ]);

        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
        ]);

        $ticket = $this->rmmService->convertAlertToTicket($alert);

        $this->assertNull($ticket);
        $this->assertNull($alert->ticket_id);
    }

    /** @test */
    public function it_skips_duplicate_alerts()
    {
        $duplicateHash = 'duplicate-hash-123';

        // Create first alert
        $firstAlert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'duplicate_hash' => $duplicateHash,
            'created_at' => now()->subMinutes(30),
        ]);

        // Create second alert with same hash (duplicate)
        $secondAlert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'duplicate_hash' => $duplicateHash,
            'created_at' => now(),
        ]);

        $isDuplicate = $this->rmmService->isDuplicateAlert($secondAlert);
        
        $this->assertTrue($isDuplicate);
    }

    /** @test */
    public function it_generates_proper_ticket_title_and_description()
    {
        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'device_id' => 'SERVER-001',
            'alert_type' => 'Disk Space',
            'severity' => 'high',
            'message' => 'Disk space low on C: drive (15% remaining)',
        ]);

        $ticket = $this->rmmService->convertAlertToTicket($alert);

        // Check title format
        $expectedTitle = '[High] Disk Space - SERVER-001';
        $this->assertEquals($expectedTitle, $ticket->title);

        // Check description contains key information
        $this->assertStringContains('Device: SERVER-001', $ticket->description);
        $this->assertStringContains('Alert Type: Disk Space', $ticket->description);
        $this->assertStringContains('Severity: High', $ticket->description);
        $this->assertStringContains('Message: Disk space low on C: drive (15% remaining)', $ticket->description);
        $this->assertStringContains('Integration: Test Integration', $ticket->description);
        $this->assertStringContains('automatically created from an RMM system alert', $ticket->description);
    }

    /** @test */
    public function it_handles_client_resolution_from_device_mapping()
    {
        // Create device mapping
        $deviceMapping = \App\Domains\Integration\Models\DeviceMapping::factory()->create([
            'integration_id' => $this->integration->id,
            'rmm_device_id' => 'DEV-001',
            'client_id' => $this->client->id,
        ]);

        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'device_id' => 'DEV-001',
        ]);

        $ticket = $this->rmmService->convertAlertToTicket($alert);

        $this->assertEquals($this->client->id, $ticket->client_id);
    }

    /** @test */
    public function it_handles_missing_client_gracefully()
    {
        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'device_id' => 'UNKNOWN-DEVICE',
        ]);

        $ticket = $this->rmmService->convertAlertToTicket($alert);

        // Should fail to create ticket without valid client
        $this->assertNull($ticket);
    }

    /** @test */
    public function it_processes_alert_payload_standardization()
    {
        $rawPayload = [
            'ComputerID' => 'CW-12345',
            'ComputerName' => 'Test-Server',
            'ClientID' => (string)$this->client->id,
            'AlertID' => 'ALERT-67890',
            'AlertMessage' => 'Service stopped unexpectedly',
            'Severity' => 'Critical',
            'AlertType' => 'Service',
            'DateStamp' => '2023-01-15T10:30:00Z',
        ];

        $standardized = $this->rmmService->standardizePayload($this->integration, $rawPayload);

        $this->assertEquals('CW-12345', $standardized['device_id']);
        $this->assertEquals('Test-Server', $standardized['device_name']);
        $this->assertEquals((string)$this->client->id, $standardized['client_id']);
        $this->assertEquals('ALERT-67890', $standardized['alert_id']);
        $this->assertEquals('Service stopped unexpectedly', $standardized['message']);
        $this->assertEquals('urgent', $standardized['severity']); // Mapped from 'Critical'
        $this->assertEquals('Service', $standardized['alert_type']);
        $this->assertEquals($rawPayload, $standardized['raw_payload']);
    }

    /** @test */
    public function it_handles_processing_job_retry_logic()
    {
        Queue::fake();

        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'processed_at' => null,
        ]);

        $job = new ProcessRMMAlert($alert);

        // Simulate job processing
        $job->handle($this->rmmService);

        // Verify alert is marked as processed
        $alert->refresh();
        $this->assertNotNull($alert->processed_at);
    }

    /** @test */
    public function it_skips_processing_already_processed_alerts()
    {
        $alert = RMMAlert::factory()->create([
            'integration_id' => $this->integration->id,
            'processed_at' => now()->subHour(),
            'ticket_id' => Ticket::factory()->create()->id,
        ]);

        $job = new ProcessRMMAlert($alert);
        $initialProcessedAt = $alert->processed_at;

        $job->handle($this->rmmService);

        // Verify processing time wasn't changed
        $alert->refresh();
        $this->assertEquals($initialProcessedAt->timestamp, $alert->processed_at->timestamp);
    }

    /** @test */
    public function it_creates_proper_ticket_categories_for_different_alert_types()
    {
        $testCases = [
            'Performance' => ['Infrastructure', 'Monitoring'],
            'Service' => ['Infrastructure', 'Services'],
            'Security' => ['Security', 'Monitoring'],
            'Backup' => ['Infrastructure', 'Backup'],
        ];

        foreach ($testCases as $alertType => $expectedCategories) {
            $alert = RMMAlert::factory()->create([
                'integration_id' => $this->integration->id,
                'alert_type' => $alertType,
            ]);

            $ticket = $this->rmmService->convertAlertToTicket($alert);

            $this->assertEquals($expectedCategories[0], $ticket->category);
            $this->assertEquals($expectedCategories[1], $ticket->subcategory);
        }
    }
}