<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RmmMonitoringSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage_integrations');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // ConnectWise Automate Settings
            'connectwise_automate_settings' => 'nullable|array',
            'connectwise_automate_settings.enabled' => 'boolean',
            'connectwise_automate_settings.server_url' => 'nullable|url|max:255',
            'connectwise_automate_settings.username' => 'nullable|string|max:255',
            'connectwise_automate_settings.password' => 'nullable|string|max:255',
            'connectwise_automate_settings.client_id' => 'nullable|string|max:255',
            'connectwise_automate_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'connectwise_automate_settings.auto_create_tickets' => 'boolean',
            'connectwise_automate_settings.sync_clients' => 'boolean',
            'connectwise_automate_settings.sync_assets' => 'boolean',
            'connectwise_automate_settings.monitor_connectivity' => 'boolean',

            // Datto RMM Settings
            'datto_rmm_settings' => 'nullable|array',
            'datto_rmm_settings.enabled' => 'boolean',
            'datto_rmm_settings.api_url' => 'nullable|url|max:255',
            'datto_rmm_settings.api_key' => 'nullable|string|max:255',
            'datto_rmm_settings.api_secret' => 'nullable|string|max:255',
            'datto_rmm_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'datto_rmm_settings.auto_create_tickets' => 'boolean',
            'datto_rmm_settings.sync_devices' => 'boolean',
            'datto_rmm_settings.sync_alerts' => 'boolean',
            'datto_rmm_settings.sync_patches' => 'boolean',
            'datto_rmm_settings.monitor_agent_status' => 'boolean',

            // NinjaOne Settings
            'ninja_rmm_settings' => 'nullable|array',
            'ninja_rmm_settings.enabled' => 'boolean',
            'ninja_rmm_settings.instance_url' => 'nullable|url|max:255',
            'ninja_rmm_settings.client_id' => 'nullable|string|max:255',
            'ninja_rmm_settings.client_secret' => 'nullable|string|max:255',
            'ninja_rmm_settings.access_token' => 'nullable|string|max:1000',
            'ninja_rmm_settings.refresh_token' => 'nullable|string|max:1000',
            'ninja_rmm_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'ninja_rmm_settings.auto_create_tickets' => 'boolean',
            'ninja_rmm_settings.sync_organizations' => 'boolean',
            'ninja_rmm_settings.sync_devices' => 'boolean',
            'ninja_rmm_settings.sync_activities' => 'boolean',
            'ninja_rmm_settings.monitor_policy_compliance' => 'boolean',

            // Kaseya VSA Settings
            'kaseya_vsa_settings' => 'nullable|array',
            'kaseya_vsa_settings.enabled' => 'boolean',
            'kaseya_vsa_settings.server_url' => 'nullable|url|max:255',
            'kaseya_vsa_settings.username' => 'nullable|string|max:255',
            'kaseya_vsa_settings.password' => 'nullable|string|max:255',
            'kaseya_vsa_settings.session_id' => 'nullable|string|max:255',
            'kaseya_vsa_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'kaseya_vsa_settings.auto_create_tickets' => 'boolean',
            'kaseya_vsa_settings.sync_agents' => 'boolean',
            'kaseya_vsa_settings.sync_alarms' => 'boolean',
            'kaseya_vsa_settings.sync_procedures' => 'boolean',
            'kaseya_vsa_settings.monitor_agent_procedures' => 'boolean',

            // Auvik Settings
            'auvik_settings' => 'nullable|array',
            'auvik_settings.enabled' => 'boolean',
            'auvik_settings.api_url' => 'nullable|url|max:255',
            'auvik_settings.username' => 'nullable|string|max:255',
            'auvik_settings.api_key' => 'nullable|string|max:255',
            'auvik_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'auvik_settings.auto_create_tickets' => 'boolean',
            'auvik_settings.sync_devices' => 'boolean',
            'auvik_settings.sync_networks' => 'boolean',
            'auvik_settings.sync_device_info' => 'boolean',
            'auvik_settings.monitor_device_status' => 'boolean',
            'auvik_settings.network_mapping_enabled' => 'boolean',

            // PRTG Settings
            'prtg_settings' => 'nullable|array',
            'prtg_settings.enabled' => 'boolean',
            'prtg_settings.server_url' => 'nullable|url|max:255',
            'prtg_settings.username' => 'nullable|string|max:255',
            'prtg_settings.password' => 'nullable|string|max:255',
            'prtg_settings.passhash' => 'nullable|string|max:255',
            'prtg_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'prtg_settings.auto_create_tickets' => 'boolean',
            'prtg_settings.sync_sensors' => 'boolean',
            'prtg_settings.sync_devices' => 'boolean',
            'prtg_settings.sync_groups' => 'boolean',
            'prtg_settings.monitor_sensor_status' => 'boolean',

            // SolarWinds Settings
            'solarwinds_settings' => 'nullable|array',
            'solarwinds_settings.enabled' => 'boolean',
            'solarwinds_settings.server_url' => 'nullable|url|max:255',
            'solarwinds_settings.username' => 'nullable|string|max:255',
            'solarwinds_settings.password' => 'nullable|string|max:255',
            'solarwinds_settings.certificate_file' => 'nullable|string|max:255',
            'solarwinds_settings.sync_frequency_minutes' => 'integer|min:5|max:1440',
            'solarwinds_settings.auto_create_tickets' => 'boolean',
            'solarwinds_settings.sync_nodes' => 'boolean',
            'solarwinds_settings.sync_interfaces' => 'boolean',
            'solarwinds_settings.sync_applications' => 'boolean',
            'solarwinds_settings.monitor_node_status' => 'boolean',

            // Monitoring Alert Thresholds
            'monitoring_alert_thresholds' => 'nullable|array',
            'monitoring_alert_thresholds.cpu_usage_critical' => 'integer|min:50|max:100',
            'monitoring_alert_thresholds.cpu_usage_warning' => 'integer|min:30|max:90',
            'monitoring_alert_thresholds.memory_usage_critical' => 'integer|min:50|max:100',
            'monitoring_alert_thresholds.memory_usage_warning' => 'integer|min:30|max:90',
            'monitoring_alert_thresholds.disk_usage_critical' => 'integer|min:50|max:100',
            'monitoring_alert_thresholds.disk_usage_warning' => 'integer|min:30|max:90',
            'monitoring_alert_thresholds.network_latency_critical' => 'integer|min:100|max:5000',
            'monitoring_alert_thresholds.network_latency_warning' => 'integer|min:50|max:2000',
            'monitoring_alert_thresholds.uptime_critical_hours' => 'integer|min:1|max:168',
            'monitoring_alert_thresholds.offline_duration_minutes' => 'integer|min:5|max:1440',

            // Escalation Rules
            'escalation_rules' => 'nullable|array',
            'escalation_rules.enabled' => 'boolean',
            'escalation_rules.first_escalation_minutes' => 'integer|min:5|max:1440',
            'escalation_rules.second_escalation_minutes' => 'integer|min:10|max:2880',
            'escalation_rules.final_escalation_minutes' => 'integer|min:15|max:4320',
            'escalation_rules.escalate_to_manager' => 'boolean',
            'escalation_rules.escalate_to_client' => 'boolean',
            'escalation_rules.escalate_weekends' => 'boolean',
            'escalation_rules.escalate_holidays' => 'boolean',
            'escalation_rules.notification_channels' => 'nullable|array',
            'escalation_rules.notification_channels.*' => 'string|in:email,sms,slack,teams,webhook',

            // Asset Discovery Settings
            'asset_discovery_settings' => 'nullable|array',
            'asset_discovery_settings.enabled' => 'boolean',
            'asset_discovery_settings.auto_discovery_frequency_hours' => 'integer|min:1|max:168',
            'asset_discovery_settings.network_scan_enabled' => 'boolean',
            'asset_discovery_settings.active_directory_sync' => 'boolean',
            'asset_discovery_settings.wmi_discovery' => 'boolean',
            'asset_discovery_settings.snmp_discovery' => 'boolean',
            'asset_discovery_settings.ssh_discovery' => 'boolean',
            'asset_discovery_settings.auto_add_discovered_assets' => 'boolean',
            'asset_discovery_settings.require_approval_for_new_assets' => 'boolean',
            'asset_discovery_settings.discovery_ip_ranges' => 'nullable|array',
            'asset_discovery_settings.excluded_ip_ranges' => 'nullable|array',

            // Patch Management Settings
            'patch_management_settings' => 'nullable|array',
            'patch_management_settings.enabled' => 'boolean',
            'patch_management_settings.auto_approve_critical_patches' => 'boolean',
            'patch_management_settings.auto_approve_security_patches' => 'boolean',
            'patch_management_settings.patch_window_start' => 'nullable|date_format:H:i',
            'patch_management_settings.patch_window_end' => 'nullable|date_format:H:i',
            'patch_management_settings.patch_window_days' => 'nullable|array',
            'patch_management_settings.patch_window_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'patch_management_settings.reboot_required_notification' => 'boolean',
            'patch_management_settings.auto_reboot_non_business_hours' => 'boolean',
            'patch_management_settings.patch_groups' => 'nullable|array',
            'patch_management_settings.test_deployment_percentage' => 'integer|min:5|max:50',

            // Remote Access Settings
            'remote_access_settings' => 'nullable|array',
            'remote_access_settings.enabled' => 'boolean',
            'remote_access_settings.require_user_consent' => 'boolean',
            'remote_access_settings.log_all_sessions' => 'boolean',
            'remote_access_settings.session_timeout_minutes' => 'integer|min:5|max:480',
            'remote_access_settings.file_transfer_enabled' => 'boolean',
            'remote_access_settings.clipboard_sync_enabled' => 'boolean',
            'remote_access_settings.multi_monitor_support' => 'boolean',
            'remote_access_settings.encryption_level' => 'string|in:low,medium,high',
            'remote_access_settings.allowed_tools' => 'nullable|array',
            'remote_access_settings.allowed_tools.*' => 'string|in:rdp,vnc,teamviewer,connectwise_control,splashtop,logmein',

            // Auto Ticket Creation
            'auto_create_tickets_from_alerts' => 'boolean',
            'alert_to_ticket_mapping' => 'nullable|array',
            'alert_to_ticket_mapping.critical_alerts' => 'nullable|array',
            'alert_to_ticket_mapping.critical_alerts.priority' => 'string|in:low,medium,high,critical',
            'alert_to_ticket_mapping.critical_alerts.auto_assign' => 'boolean',
            'alert_to_ticket_mapping.critical_alerts.notify_client' => 'boolean',
            'alert_to_ticket_mapping.warning_alerts' => 'nullable|array',
            'alert_to_ticket_mapping.warning_alerts.priority' => 'string|in:low,medium,high,critical',
            'alert_to_ticket_mapping.warning_alerts.auto_assign' => 'boolean',
            'alert_to_ticket_mapping.warning_alerts.notify_client' => 'boolean',
            'alert_to_ticket_mapping.info_alerts' => 'nullable|array',
            'alert_to_ticket_mapping.info_alerts.create_tickets' => 'boolean',
            'alert_to_ticket_mapping.info_alerts.priority' => 'string|in:low,medium,high,critical',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            '*.server_url.url' => 'Server URL must be a valid URL.',
            '*.api_url.url' => 'API URL must be a valid URL.',
            '*.instance_url.url' => 'Instance URL must be a valid URL.',
            '*.sync_frequency_minutes.min' => 'Sync frequency must be at least 5 minutes.',
            '*.sync_frequency_minutes.max' => 'Sync frequency cannot exceed 1440 minutes (24 hours).',
            'monitoring_alert_thresholds.*.min' => 'Threshold value is too low.',
            'monitoring_alert_thresholds.*.max' => 'Threshold value is too high.',
            'escalation_rules.first_escalation_minutes.min' => 'First escalation must be at least 5 minutes.',
            'patch_management_settings.patch_window_end.after' => 'Patch window end time must be after start time.',
            'patch_management_settings.test_deployment_percentage.max' => 'Test deployment percentage cannot exceed 50%.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'connectwise_automate_settings.server_url' => 'ConnectWise Automate server URL',
            'datto_rmm_settings.api_url' => 'Datto RMM API URL',
            'ninja_rmm_settings.instance_url' => 'NinjaOne instance URL',
            'kaseya_vsa_settings.server_url' => 'Kaseya VSA server URL',
            'auvik_settings.api_url' => 'Auvik API URL',
            'prtg_settings.server_url' => 'PRTG server URL',
            'solarwinds_settings.server_url' => 'SolarWinds server URL',
            'auto_create_tickets_from_alerts' => 'auto-create tickets from alerts',
            'monitoring_alert_thresholds.cpu_usage_critical' => 'CPU usage critical threshold',
            'monitoring_alert_thresholds.memory_usage_critical' => 'memory usage critical threshold',
            'monitoring_alert_thresholds.disk_usage_critical' => 'disk usage critical threshold',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that warning thresholds are lower than critical thresholds
            $thresholds = $this->input('monitoring_alert_thresholds', []);

            if (isset($thresholds['cpu_usage_warning'], $thresholds['cpu_usage_critical'])) {
                if ($thresholds['cpu_usage_warning'] >= $thresholds['cpu_usage_critical']) {
                    $validator->errors()->add('monitoring_alert_thresholds.cpu_usage_warning', 'Warning threshold must be lower than critical threshold.');
                }
            }

            if (isset($thresholds['memory_usage_warning'], $thresholds['memory_usage_critical'])) {
                if ($thresholds['memory_usage_warning'] >= $thresholds['memory_usage_critical']) {
                    $validator->errors()->add('monitoring_alert_thresholds.memory_usage_warning', 'Warning threshold must be lower than critical threshold.');
                }
            }

            if (isset($thresholds['disk_usage_warning'], $thresholds['disk_usage_critical'])) {
                if ($thresholds['disk_usage_warning'] >= $thresholds['disk_usage_critical']) {
                    $validator->errors()->add('monitoring_alert_thresholds.disk_usage_warning', 'Warning threshold must be lower than critical threshold.');
                }
            }

            // Validate escalation timing
            $escalation = $this->input('escalation_rules', []);
            if (isset($escalation['first_escalation_minutes'], $escalation['second_escalation_minutes'])) {
                if ($escalation['first_escalation_minutes'] >= $escalation['second_escalation_minutes']) {
                    $validator->errors()->add('escalation_rules.second_escalation_minutes', 'Second escalation must be later than first escalation.');
                }
            }

            if (isset($escalation['second_escalation_minutes'], $escalation['final_escalation_minutes'])) {
                if ($escalation['second_escalation_minutes'] >= $escalation['final_escalation_minutes']) {
                    $validator->errors()->add('escalation_rules.final_escalation_minutes', 'Final escalation must be later than second escalation.');
                }
            }

            // Validate patch window
            $patchSettings = $this->input('patch_management_settings', []);
            if (isset($patchSettings['patch_window_start'], $patchSettings['patch_window_end'])) {
                $start = $patchSettings['patch_window_start'];
                $end = $patchSettings['patch_window_end'];

                if ($start && $end && $start >= $end) {
                    $validator->errors()->add('patch_management_settings.patch_window_end', 'Patch window end time must be after start time.');
                }
            }
        });
    }
}
