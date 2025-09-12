<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setting Model
 * 
 * Represents system-wide configuration settings for each company.
 * Includes SMTP, IMAP, invoice, ticket, and system preferences.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $current_database_version
 * @property string $start_page
 * @property string|null $smtp_host
 * @property int|null $smtp_port
 * @property string|null $smtp_encryption
 * @property string|null $smtp_username
 * @property string|null $smtp_password
 * @property string|null $mail_from_email
 * @property string|null $mail_from_name
 * @property string|null $imap_host
 * @property int|null $imap_port
 * @property string|null $imap_encryption
 * @property string|null $imap_username
 * @property string|null $imap_password
 * @property int|null $default_transfer_from_account
 * @property int|null $default_transfer_to_account
 * @property int|null $default_payment_account
 * @property int|null $default_expense_account
 * @property string|null $default_payment_method
 * @property string|null $default_expense_payment_method
 * @property int|null $default_calendar
 * @property int|null $default_net_terms
 * @property float $default_hourly_rate
 * @property string|null $invoice_prefix
 * @property int|null $invoice_next_number
 * @property string|null $invoice_footer
 * @property string|null $invoice_from_name
 * @property string|null $invoice_from_email
 * @property bool $invoice_late_fee_enable
 * @property float $invoice_late_fee_percent
 * @property string|null $quote_prefix
 * @property int|null $quote_next_number
 * @property string|null $quote_footer
 * @property string|null $quote_from_name
 * @property string|null $quote_from_email
 * @property string|null $ticket_prefix
 * @property int|null $ticket_next_number
 * @property string|null $ticket_from_name
 * @property string|null $ticket_from_email
 * @property bool $ticket_email_parse
 * @property bool $ticket_client_general_notifications
 * @property bool $ticket_autoclose
 * @property int $ticket_autoclose_hours
 * @property string|null $ticket_new_ticket_notification_email
 * @property bool $enable_cron
 * @property string|null $cron_key
 * @property bool $recurring_auto_send_invoice
 * @property bool $enable_alert_domain_expire
 * @property bool $send_invoice_reminders
 * @property string|null $invoice_overdue_reminders
 * @property string $theme
 * @property bool $telemetry
 * @property string $timezone
 * @property bool $destructive_deletes_enable
 * @property bool $module_enable_itdoc
 * @property bool $module_enable_accounting
 * @property bool $module_enable_ticketing
 * @property bool $client_portal_enable
 * @property string|null $login_message
 * @property bool $login_key_required
 * @property string|null $login_key_secret
 * @property array|null $company_colors
 * @property array|null $business_hours
 * @property array|null $company_holidays
 * @property array|null $custom_fields
 * @property bool $two_factor_enabled
 * @property array|null $two_factor_methods
 * @property bool $multi_currency_enabled
 * @property array|null $supported_currencies
 * @property bool $recurring_billing_enabled
 * @property bool $auto_create_tickets_from_alerts
 * @property bool $time_tracking_enabled
 * @property bool $customer_satisfaction_enabled
 * @property bool $project_time_tracking_enabled
 * @property bool $barcode_scanning_enabled
 * @property bool $mobile_asset_management_enabled
 * @property bool $portal_self_service_tickets
 * @property bool $portal_knowledge_base_access
 * @property bool $portal_invoice_access
 * @property bool $portal_payment_processing
 * @property bool $portal_asset_visibility
 * @property bool $soc2_compliance_enabled
 * @property bool $hipaa_compliance_enabled
 * @property bool $pci_compliance_enabled
 * @property bool $gdpr_compliance_enabled
 * @property int|null $recovery_time_objective
 * @property int|null $recovery_point_objective
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'current_database_version',
        'start_page',
        
        // General & Company Settings
        'company_logo',
        'company_colors',
        'company_address',
        'company_city',
        'company_state',
        'company_zip',
        'company_country',
        'company_phone',
        'company_website',
        'company_tax_id',
        'business_hours',
        'company_holidays',
        'company_language',
        'company_currency',
        'custom_fields',
        'localization_settings',
        
        // Security & Access Control
        'password_min_length',
        'password_require_special',
        'password_require_numbers',
        'password_require_uppercase',
        'password_expiry_days',
        'password_history_count',
        'two_factor_enabled',
        'two_factor_methods',
        'remember_me_enabled',
        'email_verification_required',
        'api_authentication_enabled',
        'session_timeout_minutes',
        'force_single_session',
        'max_login_attempts',
        'login_lockout_duration',
        'lockout_duration_minutes',
        'password_min_length',
        'password_expiry_days',
        'password_require_uppercase',
        'password_require_lowercase',
        'password_require_number',
        'password_require_special',
        'password_history_count',
        'session_lifetime',
        'idle_timeout',
        'single_session_per_user',
        'logout_on_browser_close',
        'ip_whitelist_enabled',
        'whitelisted_ips',
        'oauth_google_enabled',
        'oauth_google_client_id',
        'oauth_google_client_secret',
        'oauth_microsoft_enabled',
        'oauth_microsoft_client_id',
        'oauth_microsoft_client_secret',
        'saml_enabled',
        'saml_entity_id',
        'saml_sso_url',
        'saml_certificate',
        'audit_login_attempts',
        'audit_password_changes',
        'audit_permission_changes',
        'audit_data_access',
        'audit_log_retention_days',
        'alert_suspicious_activity',
        'alert_multiple_failed_logins',
        'security_alert_email',
        'allowed_ip_ranges',
        'blocked_ip_ranges',
        'geo_blocking_enabled',
        'allowed_countries',
        'sso_settings',
        'audit_logging_enabled',
        'audit_retention_days',
        'login_message',
        'login_key_required',
        'login_key_secret',
        
        // Email & Communication
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_auth_method',
        'smtp_username',
        'smtp_password',
        'smtp_auth_required',
        'smtp_use_tls',
        'smtp_timeout',
        'mail_from_email',
        'mail_from_name',
        'email_retry_attempts',
        'email_templates',
        'email_signatures',
        'email_tracking_enabled',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_auth_method',
        'imap_username',
        'imap_password',
        'sms_settings',
        'voice_settings',
        'slack_settings',
        'teams_settings',
        'discord_settings',
        'video_conferencing_settings',
        'communication_preferences',
        'quiet_hours_start',
        'quiet_hours_end',
        
        // Billing & Financial Management
        'default_transfer_from_account',
        'default_transfer_to_account',
        'default_payment_account',
        'default_expense_account',
        'default_payment_method',
        'default_expense_payment_method',
        'default_calendar',
        'default_net_terms',
        'default_hourly_rate',
        'multi_currency_enabled',
        'supported_currencies',
        'exchange_rate_provider',
        'auto_update_exchange_rates',
        'tax_calculation_settings',
        'tax_engine_provider',
        'payment_gateway_settings',
        'stripe_settings',
        'square_settings',
        'paypal_settings',
        'authorize_net_settings',
        'ach_settings',
        'wire_settings',
        'check_settings',
        'recurring_billing_enabled',
        'recurring_billing_settings',
        'late_fee_settings',
        'collection_settings',
        'accounting_integration_settings',
        'quickbooks_settings',
        'xero_settings',
        'sage_settings',
        'revenue_recognition_enabled',
        'revenue_recognition_settings',
        'purchase_order_settings',
        'expense_approval_settings',
        'invoice_prefix',
        'invoice_next_number',
        'invoice_footer',
        'invoice_from_name',
        'invoice_from_email',
        'invoice_late_fee_enable',
        'invoice_late_fee_percent',
        'quote_prefix',
        'quote_next_number',
        'quote_footer',
        'quote_from_name',
        'quote_from_email',
        'recurring_auto_send_invoice',
        'send_invoice_reminders',
        'invoice_overdue_reminders',
        
        // RMM & Monitoring Integrations
        'connectwise_automate_settings',
        'datto_rmm_settings',
        'ninja_rmm_settings',
        'kaseya_vsa_settings',
        'auvik_settings',
        'prtg_settings',
        'solarwinds_settings',
        'monitoring_alert_thresholds',
        'escalation_rules',
        'asset_discovery_settings',
        'patch_management_settings',
        'remote_access_settings',
        'auto_create_tickets_from_alerts',
        'alert_to_ticket_mapping',
        
        // Ticketing & Service Desk
        'ticket_prefix',
        'ticket_next_number',
        'ticket_from_name',
        'ticket_from_email',
        'ticket_email_parse',
        'ticket_client_general_notifications',
        'ticket_autoclose',
        'ticket_autoclose_hours',
        'ticket_new_ticket_notification_email',
        'ticket_categorization_rules',
        'ticket_priority_rules',
        'sla_definitions',
        'sla_escalation_policies',
        'auto_assignment_rules',
        'routing_logic',
        'approval_workflows',
        'time_tracking_enabled',
        'time_tracking_settings',
        'customer_satisfaction_enabled',
        'csat_settings',
        'ticket_templates',
        'ticket_automation_rules',
        'multichannel_settings',
        'queue_management_settings',
        
        // Project Management
        'project_templates',
        'project_standardization_settings',
        'resource_allocation_settings',
        'capacity_planning_settings',
        'project_time_tracking_enabled',
        'project_billing_settings',
        'milestone_settings',
        'deliverable_settings',
        'gantt_chart_settings',
        'budget_management_settings',
        'profitability_tracking_settings',
        'change_request_workflows',
        'project_collaboration_settings',
        'document_management_settings',
        
        // Asset & Inventory Management
        'asset_discovery_rules',
        'asset_lifecycle_settings',
        'software_license_settings',
        'hardware_warranty_settings',
        'procurement_settings',
        'vendor_management_settings',
        'asset_depreciation_settings',
        'asset_tracking_settings',
        'barcode_scanning_enabled',
        'barcode_settings',
        'mobile_asset_management_enabled',
        'asset_relationship_settings',
        'asset_compliance_settings',
        
        // Client Portal & Self-Service
        'client_portal_enable',
        'portal_branding_settings',
        'portal_customization_settings',
        'portal_access_controls',
        'portal_feature_toggles',
        'portal_self_service_tickets',
        'portal_knowledge_base_access',
        'portal_invoice_access',
        'portal_payment_processing',
        'portal_asset_visibility',
        'portal_sso_settings',
        'portal_mobile_settings',
        'portal_dashboard_settings',
        
        // Automation & Workflows
        'business_rule_engine_settings',
        'workflow_automation_templates',
        'rpa_bot_settings',
        'event_driven_automation',
        'custom_script_policies',
        'integration_middleware_settings',
        'data_synchronization_rules',
        'notification_automation_settings',
        'approval_process_automation',
        'document_generation_automation',
        
        // Compliance & Regulatory
        'soc2_compliance_enabled',
        'soc2_settings',
        'hipaa_compliance_enabled',
        'hipaa_settings',
        'pci_compliance_enabled',
        'pci_settings',
        'gdpr_compliance_enabled',
        'gdpr_settings',
        'industry_compliance_settings',
        'data_retention_policies',
        'data_destruction_policies',
        'risk_assessment_settings',
        'vendor_compliance_settings',
        'incident_response_settings',
        
        // Backup & Disaster Recovery
        'backup_policies',
        'backup_schedules',
        'recovery_time_objective',
        'recovery_point_objective',
        'disaster_recovery_procedures',
        'data_replication_settings',
        'business_continuity_settings',
        'testing_validation_schedules',
        'cloud_backup_settings',
        'ransomware_protection_settings',
        'recovery_documentation_settings',
        
        // Performance & System Optimization
        'system_resource_monitoring',
        'performance_tuning_settings',
        'caching_strategies',
        'database_optimization_settings',
        'cdn_load_balancing_settings',
        'api_performance_settings',
        'queue_management_settings',
        'search_optimization_settings',
        'mobile_performance_settings',
        'system_health_monitoring',
        
        // Reporting & Analytics
        'custom_dashboard_settings',
        'report_templates',
        'report_scheduling_settings',
        'kpi_metric_definitions',
        'data_visualization_settings',
        'executive_summary_settings',
        'benchmarking_settings',
        'predictive_analytics_settings',
        'export_delivery_settings',
        'data_warehouse_settings',
        'business_intelligence_settings',
        
        // Notifications & Alerts
        'multichannel_notification_settings',
        'alert_escalation_policies',
        'notification_quiet_hours',
        'notification_templates',
        'notification_frequency_rules',
        'notification_batching_rules',
        'emergency_notification_settings',
        'mobile_push_settings',
        'webhook_notification_settings',
        'custom_notification_rules',
        'notification_localization_settings',
        
        // API & Integration Management
        'api_key_management_settings',
        'api_rate_limiting_settings',
        'api_throttling_settings',
        'webhook_configuration_settings',
        'third_party_integration_settings',
        'data_mapping_settings',
        'sync_scheduling_settings',
        'integration_monitoring_settings',
        'custom_connector_settings',
        'marketplace_integration_settings',
        'legacy_system_bridge_settings',
        
        // Mobile & Remote Access
        'mobile_app_settings',
        'offline_mode_settings',
        'mobile_push_notification_settings',
        'remote_access_policies',
        'gps_tracking_settings',
        'mobile_device_management_settings',
        'byod_policies',
        'mobile_feature_settings',
        'mobile_sync_settings',
        'mobile_battery_optimization',
        
        // Training & Documentation
        'knowledge_base_settings',
        'training_module_settings',
        'documentation_standards',
        'video_library_settings',
        'certification_tracking_settings',
        'skills_assessment_settings',
        'learning_path_settings',
        'external_training_integration_settings',
        'documentation_versioning_settings',
        'search_discovery_settings',
        
        // System Settings
        'enable_cron',
        'cron_key',
        'enable_alert_domain_expire',
        'theme',
        'telemetry',
        'timezone',
        'date_format',
        'destructive_deletes_enable',
        'module_enable_itdoc',
        'module_enable_accounting',
        'module_enable_ticketing',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'smtp_password',
        'imap_password',
        'cron_key',
        'login_key_secret',
        'stripe_settings',
        'square_settings',
        'paypal_settings',
        'authorize_net_settings',
        'ach_settings',
        'wire_settings',
        'check_settings',
        'connectwise_automate_settings',
        'datto_rmm_settings',
        'ninja_rmm_settings',
        'kaseya_vsa_settings',
        'auvik_settings',
        'prtg_settings',
        'solarwinds_settings',
        'quickbooks_settings',
        'xero_settings',
        'sage_settings',
        'sso_settings',
        'api_key_management_settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        
        // General & Company Settings
        'company_colors' => 'array',
        'business_hours' => 'array',
        'company_holidays' => 'array',
        'custom_fields' => 'array',
        'localization_settings' => 'array',
        
        // Security & Access Control
        'password_min_length' => 'integer',
        'password_require_special' => 'boolean',
        'password_require_numbers' => 'boolean',
        'password_require_uppercase' => 'boolean',
        'password_expiry_days' => 'integer',
        'password_history_count' => 'integer',
        'two_factor_enabled' => 'boolean',
        'two_factor_methods' => 'array',
        'session_timeout_minutes' => 'integer',
        'force_single_session' => 'boolean',
        'max_login_attempts' => 'integer',
        'lockout_duration_minutes' => 'integer',
        'allowed_ip_ranges' => 'array',
        'blocked_ip_ranges' => 'array',
        'geo_blocking_enabled' => 'boolean',
        'allowed_countries' => 'array',
        'sso_settings' => 'array',
        'audit_logging_enabled' => 'boolean',
        'audit_retention_days' => 'integer',
        'login_key_required' => 'boolean',
        
        // Email & Communication
        'smtp_port' => 'integer',
        'smtp_auth_required' => 'boolean',
        'smtp_use_tls' => 'boolean',
        'smtp_timeout' => 'integer',
        'email_retry_attempts' => 'integer',
        'email_templates' => 'array',
        'email_signatures' => 'array',
        'email_tracking_enabled' => 'boolean',
        'imap_port' => 'integer',
        'sms_settings' => 'array',
        'voice_settings' => 'array',
        'slack_settings' => 'array',
        'teams_settings' => 'array',
        'discord_settings' => 'array',
        'video_conferencing_settings' => 'array',
        'communication_preferences' => 'array',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        
        // Billing & Financial Management
        'default_transfer_from_account' => 'integer',
        'default_transfer_to_account' => 'integer',
        'default_payment_account' => 'integer',
        'default_expense_account' => 'integer',
        'default_calendar' => 'integer',
        'default_net_terms' => 'integer',
        'default_hourly_rate' => 'decimal:2',
        'multi_currency_enabled' => 'boolean',
        'supported_currencies' => 'array',
        'auto_update_exchange_rates' => 'boolean',
        'tax_calculation_settings' => 'array',
        'payment_gateway_settings' => 'array',
        'stripe_settings' => 'array',
        'square_settings' => 'array',
        'paypal_settings' => 'array',
        'authorize_net_settings' => 'array',
        'ach_settings' => 'array',
        'wire_settings' => 'array',
        'check_settings' => 'array',
        'recurring_billing_enabled' => 'boolean',
        'recurring_billing_settings' => 'array',
        'late_fee_settings' => 'array',
        'collection_settings' => 'array',
        'accounting_integration_settings' => 'array',
        'quickbooks_settings' => 'array',
        'xero_settings' => 'array',
        'sage_settings' => 'array',
        'revenue_recognition_enabled' => 'boolean',
        'revenue_recognition_settings' => 'array',
        'purchase_order_settings' => 'array',
        'expense_approval_settings' => 'array',
        'invoice_next_number' => 'integer',
        'invoice_late_fee_enable' => 'boolean',
        'invoice_late_fee_percent' => 'decimal:2',
        'quote_next_number' => 'integer',
        'recurring_auto_send_invoice' => 'boolean',
        'send_invoice_reminders' => 'boolean',
        
        // RMM & Monitoring Integrations
        'connectwise_automate_settings' => 'array',
        'datto_rmm_settings' => 'array',
        'ninja_rmm_settings' => 'array',
        'kaseya_vsa_settings' => 'array',
        'auvik_settings' => 'array',
        'prtg_settings' => 'array',
        'solarwinds_settings' => 'array',
        'monitoring_alert_thresholds' => 'array',
        'escalation_rules' => 'array',
        'asset_discovery_settings' => 'array',
        'patch_management_settings' => 'array',
        'remote_access_settings' => 'array',
        'auto_create_tickets_from_alerts' => 'boolean',
        'alert_to_ticket_mapping' => 'array',
        
        // Ticketing & Service Desk
        'ticket_next_number' => 'integer',
        'ticket_email_parse' => 'boolean',
        'ticket_client_general_notifications' => 'boolean',
        'ticket_autoclose' => 'boolean',
        'ticket_autoclose_hours' => 'integer',
        'ticket_categorization_rules' => 'array',
        'ticket_priority_rules' => 'array',
        'sla_definitions' => 'array',
        'sla_escalation_policies' => 'array',
        'auto_assignment_rules' => 'array',
        'routing_logic' => 'array',
        'approval_workflows' => 'array',
        'time_tracking_enabled' => 'boolean',
        'time_tracking_settings' => 'array',
        'customer_satisfaction_enabled' => 'boolean',
        'csat_settings' => 'array',
        'ticket_templates' => 'array',
        'ticket_automation_rules' => 'array',
        'multichannel_settings' => 'array',
        'queue_management_settings' => 'array',
        
        // Project Management
        'project_templates' => 'array',
        'project_standardization_settings' => 'array',
        'resource_allocation_settings' => 'array',
        'capacity_planning_settings' => 'array',
        'project_time_tracking_enabled' => 'boolean',
        'project_billing_settings' => 'array',
        'milestone_settings' => 'array',
        'deliverable_settings' => 'array',
        'gantt_chart_settings' => 'array',
        'budget_management_settings' => 'array',
        'profitability_tracking_settings' => 'array',
        'change_request_workflows' => 'array',
        'project_collaboration_settings' => 'array',
        'document_management_settings' => 'array',
        
        // Asset & Inventory Management
        'asset_discovery_rules' => 'array',
        'asset_lifecycle_settings' => 'array',
        'software_license_settings' => 'array',
        'hardware_warranty_settings' => 'array',
        'procurement_settings' => 'array',
        'vendor_management_settings' => 'array',
        'asset_depreciation_settings' => 'array',
        'asset_tracking_settings' => 'array',
        'barcode_scanning_enabled' => 'boolean',
        'barcode_settings' => 'array',
        'mobile_asset_management_enabled' => 'boolean',
        'asset_relationship_settings' => 'array',
        'asset_compliance_settings' => 'array',
        
        // Client Portal & Self-Service
        'client_portal_enable' => 'boolean',
        'portal_branding_settings' => 'array',
        'portal_customization_settings' => 'array',
        'portal_access_controls' => 'array',
        'portal_feature_toggles' => 'array',
        'portal_self_service_tickets' => 'boolean',
        'portal_knowledge_base_access' => 'boolean',
        'portal_invoice_access' => 'boolean',
        'portal_payment_processing' => 'boolean',
        'portal_asset_visibility' => 'boolean',
        'portal_sso_settings' => 'array',
        'portal_mobile_settings' => 'array',
        'portal_dashboard_settings' => 'array',
        
        // Automation & Workflows
        'business_rule_engine_settings' => 'array',
        'workflow_automation_templates' => 'array',
        'rpa_bot_settings' => 'array',
        'event_driven_automation' => 'array',
        'custom_script_policies' => 'array',
        'integration_middleware_settings' => 'array',
        'data_synchronization_rules' => 'array',
        'notification_automation_settings' => 'array',
        'approval_process_automation' => 'array',
        'document_generation_automation' => 'array',
        
        // Compliance & Regulatory
        'soc2_compliance_enabled' => 'boolean',
        'soc2_settings' => 'array',
        'hipaa_compliance_enabled' => 'boolean',
        'hipaa_settings' => 'array',
        'pci_compliance_enabled' => 'boolean',
        'pci_settings' => 'array',
        'gdpr_compliance_enabled' => 'boolean',
        'gdpr_settings' => 'array',
        'industry_compliance_settings' => 'array',
        'data_retention_policies' => 'array',
        'data_destruction_policies' => 'array',
        'risk_assessment_settings' => 'array',
        'vendor_compliance_settings' => 'array',
        'incident_response_settings' => 'array',
        
        // Backup & Disaster Recovery
        'backup_policies' => 'array',
        'backup_schedules' => 'array',
        'recovery_time_objective' => 'integer',
        'recovery_point_objective' => 'integer',
        'disaster_recovery_procedures' => 'array',
        'data_replication_settings' => 'array',
        'business_continuity_settings' => 'array',
        'testing_validation_schedules' => 'array',
        'cloud_backup_settings' => 'array',
        'ransomware_protection_settings' => 'array',
        'recovery_documentation_settings' => 'array',
        
        // Performance & System Optimization
        'system_resource_monitoring' => 'array',
        'performance_tuning_settings' => 'array',
        'caching_strategies' => 'array',
        'database_optimization_settings' => 'array',
        'cdn_load_balancing_settings' => 'array',
        'api_performance_settings' => 'array',
        'queue_management_settings' => 'array',
        'search_optimization_settings' => 'array',
        'mobile_performance_settings' => 'array',
        'system_health_monitoring' => 'array',
        
        // Reporting & Analytics
        'custom_dashboard_settings' => 'array',
        'report_templates' => 'array',
        'report_scheduling_settings' => 'array',
        'kpi_metric_definitions' => 'array',
        'data_visualization_settings' => 'array',
        'executive_summary_settings' => 'array',
        'benchmarking_settings' => 'array',
        'predictive_analytics_settings' => 'array',
        'export_delivery_settings' => 'array',
        'data_warehouse_settings' => 'array',
        'business_intelligence_settings' => 'array',
        
        // Notifications & Alerts
        'multichannel_notification_settings' => 'array',
        'alert_escalation_policies' => 'array',
        'notification_quiet_hours' => 'array',
        'notification_templates' => 'array',
        'notification_frequency_rules' => 'array',
        'notification_batching_rules' => 'array',
        'emergency_notification_settings' => 'array',
        'mobile_push_settings' => 'array',
        'webhook_notification_settings' => 'array',
        'custom_notification_rules' => 'array',
        'notification_localization_settings' => 'array',
        
        // API & Integration Management
        'api_key_management_settings' => 'array',
        'api_rate_limiting_settings' => 'array',
        'api_throttling_settings' => 'array',
        'webhook_configuration_settings' => 'array',
        'third_party_integration_settings' => 'array',
        'data_mapping_settings' => 'array',
        'sync_scheduling_settings' => 'array',
        'integration_monitoring_settings' => 'array',
        'custom_connector_settings' => 'array',
        'marketplace_integration_settings' => 'array',
        'legacy_system_bridge_settings' => 'array',
        
        // Mobile & Remote Access
        'mobile_app_settings' => 'array',
        'offline_mode_settings' => 'array',
        'mobile_push_notification_settings' => 'array',
        'remote_access_policies' => 'array',
        'gps_tracking_settings' => 'array',
        'mobile_device_management_settings' => 'array',
        'byod_policies' => 'array',
        'mobile_feature_settings' => 'array',
        'mobile_sync_settings' => 'array',
        'mobile_battery_optimization' => 'array',
        
        // Training & Documentation
        'knowledge_base_settings' => 'array',
        'training_module_settings' => 'array',
        'documentation_standards' => 'array',
        'video_library_settings' => 'array',
        'certification_tracking_settings' => 'array',
        'skills_assessment_settings' => 'array',
        'learning_path_settings' => 'array',
        'external_training_integration_settings' => 'array',
        'documentation_versioning_settings' => 'array',
        'search_discovery_settings' => 'array',
        
        // System Settings
        'enable_cron' => 'boolean',
        'enable_alert_domain_expire' => 'boolean',
        'telemetry' => 'boolean',
        'destructive_deletes_enable' => 'boolean',
        'module_enable_itdoc' => 'boolean',
        'module_enable_accounting' => 'boolean',
        'module_enable_ticketing' => 'boolean',
        
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if SMTP is configured.
     */
    public function hasSmtpConfiguration(): bool
    {
        return !empty($this->smtp_host) && !empty($this->smtp_port);
    }

    /**
     * Check if IMAP is configured.
     */
    public function hasImapConfiguration(): bool
    {
        return !empty($this->imap_host) && !empty($this->imap_port);
    }

    /**
     * Check if cron is enabled.
     */
    public function isCronEnabled(): bool
    {
        return $this->enable_cron === true;
    }

    /**
     * Check if module is enabled.
     */
    public function isModuleEnabled(string $module): bool
    {
        $field = 'module_enable_' . $module;
        return $this->$field === true;
    }
    
    /**
     * Check if compliance feature is enabled.
     */
    public function isComplianceEnabled(string $compliance): bool
    {
        $field = $compliance . '_compliance_enabled';
        return $this->$field === true;
    }
    
    /**
     * Check if integration is configured.
     */
    public function hasIntegrationConfiguration(string $integration): bool
    {
        $field = $integration . '_settings';
        $settings = $this->$field;
        return !empty($settings) && is_array($settings) && !empty(array_filter($settings));
    }
    
    /**
     * Check if payment gateway is configured.
     */
    public function hasPaymentGatewayConfiguration(string $gateway): bool
    {
        return $this->hasIntegrationConfiguration($gateway);
    }
    
    /**
     * Check if RMM integration is configured.
     */
    public function hasRmmIntegration(): bool
    {
        return $this->hasIntegrationConfiguration('connectwise_automate') ||
               $this->hasIntegrationConfiguration('datto_rmm') ||
               $this->hasIntegrationConfiguration('ninja_rmm') ||
               $this->hasIntegrationConfiguration('kaseya_vsa');
    }
    
    /**
     * Check if monitoring integration is configured.
     */
    public function hasMonitoringIntegration(): bool
    {
        return $this->hasIntegrationConfiguration('auvik') ||
               $this->hasIntegrationConfiguration('prtg') ||
               $this->hasIntegrationConfiguration('solarwinds');
    }
    
    /**
     * Check if accounting integration is configured.
     */
    public function hasAccountingIntegration(): bool
    {
        return $this->hasIntegrationConfiguration('quickbooks') ||
               $this->hasIntegrationConfiguration('xero') ||
               $this->hasIntegrationConfiguration('sage');
    }
    
    /**
     * Get security compliance status.
     */
    public function getComplianceStatus(): array
    {
        return [
            'soc2' => $this->soc2_compliance_enabled,
            'hipaa' => $this->hipaa_compliance_enabled,
            'pci' => $this->pci_compliance_enabled,
            'gdpr' => $this->gdpr_compliance_enabled,
        ];
    }
    
    /**
     * Get enabled integrations.
     */
    public function getEnabledIntegrations(): array
    {
        $integrations = [];
        
        $integrationFields = [
            'connectwise_automate', 'datto_rmm', 'ninja_rmm', 'kaseya_vsa',
            'auvik', 'prtg', 'solarwinds', 'stripe', 'square', 'paypal',
            'authorize_net', 'quickbooks', 'xero', 'sage'
        ];
        
        foreach ($integrationFields as $integration) {
            if ($this->hasIntegrationConfiguration($integration)) {
                $integrations[] = $integration;
            }
        }
        
        return $integrations;
    }
    
    /**
     * Check if two-factor authentication is properly configured.
     */
    public function isTwoFactorProperlyConfigured(): bool
    {
        return $this->two_factor_enabled && 
               !empty($this->two_factor_methods) && 
               is_array($this->two_factor_methods);
    }
    
    /**
     * Check if password policy is strong.
     */
    public function hasStrongPasswordPolicy(): bool
    {
        return $this->password_min_length >= 8 &&
               $this->password_require_special &&
               $this->password_require_numbers &&
               $this->password_require_uppercase &&
               $this->password_expiry_days <= 90;
    }
    
    /**
     * Get portal configuration status.
     */
    public function getPortalStatus(): array
    {
        return [
            'enabled' => $this->client_portal_enable,
            'self_service_tickets' => $this->portal_self_service_tickets,
            'knowledge_base_access' => $this->portal_knowledge_base_access,
            'invoice_access' => $this->portal_invoice_access,
            'payment_processing' => $this->portal_payment_processing,
            'asset_visibility' => $this->portal_asset_visibility,
        ];
    }

    /**
     * Get next invoice number and increment.
     */
    public function getNextInvoiceNumber(): int
    {
        $number = $this->invoice_next_number ?: 1;
        $this->update(['invoice_next_number' => $number + 1]);
        return $number;
    }

    /**
     * Get next quote number and increment.
     */
    public function getNextQuoteNumber(): int
    {
        $number = $this->quote_next_number ?: 1;
        $this->update(['quote_next_number' => $number + 1]);
        return $number;
    }

    /**
     * Get next ticket number and increment.
     */
    public function getNextTicketNumber(): int
    {
        $number = $this->ticket_next_number ?: 1;
        $this->update(['ticket_next_number' => $number + 1]);
        return $number;
    }

    /**
     * Get formatted hourly rate.
     */
    public function getFormattedHourlyRate(): string
    {
        return '$' . number_format($this->default_hourly_rate, 2) . '/hr';
    }

    /**
     * Get available themes.
     */
    public static function getAvailableThemes(): array
    {
        return [
            'blue' => 'Blue',
            'green' => 'Green',
            'red' => 'Red',
            'purple' => 'Purple',
            'orange' => 'Orange',
            'dark' => 'Dark',
            'light' => 'Light',
            'corporate' => 'Corporate',
        ];
    }
    
    /**
     * Get available currencies.
     */
    public static function getAvailableCurrencies(): array
    {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'JPY' => 'Japanese Yen',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'INR' => 'Indian Rupee',
            'BRL' => 'Brazilian Real',
        ];
    }
    
    /**
     * Get available languages.
     */
    public static function getAvailableLanguages(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'ru' => 'Russian',
            'ja' => 'Japanese',
        ];
    }
    
    /**
     * Get available date formats.
     */
    public static function getAvailableDateFormats(): array
    {
        return [
            'Y-m-d' => 'YYYY-MM-DD (2024-12-31)',
            'm/d/Y' => 'MM/DD/YYYY (12/31/2024)',
            'd/m/Y' => 'DD/MM/YYYY (31/12/2024)',
            'M d, Y' => 'Mon DD, YYYY (Dec 31, 2024)',
            'd M Y' => 'DD Mon YYYY (31 Dec 2024)',
            'j F Y' => 'D Month YYYY (31 December 2024)',
        ];
    }
    
    /**
     * Get available time formats.
     */
    public static function getAvailableTimeFormats(): array
    {
        return [
            'H:i:s' => '24-hour (23:59:59)',
            'H:i' => '24-hour (23:59)',
            'g:i:s A' => '12-hour (11:59:59 PM)',
            'g:i A' => '12-hour (11:59 PM)',
        ];
    }
    
    /**
     * Get available two-factor methods.
     */
    public static function getAvailableTwoFactorMethods(): array
    {
        return [
            'totp' => 'TOTP (Google Authenticator, Authy)',
            'sms' => 'SMS Text Message',
            'email' => 'Email Code',
            'backup_codes' => 'Backup Codes',
            'hardware_token' => 'Hardware Security Key',
        ];
    }
    
    /**
     * Get available encryption methods.
     */
    public static function getAvailableEncryptionMethods(): array
    {
        return [
            'none' => 'None',
            'tls' => 'TLS/STARTTLS',
            'ssl' => 'SSL',
        ];
    }

    /**
     * Get available timezones.
     */
    public static function getAvailableTimezones(): array
    {
        return [
            'America/New_York' => 'Eastern Time',
            'America/Chicago' => 'Central Time',
            'America/Denver' => 'Mountain Time',
            'America/Los_Angeles' => 'Pacific Time',
            'UTC' => 'UTC',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
        ];
    }

    /**
     * Get validation rules for settings.
     */
    public static function getValidationRules(): array
    {
        return [
            // Core settings
            'company_id' => 'required|integer|exists:companies,id',
            'current_database_version' => 'required|string|max:10',
            'start_page' => 'required|string|max:255',
            
            // General & Company
            'company_logo' => 'nullable|string|max:255',
            'company_colors' => 'nullable|array',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_zip' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|size:2',
            'company_phone' => 'nullable|string|max:50',
            'company_website' => 'nullable|url|max:255',
            'company_tax_id' => 'nullable|string|max:50',
            'business_hours' => 'nullable|array',
            'company_holidays' => 'nullable|array',
            'company_language' => 'nullable|string|size:2',
            'company_currency' => 'nullable|string|size:3',
            
            // Security
            'password_min_length' => 'integer|min:6|max:32',
            'password_require_special' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_uppercase' => 'boolean',
            'password_expiry_days' => 'integer|min:30|max:365',
            'password_history_count' => 'integer|min:1|max:24',
            'two_factor_enabled' => 'boolean',
            'two_factor_methods' => 'nullable|array',
            'session_timeout_minutes' => 'integer|min:5|max:1440',
            'force_single_session' => 'boolean',
            'max_login_attempts' => 'integer|min:3|max:10',
            'lockout_duration_minutes' => 'integer|min:5|max:60',
            'allowed_ip_ranges' => 'nullable|array',
            'blocked_ip_ranges' => 'nullable|array',
            'geo_blocking_enabled' => 'boolean',
            'allowed_countries' => 'nullable|array',
            'audit_logging_enabled' => 'boolean',
            'audit_retention_days' => 'integer|min:90|max:2555',
            
            // Email & Communication
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_auth_required' => 'boolean',
            'smtp_use_tls' => 'boolean',
            'smtp_timeout' => 'integer|min:10|max:120',
            'mail_from_email' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'email_retry_attempts' => 'integer|min:1|max:5',
            'email_tracking_enabled' => 'boolean',
            'imap_host' => 'nullable|string|max:255',
            'imap_port' => 'nullable|integer|min:1|max:65535',
            'imap_encryption' => 'nullable|in:tls,ssl,none',
            'imap_username' => 'nullable|string|max:255',
            'imap_password' => 'nullable|string|max:255',
            
            // Financial
            'default_net_terms' => 'nullable|integer|min:0|max:365',
            'default_hourly_rate' => 'numeric|min:0|max:9999.99',
            'multi_currency_enabled' => 'boolean',
            'auto_update_exchange_rates' => 'boolean',
            'recurring_billing_enabled' => 'boolean',
            'revenue_recognition_enabled' => 'boolean',
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_next_number' => 'nullable|integer|min:1',
            'invoice_late_fee_enable' => 'boolean',
            'invoice_late_fee_percent' => 'numeric|min:0|max:100',
            'quote_prefix' => 'nullable|string|max:10',
            'quote_next_number' => 'nullable|integer|min:1',
            'recurring_auto_send_invoice' => 'boolean',
            'send_invoice_reminders' => 'boolean',
            
            // Ticketing
            'ticket_prefix' => 'nullable|string|max:10',
            'ticket_next_number' => 'nullable|integer|min:1',
            'ticket_email_parse' => 'boolean',
            'ticket_client_general_notifications' => 'boolean',
            'ticket_autoclose' => 'boolean',
            'ticket_autoclose_hours' => 'integer|min:1|max:8760',
            'auto_create_tickets_from_alerts' => 'boolean',
            'time_tracking_enabled' => 'boolean',
            'customer_satisfaction_enabled' => 'boolean',
            
            // Project Management
            'project_time_tracking_enabled' => 'boolean',
            
            // Assets
            'barcode_scanning_enabled' => 'boolean',
            'mobile_asset_management_enabled' => 'boolean',
            
            // Portal
            'client_portal_enable' => 'boolean',
            'portal_self_service_tickets' => 'boolean',
            'portal_knowledge_base_access' => 'boolean',
            'portal_invoice_access' => 'boolean',
            'portal_payment_processing' => 'boolean',
            'portal_asset_visibility' => 'boolean',
            
            // Compliance
            'soc2_compliance_enabled' => 'boolean',
            'hipaa_compliance_enabled' => 'boolean',
            'pci_compliance_enabled' => 'boolean',
            'gdpr_compliance_enabled' => 'boolean',
            
            // Backup & Recovery
            'recovery_time_objective' => 'nullable|integer|min:1|max:8760',
            'recovery_point_objective' => 'nullable|integer|min:1|max:1440',
            
            // System
            'theme' => 'required|string|in:blue,green,red,purple,orange,dark',
            'timezone' => 'required|string|max:255',
            'telemetry' => 'boolean',
            'destructive_deletes_enable' => 'boolean',
            'module_enable_itdoc' => 'boolean',
            'module_enable_accounting' => 'boolean',
            'module_enable_ticketing' => 'boolean',
            'enable_cron' => 'boolean',
            'enable_alert_domain_expire' => 'boolean',
            'login_key_required' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate cron key if cron is enabled
        static::saving(function ($setting) {
            if ($setting->enable_cron && empty($setting->cron_key)) {
                $setting->cron_key = bin2hex(random_bytes(32));
            }
            
            // Encrypt sensitive settings
            if (!empty($setting->smtp_password) && !str_starts_with($setting->smtp_password, 'eyJpdiI6')) {
                $setting->smtp_password = encrypt($setting->smtp_password);
            }
            
            if (!empty($setting->imap_password) && !str_starts_with($setting->imap_password, 'eyJpdiI6')) {
                $setting->imap_password = encrypt($setting->imap_password);
            }
            
            // Set default values for new settings
            if (empty($setting->company_country)) {
                $setting->company_country = 'US';
            }
            
            if (empty($setting->company_language)) {
                $setting->company_language = 'en';
            }
            
            if (empty($setting->company_currency)) {
                $setting->company_currency = 'USD';
            }
            
            if (empty($setting->password_min_length)) {
                $setting->password_min_length = 8;
            }
            
            if (empty($setting->session_timeout_minutes)) {
                $setting->session_timeout_minutes = 480;
            }
            
            if (empty($setting->audit_retention_days)) {
                $setting->audit_retention_days = 365;
            }
        });
    }

    /**
     * Get PayPal enabled status from JSON settings
     */
    public function getPaypalEnabledAttribute(): bool
    {
        $settings = $this->paypal_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get Stripe enabled status from JSON settings
     */
    public function getStripeEnabledAttribute(): bool
    {
        $settings = $this->stripe_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get Square enabled status from JSON settings
     */
    public function getSquareEnabledAttribute(): bool
    {
        $settings = $this->square_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get Authorize.Net enabled status from JSON settings
     */
    public function getAuthorizeNetEnabledAttribute(): bool
    {
        $settings = $this->authorize_net_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get PayPal Client ID from JSON settings
     */
    public function getPaypalClientIdAttribute(): ?string
    {
        $settings = $this->paypal_settings ?? [];
        return $settings['client_id'] ?? null;
    }

    /**
     * Get Stripe Publishable Key from JSON settings
     */
    public function getStripePublishableKeyAttribute(): ?string
    {
        $settings = $this->stripe_settings ?? [];
        return $settings['publishable_key'] ?? null;
    }

    /**
     * Get ACH enabled status from JSON settings
     */
    public function getAchEnabledAttribute(): bool
    {
        $settings = $this->ach_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get ACH Bank Name from JSON settings
     */
    public function getAchBankNameAttribute(): ?string
    {
        $settings = $this->ach_settings ?? [];
        return $settings['bank_name'] ?? null;
    }

    /**
     * Get ACH Routing Number from JSON settings
     */
    public function getAchRoutingNumberAttribute(): ?string
    {
        $settings = $this->ach_settings ?? [];
        return $settings['routing_number'] ?? null;
    }

    /**
     * Get ACH Account Number from JSON settings
     */
    public function getAchAccountNumberAttribute(): ?string
    {
        $settings = $this->ach_settings ?? [];
        return $settings['account_number'] ?? null;
    }

    /**
     * Get Wire enabled status from JSON settings
     */
    public function getWireEnabledAttribute(): bool
    {
        $settings = $this->wire_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get Wire Bank Name from JSON settings
     */
    public function getWireBankNameAttribute(): ?string
    {
        $settings = $this->wire_settings ?? [];
        return $settings['bank_name'] ?? null;
    }

    /**
     * Get Wire SWIFT Code from JSON settings
     */
    public function getWireSwiftCodeAttribute(): ?string
    {
        $settings = $this->wire_settings ?? [];
        return $settings['swift_code'] ?? null;
    }

    /**
     * Get Wire Account Number from JSON settings
     */
    public function getWireAccountNumberAttribute(): ?string
    {
        $settings = $this->wire_settings ?? [];
        return $settings['account_number'] ?? null;
    }

    /**
     * Get Check enabled status from JSON settings
     */
    public function getCheckEnabledAttribute(): bool
    {
        $settings = $this->check_settings ?? [];
        return $settings['enabled'] ?? false;
    }

    /**
     * Get Check Pay To Name from JSON settings
     */
    public function getCheckPaytoNameAttribute(): ?string
    {
        $settings = $this->check_settings ?? [];
        return $settings['payto_name'] ?? null;
    }

    /**
     * Get Check Mailing Address from JSON settings
     */
    public function getCheckMailingAddressAttribute(): ?string
    {
        $settings = $this->check_settings ?? [];
        return $settings['mailing_address'] ?? null;
    }
}