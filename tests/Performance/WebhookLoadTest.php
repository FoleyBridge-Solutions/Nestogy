<?php

namespace Tests\Performance;

use App\Domains\Integration\Models\Integration;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Performance tests for webhook processing
 * 
 * @group performance
 * @group integration
 * @group slow
 */
class WebhookLoadTest extends TestCase
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
            'name' => 'Load Test Integration',
            'credentials_encrypted' => encrypt(json_encode(['api_key' => 'test-api-key'])),
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
        
        // Fake the queue for performance testing
        Queue::fake();
    }

    /** @test */
    public function it_handles_high_volume_webhook_processing()
    {
        $alertCount = 100;
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        // Generate and process multiple alerts
        for ($i = 1; $i <= $alertCount; $i++) {
            $payload = [
                'ComputerID' => "CW-{$i}",
                'ComputerName' => "Test-Device-{$i}",
                'ClientID' => (string)$this->client->id,
                'AlertID' => "ALERT-{$i}",
                'AlertMessage' => "Test alert message {$i}",
                'Severity' => $this->getRandomSeverity(),
                'AlertType' => 'Performance',
                'DateStamp' => now()->toISOString(),
            ];

            $response = $this->postJson(
                route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
                $payload,
                ['X-CW-API-Key' => 'test-api-key']
            );

            $response->assertOk();

            // Check memory usage every 25 requests
            if ($i % 25 === 0) {
                $currentMemory = memory_get_usage(true);
                $memoryIncrease = $currentMemory - $memoryStart;
                
                // Log memory usage - should not increase dramatically
                echo "\nProcessed {$i} alerts - Memory usage: " . 
                     number_format($memoryIncrease / 1024 / 1024, 2) . " MB increase\n";
                
                // Fail if memory usage grows too much (more than 50MB)
                $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 
                    "Memory usage increased by more than 50MB after processing {$i} alerts");
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $averageTime = ($totalTime / $alertCount) * 1000; // Convert to milliseconds

        // Performance assertions
        $this->assertLessThan(30, $totalTime, 
            "Processing {$alertCount} alerts took more than 30 seconds");
        
        $this->assertLessThan(100, $averageTime, 
            "Average processing time per alert exceeded 100ms");

        // Verify database records were created
        $this->assertEquals($alertCount, DB::table('rmm_alerts')->count());
        $this->assertEquals($alertCount, DB::table('device_mappings')->count());

        echo "\nPerformance Results:\n";
        echo "- Total time: " . number_format($totalTime, 3) . "s\n";
        echo "- Average time per alert: " . number_format($averageTime, 2) . "ms\n";
        echo "- Alerts per second: " . number_format($alertCount / $totalTime, 2) . "\n";
        echo "- Memory increase: " . number_format((memory_get_usage(true) - $memoryStart) / 1024 / 1024, 2) . "MB\n";
    }

    /** @test */
    public function it_handles_concurrent_webhook_requests()
    {
        $batchSize = 10;
        $batches = 5;
        $startTime = microtime(true);

        // Simulate concurrent batches of requests
        for ($batch = 1; $batch <= $batches; $batch++) {
            $batchStartTime = microtime(true);
            
            // Process batch of concurrent requests
            for ($i = 1; $i <= $batchSize; $i++) {
                $alertId = ($batch - 1) * $batchSize + $i;
                
                $payload = [
                    'ComputerID' => "CONCURRENT-{$alertId}",
                    'ComputerName' => "Concurrent-Device-{$alertId}",
                    'ClientID' => (string)$this->client->id,
                    'AlertID' => "CONCURRENT-ALERT-{$alertId}",
                    'AlertMessage' => "Concurrent alert {$alertId}",
                    'Severity' => 'Critical',
                    'AlertType' => 'Performance',
                    'DateStamp' => now()->toISOString(),
                ];

                $response = $this->postJson(
                    route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
                    $payload,
                    ['X-CW-API-Key' => 'test-api-key']
                );

                $response->assertOk();
            }
            
            $batchTime = microtime(true) - $batchStartTime;
            echo "\nBatch {$batch} processed {$batchSize} alerts in " . 
                 number_format($batchTime * 1000, 2) . "ms\n";
        }

        $totalTime = microtime(true) - $startTime;
        $totalAlerts = $batchSize * $batches;

        // Performance assertions for concurrent processing
        $this->assertLessThan(15, $totalTime, 
            "Concurrent processing of {$totalAlerts} alerts took more than 15 seconds");

        echo "\nConcurrent Processing Results:\n";
        echo "- Total alerts processed: {$totalAlerts}\n";
        echo "- Total time: " . number_format($totalTime, 3) . "s\n";
        echo "- Throughput: " . number_format($totalAlerts / $totalTime, 2) . " alerts/second\n";
    }

    /** @test */
    public function it_handles_large_payload_processing()
    {
        $startTime = microtime(true);

        // Create a large payload with lots of additional data
        $largePayload = [
            'ComputerID' => 'LARGE-PAYLOAD-001',
            'ComputerName' => 'Large-Payload-Device',
            'ClientID' => (string)$this->client->id,
            'AlertID' => 'LARGE-ALERT-001',
            'AlertMessage' => 'Large payload test alert',
            'Severity' => 'High',
            'AlertType' => 'Performance',
            'DateStamp' => now()->toISOString(),
            'AdditionalData' => [
                'system_info' => array_fill(0, 100, 'System data entry'),
                'metrics' => array_fill(0, 200, ['cpu' => 85.5, 'memory' => 76.2, 'disk' => 45.1]),
                'logs' => array_fill(0, 50, str_repeat('Log entry data ', 20)),
                'processes' => array_fill(0, 150, [
                    'name' => 'Process Name',
                    'pid' => rand(1000, 9999),
                    'cpu' => rand(0, 100),
                    'memory' => rand(1024, 1048576),
                ]),
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
            $largePayload,
            ['X-CW-API-Key' => 'test-api-key']
        );

        $processingTime = microtime(true) - $startTime;

        $response->assertOk();

        // Should handle large payloads within reasonable time
        $this->assertLessThan(2, $processingTime, 
            "Large payload processing took more than 2 seconds");

        // Verify the large payload was stored correctly
        $alert = DB::table('rmm_alerts')
            ->where('external_alert_id', 'LARGE-ALERT-001')
            ->first();

        $this->assertNotNull($alert);
        $this->assertIsString($alert->raw_payload);
        
        $decodedPayload = json_decode($alert->raw_payload, true);
        $this->assertArrayHasKey('AdditionalData', $decodedPayload);

        echo "\nLarge Payload Results:\n";
        echo "- Processing time: " . number_format($processingTime * 1000, 2) . "ms\n";
        echo "- Payload size: " . number_format(strlen(json_encode($largePayload)) / 1024, 2) . "KB\n";
    }

    /** @test */
    public function it_maintains_performance_with_duplicate_detection()
    {
        $alertCount = 50;
        $duplicatePercentage = 30; // 30% duplicates
        $duplicateCount = intval($alertCount * $duplicatePercentage / 100);

        $startTime = microtime(true);

        // Create initial alerts
        for ($i = 1; $i <= $alertCount - $duplicateCount; $i++) {
            $payload = [
                'ComputerID' => "PERF-{$i}",
                'ComputerName' => "Perf-Device-{$i}",
                'ClientID' => (string)$this->client->id,
                'AlertID' => "PERF-ALERT-{$i}",
                'AlertMessage' => "Performance test alert {$i}",
                'Severity' => 'High',
                'AlertType' => 'Performance',
                'DateStamp' => now()->toISOString(),
            ];

            $response = $this->postJson(
                route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
                $payload,
                ['X-CW-API-Key' => 'test-api-key']
            );

            $response->assertOk();
        }

        // Create duplicate alerts
        for ($i = 1; $i <= $duplicateCount; $i++) {
            $payload = [
                'ComputerID' => "PERF-{$i}", // Same device
                'ComputerName' => "Perf-Device-{$i}",
                'ClientID' => (string)$this->client->id,
                'AlertID' => "PERF-ALERT-DUPLICATE-{$i}", // Different alert ID
                'AlertMessage' => "Performance test alert {$i}", // Same message (creates duplicate)
                'Severity' => 'High',
                'AlertType' => 'Performance',
                'DateStamp' => now()->toISOString(),
            ];

            $response = $this->postJson(
                route('api.webhooks.connectwise.webhook', ['integration' => $this->integration->uuid]),
                $payload,
                ['X-CW-API-Key' => 'test-api-key']
            );

            $response->assertOk()
                    ->assertJson(['data' => ['status' => 'duplicate']]);
        }

        $totalTime = microtime(true) - $startTime;
        $averageTime = ($totalTime / $alertCount) * 1000;

        // Performance should not degrade significantly with duplicate detection
        $this->assertLessThan(50, $averageTime, 
            "Average processing time with duplicate detection exceeded 50ms");

        // Verify duplicate detection worked
        $totalAlerts = DB::table('rmm_alerts')->count();
        $duplicateAlerts = DB::table('rmm_alerts')->where('is_duplicate', true)->count();

        $this->assertEquals($alertCount, $totalAlerts);
        $this->assertEquals($duplicateCount, $duplicateAlerts);

        echo "\nDuplicate Detection Performance:\n";
        echo "- Total alerts processed: {$alertCount}\n";
        echo "- Duplicates detected: {$duplicateCount}\n";
        echo "- Average time per alert: " . number_format($averageTime, 2) . "ms\n";
        echo "- Total processing time: " . number_format($totalTime, 3) . "s\n";
    }

    /**
     * Get random severity for testing
     */
    private function getRandomSeverity(): string
    {
        $severities = ['Critical', 'High', 'Medium', 'Low'];
        return $severities[array_rand($severities)];
    }
}