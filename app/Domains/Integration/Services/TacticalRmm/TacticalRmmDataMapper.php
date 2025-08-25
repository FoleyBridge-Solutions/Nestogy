<?php

namespace App\Domains\Integration\Services\TacticalRmm;

use Carbon\Carbon;

/**
 * Tactical RMM Data Mapper
 * 
 * Maps data from Tactical RMM API responses to standardized internal format.
 * Provides consistent data structure across different RMM providers.
 */
class TacticalRmmDataMapper
{
    /**
     * Map array of agents to standardized format.
     */
    public function mapAgents(array $agents): array
    {
        return array_map([$this, 'mapAgent'], $agents);
    }

    /**
     * Map single agent to standardized format.
     */
    public function mapAgent(array $agent): array
    {
        return [
            'id' => $agent['agent_id'] ?? $agent['id'],
            'hostname' => $agent['hostname'] ?? 'Unknown',
            'client' => $agent['client_name'] ?? $agent['client'] ?? 'Unknown',
            'site' => $agent['site_name'] ?? $agent['site'] ?? 'Unknown',
            'operating_system' => $agent['operating_system'] ?? 'Unknown',
            'platform' => $agent['plat'] ?? 'Unknown',
            'architecture' => $agent['arch'] ?? 'Unknown',
            'version' => $agent['version'] ?? 'Unknown',
            'online' => $agent['status'] === 'online' ?? false,
            'last_seen' => $this->parseDateTime($agent['last_seen'] ?? null),
            'public_ip' => $agent['public_ip'] ?? null,
            'local_ip' => $agent['local_ip'] ?? null,
            'mac_address' => $agent['mac_address'] ?? null,
            'cpu_info' => $agent['cpu'] ?? null,
            'total_ram' => $agent['total_ram'] ?? null,
            'boot_time' => $this->parseDateTime($agent['boot_time'] ?? null),
            'logged_in_username' => $agent['logged_in_username'] ?? null,
            'timezone' => $agent['timezone'] ?? null,
            'monitoring_type' => $agent['monitoring_type'] ?? 'workstation',
            'description' => $agent['description'] ?? null,
            'mesh_node_id' => $agent['mesh_node_id'] ?? null,
            'antivirus' => $agent['antivirus'] ?? null,
            'needs_reboot' => $agent['needs_reboot'] ?? false,
            'pending_actions_count' => $agent['pending_actions_count'] ?? 0,
            'overdue_email_alert' => $agent['overdue_email_alert'] ?? false,
            'overdue_text_alert' => $agent['overdue_text_alert'] ?? false,
            'overdue_dashboard_alert' => $agent['overdue_dashboard_alert'] ?? false,
            'maintenance_mode' => $agent['maintenance_mode'] ?? false,
            'raw_data' => $agent,
        ];
    }

    /**
     * Map array of clients to standardized format.
     */
    public function mapClients(array $clients): array
    {
        return array_map([$this, 'mapClient'], $clients);
    }

    /**
     * Map single client to standardized format.
     */
    public function mapClient(array $client): array
    {
        return [
            'id' => $client['id'],
            'name' => $client['name'] ?? 'Unknown',
            'sites_count' => count($client['sites'] ?? []),
            'agents_count' => $client['agents_count'] ?? 0,
            'creation_time' => $this->parseDateTime($client['creation_time'] ?? null),
            'custom_fields' => $client['custom_fields'] ?? [],
            'raw_data' => $client,
        ];
    }

    /**
     * Map array of sites to standardized format.
     */
    public function mapSites(array $sites): array
    {
        return array_map([$this, 'mapSite'], $sites);
    }

    /**
     * Map single site to standardized format.
     */
    public function mapSite(array $site): array
    {
        return [
            'id' => $site['id'],
            'name' => $site['name'] ?? 'Unknown',
            'client_id' => $site['client'] ?? null,
            'agents_count' => $site['agents_count'] ?? 0,
            'creation_time' => $this->parseDateTime($site['creation_time'] ?? null),
            'custom_fields' => $site['custom_fields'] ?? [],
            'raw_data' => $site,
        ];
    }

    /**
     * Map array of alerts to standardized format.
     */
    public function mapAlerts(array $alerts): array
    {
        return array_map([$this, 'mapAlert'], $alerts);
    }

    /**
     * Map single alert to standardized format.
     */
    public function mapAlert(array $alert): array
    {
        return [
            'id' => $alert['id'],
            'agent_id' => $alert['agent'] ?? null,
            'agent_hostname' => $alert['agent_hostname'] ?? 'Unknown',
            'client' => $alert['client'] ?? 'Unknown',
            'site' => $alert['site'] ?? 'Unknown',
            'alert_type' => $alert['alert_type'] ?? 'system',
            'severity' => $this->normalizeSeverity($alert['severity'] ?? 'info'),
            'message' => $alert['message'] ?? 'No message',
            'created_time' => $this->parseDateTime($alert['created_time'] ?? null),
            'resolved' => $alert['resolved'] ?? false,
            'resolved_time' => $this->parseDateTime($alert['resolved_time'] ?? null),
            'snoozed' => $alert['snoozed'] ?? false,
            'snooze_until' => $this->parseDateTime($alert['snooze_until'] ?? null),
            'hidden' => $alert['hidden'] ?? false,
            'assigned_check' => $alert['assigned_check'] ?? null,
            'raw_data' => $alert,
        ];
    }

    /**
     * Map array of checks to standardized format.
     */
    public function mapChecks(array $checks): array
    {
        return array_map([$this, 'mapCheck'], $checks);
    }

    /**
     * Map single check to standardized format.
     */
    public function mapCheck(array $check): array
    {
        return [
            'id' => $check['id'],
            'agent_id' => $check['agent'] ?? null,
            'check_type' => $check['check_type'] ?? 'unknown',
            'name' => $check['name'] ?? 'Unknown Check',
            'status' => $this->normalizeCheckStatus($check['status'] ?? 'unknown'),
            'more_info' => $check['more_info'] ?? null,
            'last_run' => $this->parseDateTime($check['last_run'] ?? null),
            'email_alert' => $check['email_alert'] ?? false,
            'text_alert' => $check['text_alert'] ?? false,
            'dashboard_alert' => $check['dashboard_alert'] ?? false,
            'history' => $check['history'] ?? [],
            'raw_data' => $check,
        ];
    }

    /**
     * Map agent system information to standardized format.
     */
    public function mapAgentInfo(array $agentData): array
    {
        return [
            'system_info' => [
                'hostname' => $agentData['hostname'] ?? 'Unknown',
                'operating_system' => $agentData['operating_system'] ?? 'Unknown',
                'platform' => $agentData['plat'] ?? 'Unknown',
                'architecture' => $agentData['arch'] ?? 'Unknown',
                'version' => $agentData['version'] ?? 'Unknown',
                'total_ram' => $agentData['total_ram'] ?? null,
                'cpu_info' => $agentData['cpu'] ?? null,
                'timezone' => $agentData['timezone'] ?? null,
            ],
            'network_info' => [
                'public_ip' => $agentData['public_ip'] ?? null,
                'local_ip' => $agentData['local_ip'] ?? null,
                'mac_address' => $agentData['mac_address'] ?? null,
            ],
            'status_info' => [
                'online' => $agentData['status'] === 'online' ?? false,
                'last_seen' => $this->parseDateTime($agentData['last_seen'] ?? null),
                'boot_time' => $this->parseDateTime($agentData['boot_time'] ?? null),
                'logged_in_username' => $agentData['logged_in_username'] ?? null,
                'needs_reboot' => $agentData['needs_reboot'] ?? false,
                'maintenance_mode' => $agentData['maintenance_mode'] ?? false,
            ],
            'security_info' => [
                'antivirus' => $agentData['antivirus'] ?? null,
            ],
            'raw_data' => $agentData,
        ];
    }

    /**
     * Map installed software to standardized format.
     */
    public function mapSoftware(array $software): array
    {
        return array_map(function ($item) {
            return [
                'name' => $item['name'] ?? 'Unknown',
                'version' => $item['version'] ?? 'Unknown',
                'publisher' => $item['publisher'] ?? 'Unknown',
                'install_date' => $this->parseDateTime($item['install_date'] ?? null),
                'size' => $item['size'] ?? null,
                'location' => $item['location'] ?? null,
                'raw_data' => $item,
            ];
        }, $software);
    }

    /**
     * Map services to standardized format.
     */
    public function mapServices(array $services): array
    {
        return array_map(function ($service) {
            return [
                'name' => $service['name'] ?? 'Unknown',
                'display_name' => $service['display_name'] ?? $service['name'] ?? 'Unknown',
                'status' => $this->normalizeServiceStatus($service['status'] ?? 'unknown'),
                'start_type' => $service['start_type'] ?? 'unknown',
                'pid' => $service['pid'] ?? null,
                'username' => $service['username'] ?? null,
                'description' => $service['description'] ?? null,
                'binpath' => $service['binpath'] ?? null,
                'raw_data' => $service,
            ];
        }, $services);
    }

    /**
     * Map event logs to standardized format.
     */
    public function mapEventLogs(array $logs): array
    {
        return array_map(function ($log) {
            return [
                'event_id' => $log['eventid'] ?? null,
                'level' => $this->normalizeLogLevel($log['level'] ?? 'info'),
                'source' => $log['source'] ?? 'Unknown',
                'message' => $log['message'] ?? 'No message',
                'time_generated' => $this->parseDateTime($log['time_generated'] ?? null),
                'computer' => $log['computer'] ?? 'Unknown',
                'user' => $log['user'] ?? null,
                'raw_data' => $log,
            ];
        }, $logs);
    }

    /**
     * Map webhook payload to standardized format.
     */
    public function mapWebhookPayload(array $payload): array
    {
        // Determine the type of webhook (alert, agent status change, etc.)
        $webhookType = $this->determineWebhookType($payload);

        switch ($webhookType) {
            case 'alert':
                return [
                    'type' => 'alert',
                    'data' => $this->mapAlert($payload),
                ];

            case 'agent_status':
                return [
                    'type' => 'agent_status',
                    'data' => [
                        'agent_id' => $payload['agent_id'] ?? null,
                        'hostname' => $payload['hostname'] ?? 'Unknown',
                        'status' => $payload['status'] ?? 'unknown',
                        'timestamp' => $this->parseDateTime($payload['timestamp'] ?? null),
                    ],
                ];

            default:
                return [
                    'type' => 'unknown',
                    'data' => $payload,
                ];
        }
    }

    /**
     * Normalize severity from Tactical RMM to internal format.
     */
    protected function normalizeSeverity(string $severity): string
    {
        $mapping = [
            'critical' => 'urgent',
            'error' => 'high',
            'warning' => 'high',
            'info' => 'normal',
            'debug' => 'low',
        ];

        return $mapping[strtolower($severity)] ?? 'normal';
    }

    /**
     * Normalize check status to standard format.
     */
    protected function normalizeCheckStatus(string $status): string
    {
        $mapping = [
            'passing' => 'healthy',
            'failing' => 'unhealthy',
            'pending' => 'pending',
        ];

        return $mapping[strtolower($status)] ?? 'unknown';
    }

    /**
     * Normalize service status to standard format.
     */
    protected function normalizeServiceStatus(string $status): string
    {
        $mapping = [
            'running' => 'running',
            'stopped' => 'stopped',
            'paused' => 'paused',
            'pending' => 'pending',
        ];

        return $mapping[strtolower($status)] ?? 'unknown';
    }

    /**
     * Normalize log level to standard format.
     */
    protected function normalizeLogLevel(string $level): string
    {
        $mapping = [
            'error' => 'error',
            'warning' => 'warning',
            'information' => 'info',
            'success audit' => 'info',
            'failure audit' => 'warning',
        ];

        return $mapping[strtolower($level)] ?? 'info';
    }

    /**
     * Parse datetime string to Carbon instance.
     */
    protected function parseDateTime(?string $datetime): ?Carbon
    {
        if (!$datetime) {
            return null;
        }

        try {
            return Carbon::parse($datetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determine webhook type from payload structure.
     */
    protected function determineWebhookType(array $payload): string
    {
        // Check for alert-specific fields
        if (isset($payload['alert_type']) || isset($payload['severity'])) {
            return 'alert';
        }

        // Check for agent status fields
        if (isset($payload['agent_id']) && isset($payload['status'])) {
            return 'agent_status';
        }

        return 'unknown';
    }

    /**
     * Extract pagination info from API response.
     */
    public function extractPaginationInfo(array $response): array
    {
        return [
            'total' => $response['count'] ?? count($response),
            'page' => $response['page'] ?? 1,
            'per_page' => $response['per_page'] ?? count($response),
            'has_next' => !empty($response['next']),
            'has_previous' => !empty($response['previous']),
            'next_url' => $response['next'] ?? null,
            'previous_url' => $response['previous'] ?? null,
        ];
    }

    /**
     * Map task/action result to standardized format.
     */
    public function mapTaskResult(array $taskData): array
    {
        return [
            'id' => $taskData['id'] ?? null,
            'status' => $this->normalizeTaskStatus($taskData['status'] ?? 'pending'),
            'stdout' => $taskData['stdout'] ?? null,
            'stderr' => $taskData['stderr'] ?? null,
            'retcode' => $taskData['retcode'] ?? null,
            'execution_time' => $taskData['execution_time'] ?? null,
            'created_at' => $this->parseDateTime($taskData['created_at'] ?? null),
            'updated_at' => $this->parseDateTime($taskData['updated_at'] ?? null),
            'raw_data' => $taskData,
        ];
    }

    /**
     * Normalize task status to standard format.
     */
    protected function normalizeTaskStatus(string $status): string
    {
        $mapping = [
            'pending' => 'pending',
            'running' => 'running',
            'completed' => 'completed',
            'failed' => 'failed',
            'timeout' => 'timeout',
        ];

        return $mapping[strtolower($status)] ?? 'unknown';
    }

    /**
     * Map comprehensive hardware information to standardized format.
     */
    public function mapHardwareInfo(array $agentData): array
    {
        return [
            'cpu' => [
                'model' => $agentData['cpu'] ?? 'Unknown',
                'cores' => $agentData['cpu_cores'] ?? null,
                'logical_cores' => $agentData['logical_cores'] ?? null,
                'architecture' => $agentData['arch'] ?? 'Unknown',
                'usage_percent' => $agentData['cpu_usage'] ?? null,
            ],
            'memory' => [
                'total_gb' => $agentData['total_ram'] ?? null,
                'available_gb' => $agentData['available_ram'] ?? null,
                'used_percent' => $agentData['memory_usage'] ?? null,
            ],
            'storage' => $this->mapStorageInfo($agentData['disks'] ?? []),
            'network_adapters' => $this->mapNetworkAdapters($agentData['network_adapters'] ?? []),
            'motherboard' => [
                'manufacturer' => $agentData['motherboard_manufacturer'] ?? null,
                'model' => $agentData['motherboard_model'] ?? null,
                'serial' => $agentData['motherboard_serial'] ?? null,
            ],
            'bios' => [
                'version' => $agentData['bios_version'] ?? null,
                'date' => $agentData['bios_date'] ?? null,
                'manufacturer' => $agentData['bios_manufacturer'] ?? null,
            ],
            'graphics' => $this->mapGraphicsInfo($agentData['graphics'] ?? []),
            'system' => [
                'manufacturer' => $agentData['system_manufacturer'] ?? null,
                'model' => $agentData['system_model'] ?? null,
                'serial' => $agentData['system_serial'] ?? null,
                'uuid' => $agentData['system_uuid'] ?? null,
            ],
            'raw_data' => $agentData,
        ];
    }

    /**
     * Map storage/disk information.
     */
    protected function mapStorageInfo(array $disks): array
    {
        return array_map(function ($disk) {
            return [
                'device' => $disk['device'] ?? 'Unknown',
                'model' => $disk['model'] ?? 'Unknown',
                'serial' => $disk['serial'] ?? null,
                'size_gb' => $disk['size'] ?? null,
                'free_gb' => $disk['free'] ?? null,
                'used_gb' => $disk['used'] ?? null,
                'used_percent' => $disk['percent'] ?? null,
                'filesystem' => $disk['fstype'] ?? null,
                'mount_point' => $disk['mountpoint'] ?? null,
                'drive_type' => $disk['drive_type'] ?? null,
                'health_status' => $disk['health'] ?? null,
            ];
        }, $disks);
    }

    /**
     * Map network adapter information.
     */
    protected function mapNetworkAdapters(array $adapters): array
    {
        return array_map(function ($adapter) {
            return [
                'name' => $adapter['name'] ?? 'Unknown',
                'description' => $adapter['description'] ?? null,
                'mac_address' => $adapter['mac_address'] ?? null,
                'ip_addresses' => $adapter['ip_addresses'] ?? [],
                'dhcp_enabled' => $adapter['dhcp_enabled'] ?? false,
                'connection_status' => $adapter['status'] ?? 'unknown',
                'speed_mbps' => $adapter['speed'] ?? null,
                'interface_type' => $adapter['type'] ?? null,
            ];
        }, $adapters);
    }

    /**
     * Map graphics/video card information.
     */
    protected function mapGraphicsInfo($graphics): array
    {
        // Handle case where graphics data comes as a string instead of array
        if (is_string($graphics)) {
            return [[
                'name' => $graphics,
                'driver_version' => null,
                'driver_date' => null,
                'memory_mb' => null,
                'resolution' => null,
                'refresh_rate' => null,
            ]];
        }

        // Handle empty or non-array cases
        if (!is_array($graphics) || empty($graphics)) {
            return [];
        }

        return array_map(function ($gpu) {
            // Handle case where individual GPU data might also be a string
            if (is_string($gpu)) {
                return [
                    'name' => $gpu,
                    'driver_version' => null,
                    'driver_date' => null,
                    'memory_mb' => null,
                    'resolution' => null,
                    'refresh_rate' => null,
                ];
            }

            return [
                'name' => $gpu['name'] ?? 'Unknown',
                'driver_version' => $gpu['driver_version'] ?? null,
                'driver_date' => $gpu['driver_date'] ?? null,
                'memory_mb' => $gpu['memory'] ?? null,
                'resolution' => $gpu['resolution'] ?? null,
                'refresh_rate' => $gpu['refresh_rate'] ?? null,
            ];
        }, $graphics);
    }

    /**
     * Map performance metrics to standardized format.
     */
    public function mapPerformanceMetrics(array $agentData): array
    {
        return [
            'cpu' => [
                'usage_percent' => $agentData['cpu_usage'] ?? null,
                'load_average' => $agentData['cpu_load'] ?? null,
                'temperature' => $agentData['cpu_temp'] ?? null,
            ],
            'memory' => [
                'total_gb' => $agentData['total_ram'] ?? null,
                'available_gb' => $agentData['available_ram'] ?? null,
                'used_gb' => $agentData['used_ram'] ?? null,
                'usage_percent' => $agentData['memory_usage'] ?? null,
                'cached_gb' => $agentData['cached_ram'] ?? null,
            ],
            'disk' => $this->mapDiskPerformance($agentData['disk_io'] ?? []),
            'network' => $this->mapNetworkPerformance($agentData['network_io'] ?? []),
            'uptime' => [
                'boot_time' => $this->parseDateTime($agentData['boot_time'] ?? null),
                'uptime_seconds' => $agentData['uptime'] ?? null,
                'uptime_formatted' => $this->formatUptime($agentData['uptime'] ?? 0),
            ],
            'processes' => [
                'total_count' => $agentData['process_count'] ?? null,
                'top_cpu_processes' => $agentData['top_cpu_processes'] ?? [],
                'top_memory_processes' => $agentData['top_memory_processes'] ?? [],
            ],
            'system' => [
                'logged_in_users' => $agentData['logged_in_users'] ?? [],
                'active_sessions' => $agentData['active_sessions'] ?? null,
                'pending_reboot' => $agentData['needs_reboot'] ?? false,
            ],
            'timestamp' => now()->toISOString(),
            'raw_data' => $agentData,
        ];
    }

    /**
     * Map disk I/O performance data.
     */
    protected function mapDiskPerformance(array $diskIO): array
    {
        return array_map(function ($disk) {
            return [
                'device' => $disk['device'] ?? 'Unknown',
                'read_bytes_per_sec' => $disk['read_bytes'] ?? null,
                'write_bytes_per_sec' => $disk['write_bytes'] ?? null,
                'read_operations_per_sec' => $disk['read_ops'] ?? null,
                'write_operations_per_sec' => $disk['write_ops'] ?? null,
                'queue_depth' => $disk['queue_depth'] ?? null,
                'response_time_ms' => $disk['response_time'] ?? null,
            ];
        }, $diskIO);
    }

    /**
     * Map network I/O performance data.
     */
    protected function mapNetworkPerformance(array $networkIO): array
    {
        return array_map(function ($interface) {
            return [
                'interface' => $interface['interface'] ?? 'Unknown',
                'bytes_sent_per_sec' => $interface['bytes_sent'] ?? null,
                'bytes_received_per_sec' => $interface['bytes_recv'] ?? null,
                'packets_sent_per_sec' => $interface['packets_sent'] ?? null,
                'packets_received_per_sec' => $interface['packets_recv'] ?? null,
                'errors_in' => $interface['errin'] ?? null,
                'errors_out' => $interface['errout'] ?? null,
                'dropped_in' => $interface['dropin'] ?? null,
                'dropped_out' => $interface['dropout'] ?? null,
            ];
        }, $networkIO);
    }

    /**
     * Map network configuration information.
     */
    public function mapNetworkInfo(array $agentData): array
    {
        return [
            'interfaces' => $this->mapNetworkInterfaces($agentData['network_interfaces'] ?? []),
            'routing_table' => $this->mapRoutingTable($agentData['routes'] ?? []),
            'dns_config' => [
                'servers' => $agentData['dns_servers'] ?? [],
                'search_domains' => $agentData['dns_search_domains'] ?? [],
                'suffix' => $agentData['dns_suffix'] ?? null,
            ],
            'firewall' => [
                'enabled' => $agentData['firewall_enabled'] ?? null,
                'profiles' => $agentData['firewall_profiles'] ?? [],
                'rules_count' => $agentData['firewall_rules_count'] ?? null,
            ],
            'connectivity' => [
                'public_ip' => $agentData['public_ip'] ?? null,
                'gateway' => $agentData['default_gateway'] ?? null,
                'proxy_config' => $agentData['proxy_config'] ?? null,
            ],
            'wireless' => $this->mapWirelessInfo($agentData['wireless'] ?? []),
            'raw_data' => $agentData,
        ];
    }

    /**
     * Map detailed network interfaces.
     */
    protected function mapNetworkInterfaces(array $interfaces): array
    {
        return array_map(function ($interface) {
            return [
                'name' => $interface['name'] ?? 'Unknown',
                'display_name' => $interface['display_name'] ?? null,
                'description' => $interface['description'] ?? null,
                'mac_address' => $interface['mac_address'] ?? null,
                'type' => $interface['type'] ?? null,
                'status' => $interface['status'] ?? 'unknown',
                'speed' => $interface['speed'] ?? null,
                'mtu' => $interface['mtu'] ?? null,
                'ip_config' => [
                    'ip_addresses' => $interface['ip_addresses'] ?? [],
                    'subnet_masks' => $interface['subnet_masks'] ?? [],
                    'gateways' => $interface['gateways'] ?? [],
                    'dhcp_enabled' => $interface['dhcp_enabled'] ?? false,
                    'dhcp_server' => $interface['dhcp_server'] ?? null,
                ],
                'statistics' => [
                    'bytes_sent' => $interface['bytes_sent'] ?? null,
                    'bytes_received' => $interface['bytes_received'] ?? null,
                    'packets_sent' => $interface['packets_sent'] ?? null,
                    'packets_received' => $interface['packets_received'] ?? null,
                ],
            ];
        }, $interfaces);
    }

    /**
     * Map routing table information.
     */
    protected function mapRoutingTable(array $routes): array
    {
        return array_map(function ($route) {
            return [
                'destination' => $route['destination'] ?? null,
                'netmask' => $route['netmask'] ?? null,
                'gateway' => $route['gateway'] ?? null,
                'interface' => $route['interface'] ?? null,
                'metric' => $route['metric'] ?? null,
                'type' => $route['type'] ?? null,
            ];
        }, $routes);
    }

    /**
     * Map wireless network information.
     */
    protected function mapWirelessInfo(array $wireless): array
    {
        return array_map(function ($wifi) {
            return [
                'ssid' => $wifi['ssid'] ?? null,
                'bssid' => $wifi['bssid'] ?? null,
                'signal_strength' => $wifi['signal_strength'] ?? null,
                'frequency' => $wifi['frequency'] ?? null,
                'security' => $wifi['security'] ?? null,
                'connected' => $wifi['connected'] ?? false,
            ];
        }, $wireless);
    }

    /**
     * Map Windows updates information.
     */
    public function mapWindowsUpdates(array $updates): array
    {
        return [
            'available_updates' => array_map([$this, 'mapSingleUpdate'], $updates['available'] ?? []),
            'installed_updates' => array_map([$this, 'mapSingleUpdate'], $updates['installed'] ?? []),
            'pending_reboot_updates' => array_map([$this, 'mapSingleUpdate'], $updates['pending_reboot'] ?? []),
            'failed_updates' => array_map([$this, 'mapSingleUpdate'], $updates['failed'] ?? []),
            'summary' => [
                'total_available' => count($updates['available'] ?? []),
                'critical_count' => $this->countUpdatesBySeverity($updates['available'] ?? [], 'critical'),
                'security_count' => $this->countUpdatesByCategory($updates['available'] ?? [], 'security'),
                'last_check' => $this->parseDateTime($updates['last_check'] ?? null),
                'auto_update_enabled' => $updates['auto_update_enabled'] ?? null,
                'reboot_required' => $updates['reboot_required'] ?? false,
            ],
            'raw_data' => $updates,
        ];
    }

    /**
     * Map single Windows update.
     */
    protected function mapSingleUpdate(array $update): array
    {
        return [
            'id' => $update['id'] ?? null,
            'kb_id' => $update['kb'] ?? null,
            'title' => $update['title'] ?? 'Unknown Update',
            'description' => $update['description'] ?? null,
            'category' => $update['category'] ?? 'Other',
            'severity' => $update['severity'] ?? 'Moderate',
            'size_mb' => $update['size'] ?? null,
            'download_url' => $update['download_url'] ?? null,
            'support_url' => $update['support_url'] ?? null,
            'install_date' => $this->parseDateTime($update['install_date'] ?? null),
            'requires_reboot' => $update['requires_reboot'] ?? false,
            'is_installed' => $update['is_installed'] ?? false,
            'install_status' => $update['install_status'] ?? 'pending',
            'raw_data' => $update,
        ];
    }

    /**
     * Map processes information.
     */
    public function mapProcesses(array $processes): array
    {
        return array_map(function ($process) {
            return [
                'pid' => $process['pid'] ?? null,
                'name' => $process['name'] ?? 'Unknown',
                'executable' => $process['exe'] ?? null,
                'command_line' => $process['cmdline'] ?? null,
                'username' => $process['username'] ?? null,
                'status' => $process['status'] ?? 'unknown',
                'cpu_percent' => $process['cpu_percent'] ?? null,
                'memory_mb' => $process['memory_info']['rss'] ?? null,
                'memory_percent' => $process['memory_percent'] ?? null,
                'create_time' => $this->parseDateTime($process['create_time'] ?? null),
                'num_threads' => $process['num_threads'] ?? null,
                'num_handles' => $process['num_handles'] ?? null,
                'parent_pid' => $process['ppid'] ?? null,
                'priority' => $process['nice'] ?? null,
                'working_directory' => $process['cwd'] ?? null,
                'open_files' => $process['open_files'] ?? [],
                'connections' => $process['connections'] ?? [],
                'raw_data' => $process,
            ];
        }, $processes);
    }

    /**
     * Map agent history/activity logs.
     */
    public function mapAgentHistory(array $history): array
    {
        return array_map(function ($entry) {
            return [
                'id' => $entry['id'] ?? null,
                'timestamp' => $this->parseDateTime($entry['timestamp'] ?? null),
                'action' => $entry['action'] ?? 'unknown',
                'description' => $entry['description'] ?? null,
                'username' => $entry['username'] ?? null,
                'source' => $entry['source'] ?? 'system',
                'details' => $entry['details'] ?? [],
                'severity' => $this->normalizeLogLevel($entry['severity'] ?? 'info'),
                'raw_data' => $entry,
            ];
        }, $history);
    }

    /**
     * Count updates by severity level.
     */
    protected function countUpdatesBySeverity(array $updates, string $severity): int
    {
        return count(array_filter($updates, function ($update) use ($severity) {
            return strtolower($update['severity'] ?? '') === strtolower($severity);
        }));
    }

    /**
     * Count updates by category.
     */
    protected function countUpdatesByCategory(array $updates, string $category): int
    {
        return count(array_filter($updates, function ($update) use ($category) {
            return stripos($update['category'] ?? '', $category) !== false;
        }));
    }

    /**
     * Format uptime seconds to human readable format.
     */
    protected function formatUptime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $days = floor($hours / 24);

        if ($days > 0) {
            $hours = $hours % 24;
            return "{$days} days, {$hours} hours";
        }

        if ($hours > 0) {
            $minutes = $minutes % 60;
            return "{$hours} hours, {$minutes} minutes";
        }

        return "{$minutes} minutes";
    }
}