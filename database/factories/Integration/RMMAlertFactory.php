<?php

namespace Database\Factories\Integration;

use App\Domains\Integration\Models\RMMAlert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RMMAlertFactory extends Factory
{
    protected $model = RMMAlert::class;

    public function definition(): array
    {
        $severity = $this->faker->randomElement(['urgent', 'high', 'normal', 'low']);
        $alertType = $this->faker->randomElement([
            'Performance', 'Service', 'Security', 'Backup', 'Disk Space',
            'Network', 'Hardware', 'Software', 'Monitoring', 'Maintenance',
        ]);

        $deviceId = 'DEV-'.$this->faker->numberBetween(1000, 9999);
        $message = $this->generateAlertMessage($alertType);

        return [
            'uuid' => Str::uuid(),
            'integration_id' => 1, // Will be overridden in tests
            'external_alert_id' => 'ALERT-'.$this->faker->numerify('######'),
            'device_id' => $deviceId,
            'asset_id' => $this->faker->optional(0.6)->numberBetween(1, 100),
            'alert_type' => $alertType,
            'severity' => $severity,
            'message' => $message,
            'raw_payload' => $this->generateRawPayload($deviceId, $alertType, $severity, $message),
            'processed_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'ticket_id' => $this->faker->optional(0.6)->numberBetween(1, 1000),
            'is_duplicate' => $this->faker->boolean(10), // 10% chance of duplicate
            'duplicate_hash' => null, // Will be set by model
        ];
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'urgent',
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'high',
        ]);
    }

    public function normal(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'normal',
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'low',
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_at' => now()->subMinutes($this->faker->numberBetween(1, 1440)),
        ]);
    }

    public function unprocessed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_at' => null,
        ]);
    }

    public function duplicate(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_duplicate' => true,
        ]);
    }

    public function withTicket(): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => $this->faker->numberBetween(1, 1000),
            'processed_at' => now()->subMinutes($this->faker->numberBetween(1, 1440)),
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ]);
    }

    public function performance(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'Performance',
            'message' => $this->faker->randomElement([
                'High CPU usage detected (95%)',
                'Memory usage is critical (98%)',
                'Disk I/O is extremely high',
                'Network latency is elevated',
                'System performance is degraded',
            ]),
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'Service',
            'message' => $this->faker->randomElement([
                'SQL Server service has stopped',
                'IIS service is not responding',
                'Print Spooler service failed to start',
                'Antivirus service is disabled',
                'Backup service encountered an error',
            ]),
        ]);
    }

    public function security(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'Security',
            'severity' => $this->faker->randomElement(['urgent', 'high']),
            'message' => $this->faker->randomElement([
                'Multiple failed login attempts detected',
                'Antivirus definitions are outdated',
                'Firewall rule violation detected',
                'Suspicious network activity',
                'Security patch missing',
            ]),
        ]);
    }

    protected function generateAlertMessage(string $alertType): string
    {
        $messages = [
            'Performance' => [
                'High CPU usage detected',
                'Memory usage is critical',
                'Disk space is running low',
                'Network performance is degraded',
                'System response time is slow',
            ],
            'Service' => [
                'Service has stopped unexpectedly',
                'Service failed to start',
                'Service is not responding',
                'Service crashed and restarted',
                'Service timeout occurred',
            ],
            'Security' => [
                'Security vulnerability detected',
                'Unauthorized access attempt',
                'Malware signature found',
                'Firewall breach detected',
                'Security policy violation',
            ],
            'Backup' => [
                'Backup job failed',
                'Backup destination unreachable',
                'Backup verification failed',
                'Backup schedule missed',
                'Backup storage full',
            ],
            'Disk Space' => [
                'Disk space low on C: drive',
                'Partition is nearly full',
                'Storage threshold exceeded',
                'Disk cleanup required',
                'Free space critical',
            ],
            'Network' => [
                'Network connectivity lost',
                'High network utilization',
                'Network timeout occurred',
                'DNS resolution failed',
                'Network adapter error',
            ],
            'Hardware' => [
                'Hardware failure detected',
                'Temperature threshold exceeded',
                'Fan speed is abnormal',
                'Power supply issue',
                'Memory module error',
            ],
            'Software' => [
                'Application crashed',
                'Software update failed',
                'License expiration warning',
                'Configuration error detected',
                'Software conflict found',
            ],
        ];

        $typeMessages = $messages[$alertType] ?? ['System alert'];

        return $this->faker->randomElement($typeMessages);
    }

    protected function generateRawPayload(string $deviceId, string $alertType, string $severity, string $message): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'source' => 'RMM System',
            'device_id' => $deviceId,
            'alert_type' => $alertType,
            'severity' => $severity,
            'message' => $message,
            'additional_data' => [
                'cpu_usage' => $this->faker->numberBetween(10, 100),
                'memory_usage' => $this->faker->numberBetween(20, 95),
                'disk_usage' => $this->faker->numberBetween(30, 90),
                'uptime' => $this->faker->numberBetween(1, 8760).' hours',
            ],
        ];
    }
}
