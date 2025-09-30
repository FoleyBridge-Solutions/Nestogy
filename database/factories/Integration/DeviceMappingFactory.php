<?php

namespace Database\Factories\Integration;

use App\Domains\Integration\Models\DeviceMapping;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceMappingFactory extends Factory
{
    protected $model = DeviceMapping::class;

    public function definition(): array
    {
        $deviceTypes = ['Server', 'Workstation', 'Laptop', 'Router', 'Switch', 'Firewall', 'Printer', 'NAS'];
        $osTypes = ['Windows', 'Linux', 'macOS', 'Router OS', 'Printer OS'];

        $deviceType = $this->faker->randomElement($deviceTypes);
        $deviceName = $deviceType.'-'.$this->faker->numberBetween(1, 999);

        return [
            'uuid' => Str::uuid(),
            'integration_id' => 1, // Will be overridden in tests
            'rmm_device_id' => 'RMM-'.$this->faker->numerify('######'),
            'asset_id' => $this->faker->optional(0.7)->numberBetween(1, 1000),
            'client_id' => 1, // Will be overridden in tests
            'device_name' => $deviceName,
            'sync_data' => $this->generateSyncData($deviceType),
            'last_updated' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function mapped(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => $this->faker->numberBetween(1, 1000),
        ]);
    }

    public function unmapped(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => null,
        ]);
    }

    public function fresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_updated' => now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_updated' => now()->subHours($this->faker->numberBetween(48, 168)), // 2-7 days old
        ]);
    }

    public function server(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_name' => 'Server-'.$this->faker->numberBetween(1, 99),
            'sync_data' => $this->generateSyncData('Server'),
        ]);
    }

    public function workstation(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_name' => 'Workstation-'.$this->faker->numberBetween(1, 999),
            'sync_data' => $this->generateSyncData('Workstation'),
        ]);
    }

    public function laptop(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_name' => 'Laptop-'.$this->faker->numberBetween(1, 999),
            'sync_data' => $this->generateSyncData('Laptop'),
        ]);
    }

    public function networkDevice(): static
    {
        $deviceType = $this->faker->randomElement(['Router', 'Switch', 'Firewall']);

        return $this->state(fn (array $attributes) => [
            'device_name' => $deviceType.'-'.$this->faker->numberBetween(1, 99),
            'sync_data' => $this->generateSyncData($deviceType),
        ]);
    }

    public function withRecentActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_data' => array_merge(
                $this->generateSyncData('Workstation'),
                [
                    'last_alert' => now()->subMinutes($this->faker->numberBetween(1, 60))->toISOString(),
                    'recent_activity' => true,
                ]
            ),
        ]);
    }

    protected function generateSyncData(string $deviceType): array
    {
        $baseData = [
            'device_type' => $deviceType,
            'ip_address' => $this->faker->localIpv4,
            'mac_address' => $this->faker->macAddress,
            'last_seen' => $this->faker->dateTimeBetween('-24 hours', 'now')->toISOString(),
            'uptime_hours' => $this->faker->numberBetween(1, 8760),
            'domain' => $this->faker->optional(0.8)->domainName,
        ];

        switch ($deviceType) {
            case 'Server':
                return array_merge($baseData, [
                    'os_name' => $this->faker->randomElement(['Windows Server 2019', 'Windows Server 2022', 'Ubuntu Server 20.04', 'CentOS 8']),
                    'cpu_cores' => $this->faker->numberBetween(4, 32),
                    'ram_gb' => $this->faker->randomElement([8, 16, 32, 64, 128]),
                    'disk_space_gb' => $this->faker->randomElement([500, 1000, 2000, 4000]),
                    'server_roles' => $this->faker->randomElements(['Domain Controller', 'File Server', 'Database', 'Web Server'], $this->faker->numberBetween(1, 3)),
                ]);

            case 'Workstation':
            case 'Laptop':
                return array_merge($baseData, [
                    'os_name' => $this->faker->randomElement(['Windows 10', 'Windows 11', 'macOS Monterey', 'Ubuntu 22.04']),
                    'cpu_cores' => $this->faker->numberBetween(2, 8),
                    'ram_gb' => $this->faker->randomElement([4, 8, 16, 32]),
                    'disk_space_gb' => $this->faker->randomElement([256, 512, 1000]),
                    'user' => $this->faker->userName,
                    'antivirus' => $this->faker->randomElement(['Windows Defender', 'Symantec', 'McAfee', 'Bitdefender']),
                ]);

            case 'Router':
            case 'Switch':
                return array_merge($baseData, [
                    'firmware_version' => $this->faker->numerify('##.##.##'),
                    'port_count' => $this->faker->numberBetween(8, 48),
                    'model' => $this->faker->randomElement(['Cisco 2960', 'Netgear GS724T', 'HP ProCurve 2810']),
                    'snmp_enabled' => $this->faker->boolean,
                ]);

            case 'Firewall':
                return array_merge($baseData, [
                    'firmware_version' => $this->faker->numerify('##.##.##'),
                    'model' => $this->faker->randomElement(['SonicWall TZ370', 'Fortinet FortiGate 60F', 'pfSense']),
                    'vpn_enabled' => $this->faker->boolean,
                    'intrusion_prevention' => $this->faker->boolean,
                ]);

            case 'Printer':
                return array_merge($baseData, [
                    'model' => $this->faker->randomElement(['HP LaserJet Pro', 'Canon imageCLASS', 'Xerox WorkCentre']),
                    'print_count' => $this->faker->numberBetween(1000, 100000),
                    'toner_level' => $this->faker->numberBetween(10, 100).'%',
                    'duplex_enabled' => $this->faker->boolean,
                ]);

            default:
                return $baseData;
        }
    }
}
