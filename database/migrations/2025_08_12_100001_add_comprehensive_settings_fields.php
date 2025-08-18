<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get existing columns to avoid conflicts
        $existingColumns = collect(Schema::getColumnListing('settings'));
        
        Schema::table('settings', function (Blueprint $table) use ($existingColumns) {
            // General & Company Settings
            $table->string('company_logo')->nullable()->after('company_id');
            $table->json('company_colors')->nullable()->after('company_logo');
            $table->string('company_address')->nullable()->after('company_colors');
            $table->string('company_city')->nullable()->after('company_address');
            $table->string('company_state')->nullable()->after('company_city');
            $table->string('company_zip')->nullable()->after('company_state');
            $table->string('company_country')->default('US')->after('company_zip');
            $table->string('company_phone')->nullable()->after('company_country');
            $table->string('company_website')->nullable()->after('company_phone');
            $table->string('company_tax_id')->nullable()->after('company_website');
            $table->json('business_hours')->nullable()->after('company_tax_id');
            $table->json('company_holidays')->nullable()->after('business_hours');
            $table->string('company_language')->default('en')->after('company_holidays');
            $table->string('company_currency')->default('USD')->after('company_language');
            $table->json('custom_fields')->nullable()->after('company_currency');
            $table->json('localization_settings')->nullable()->after('custom_fields');

            // Enhanced Security & Access Control
            $table->integer('password_min_length')->default(8)->after('login_key_secret');
            $table->boolean('password_require_special')->default(true)->after('password_min_length');
            $table->boolean('password_require_numbers')->default(true)->after('password_require_special');
            $table->boolean('password_require_uppercase')->default(true)->after('password_require_numbers');
            $table->integer('password_expiry_days')->default(90)->after('password_require_uppercase');
            $table->integer('password_history_count')->default(5)->after('password_expiry_days');
            $table->boolean('two_factor_enabled')->default(false)->after('password_history_count');
            $table->json('two_factor_methods')->nullable()->after('two_factor_enabled');
            $table->integer('session_timeout_minutes')->default(480)->after('two_factor_methods');
            $table->boolean('force_single_session')->default(false)->after('session_timeout_minutes');
            $table->integer('max_login_attempts')->default(5)->after('force_single_session');
            $table->integer('lockout_duration_minutes')->default(15)->after('max_login_attempts');
            $table->json('allowed_ip_ranges')->nullable()->after('lockout_duration_minutes');
            $table->json('blocked_ip_ranges')->nullable()->after('allowed_ip_ranges');
            $table->boolean('geo_blocking_enabled')->default(false)->after('blocked_ip_ranges');
            $table->json('allowed_countries')->nullable()->after('geo_blocking_enabled');
            $table->json('sso_settings')->nullable()->after('allowed_countries');
            $table->boolean('audit_logging_enabled')->default(true)->after('sso_settings');
            $table->integer('audit_retention_days')->default(365)->after('audit_logging_enabled');

            // Enhanced Email & Communication
            $table->boolean('smtp_auth_required')->default(true)->after('imap_password');
            $table->boolean('smtp_use_tls')->default(true)->after('smtp_auth_required');
            $table->integer('smtp_timeout')->default(30)->after('smtp_use_tls');
            $table->integer('email_retry_attempts')->default(3)->after('smtp_timeout');
            $table->json('email_templates')->nullable()->after('email_retry_attempts');
            $table->json('email_signatures')->nullable()->after('email_templates');
            $table->boolean('email_tracking_enabled')->default(false)->after('email_signatures');
            $table->json('sms_settings')->nullable()->after('email_tracking_enabled');
            $table->json('voice_settings')->nullable()->after('sms_settings');
            $table->json('slack_settings')->nullable()->after('voice_settings');
            $table->json('teams_settings')->nullable()->after('slack_settings');
            $table->json('discord_settings')->nullable()->after('teams_settings');
            $table->json('video_conferencing_settings')->nullable()->after('discord_settings');
            $table->json('communication_preferences')->nullable()->after('video_conferencing_settings');
            $table->time('quiet_hours_start')->nullable()->after('communication_preferences');
            $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');

            // Billing & Financial Management
            $table->boolean('multi_currency_enabled')->default(false)->after('default_hourly_rate');
            $table->json('supported_currencies')->nullable()->after('multi_currency_enabled');
            $table->string('exchange_rate_provider')->nullable()->after('supported_currencies');
            $table->boolean('auto_update_exchange_rates')->default(true)->after('exchange_rate_provider');
            $table->json('tax_calculation_settings')->nullable()->after('auto_update_exchange_rates');
            $table->string('tax_engine_provider')->nullable()->after('tax_calculation_settings');
            $table->json('payment_gateway_settings')->nullable()->after('tax_engine_provider');
            $table->json('stripe_settings')->nullable()->after('payment_gateway_settings');
            $table->json('square_settings')->nullable()->after('stripe_settings');
            $table->json('paypal_settings')->nullable()->after('square_settings');
            $table->json('authorize_net_settings')->nullable()->after('paypal_settings');
            $table->json('ach_settings')->nullable()->after('authorize_net_settings');
            $table->boolean('recurring_billing_enabled')->default(true)->after('ach_settings');
            $table->json('recurring_billing_settings')->nullable()->after('recurring_billing_enabled');
            $table->json('late_fee_settings')->nullable()->after('recurring_billing_settings');
            $table->json('collection_settings')->nullable()->after('late_fee_settings');
            $table->json('accounting_integration_settings')->nullable()->after('collection_settings');
            $table->json('quickbooks_settings')->nullable()->after('accounting_integration_settings');
            $table->json('xero_settings')->nullable()->after('quickbooks_settings');
            $table->json('sage_settings')->nullable()->after('xero_settings');
            $table->boolean('revenue_recognition_enabled')->default(false)->after('sage_settings');
            $table->json('revenue_recognition_settings')->nullable()->after('revenue_recognition_enabled');
            $table->json('purchase_order_settings')->nullable()->after('revenue_recognition_settings');
            $table->json('expense_approval_settings')->nullable()->after('purchase_order_settings');

            // RMM & Monitoring Integrations
            $table->json('connectwise_automate_settings')->nullable()->after('expense_approval_settings');
            $table->json('datto_rmm_settings')->nullable()->after('connectwise_automate_settings');
            $table->json('ninja_rmm_settings')->nullable()->after('datto_rmm_settings');
            $table->json('kaseya_vsa_settings')->nullable()->after('ninja_rmm_settings');
            $table->json('auvik_settings')->nullable()->after('kaseya_vsa_settings');
            $table->json('prtg_settings')->nullable()->after('auvik_settings');
            $table->json('solarwinds_settings')->nullable()->after('prtg_settings');
            $table->json('monitoring_alert_thresholds')->nullable()->after('solarwinds_settings');
            $table->json('escalation_rules')->nullable()->after('monitoring_alert_thresholds');
            $table->json('asset_discovery_settings')->nullable()->after('escalation_rules');
            $table->json('patch_management_settings')->nullable()->after('asset_discovery_settings');
            $table->json('remote_access_settings')->nullable()->after('patch_management_settings');
            $table->boolean('auto_create_tickets_from_alerts')->default(false)->after('remote_access_settings');
            $table->json('alert_to_ticket_mapping')->nullable()->after('auto_create_tickets_from_alerts');

            // Enhanced Ticketing & Service Desk
            $table->json('ticket_categorization_rules')->nullable()->after('ticket_new_ticket_notification_email');
            $table->json('ticket_priority_rules')->nullable()->after('ticket_categorization_rules');
            $table->json('sla_definitions')->nullable()->after('ticket_priority_rules');
            $table->json('sla_escalation_policies')->nullable()->after('sla_definitions');
            $table->json('auto_assignment_rules')->nullable()->after('sla_escalation_policies');
            $table->json('routing_logic')->nullable()->after('auto_assignment_rules');
            $table->json('approval_workflows')->nullable()->after('routing_logic');
            $table->boolean('time_tracking_enabled')->default(true)->after('approval_workflows');
            $table->json('time_tracking_settings')->nullable()->after('time_tracking_enabled');
            $table->boolean('customer_satisfaction_enabled')->default(false)->after('time_tracking_settings');
            $table->json('csat_settings')->nullable()->after('customer_satisfaction_enabled');
            $table->json('ticket_templates')->nullable()->after('csat_settings');
            $table->json('ticket_automation_rules')->nullable()->after('ticket_templates');
            $table->json('multichannel_settings')->nullable()->after('ticket_automation_rules');
            if (!$existingColumns->contains('queue_management_settings')) {
                $table->json('queue_management_settings')->nullable()->after('multichannel_settings');
            }

            // Project Management
            $table->json('project_templates')->nullable()->after('queue_management_settings');
            $table->json('project_standardization_settings')->nullable()->after('project_templates');
            $table->json('resource_allocation_settings')->nullable()->after('project_standardization_settings');
            $table->json('capacity_planning_settings')->nullable()->after('resource_allocation_settings');
            $table->boolean('project_time_tracking_enabled')->default(true)->after('capacity_planning_settings');
            $table->json('project_billing_settings')->nullable()->after('project_time_tracking_enabled');
            $table->json('milestone_settings')->nullable()->after('project_billing_settings');
            $table->json('deliverable_settings')->nullable()->after('milestone_settings');
            $table->json('gantt_chart_settings')->nullable()->after('deliverable_settings');
            $table->json('budget_management_settings')->nullable()->after('gantt_chart_settings');
            $table->json('profitability_tracking_settings')->nullable()->after('budget_management_settings');
            $table->json('change_request_workflows')->nullable()->after('profitability_tracking_settings');
            $table->json('project_collaboration_settings')->nullable()->after('change_request_workflows');
            $table->json('document_management_settings')->nullable()->after('project_collaboration_settings');

            // Asset & Inventory Management
            $table->json('asset_discovery_rules')->nullable()->after('document_management_settings');
            $table->json('asset_lifecycle_settings')->nullable()->after('asset_discovery_rules');
            $table->json('software_license_settings')->nullable()->after('asset_lifecycle_settings');
            $table->json('hardware_warranty_settings')->nullable()->after('software_license_settings');
            $table->json('procurement_settings')->nullable()->after('hardware_warranty_settings');
            $table->json('vendor_management_settings')->nullable()->after('procurement_settings');
            $table->json('asset_depreciation_settings')->nullable()->after('vendor_management_settings');
            $table->json('asset_tracking_settings')->nullable()->after('asset_depreciation_settings');
            $table->boolean('barcode_scanning_enabled')->default(false)->after('asset_tracking_settings');
            $table->json('barcode_settings')->nullable()->after('barcode_scanning_enabled');
            $table->boolean('mobile_asset_management_enabled')->default(false)->after('barcode_settings');
            $table->json('asset_relationship_settings')->nullable()->after('mobile_asset_management_enabled');
            $table->json('asset_compliance_settings')->nullable()->after('asset_relationship_settings');

            // Client Portal & Self-Service
            $table->json('portal_branding_settings')->nullable()->after('client_portal_enable');
            $table->json('portal_customization_settings')->nullable()->after('portal_branding_settings');
            $table->json('portal_access_controls')->nullable()->after('portal_customization_settings');
            $table->json('portal_feature_toggles')->nullable()->after('portal_access_controls');
            $table->boolean('portal_self_service_tickets')->default(true)->after('portal_feature_toggles');
            $table->boolean('portal_knowledge_base_access')->default(true)->after('portal_self_service_tickets');
            $table->boolean('portal_invoice_access')->default(true)->after('portal_knowledge_base_access');
            $table->boolean('portal_payment_processing')->default(false)->after('portal_invoice_access');
            $table->boolean('portal_asset_visibility')->default(false)->after('portal_payment_processing');
            $table->json('portal_sso_settings')->nullable()->after('portal_asset_visibility');
            $table->json('portal_mobile_settings')->nullable()->after('portal_sso_settings');
            $table->json('portal_dashboard_settings')->nullable()->after('portal_mobile_settings');

            // Automation & Workflows
            $table->json('business_rule_engine_settings')->nullable()->after('portal_dashboard_settings');
            $table->json('workflow_automation_templates')->nullable()->after('business_rule_engine_settings');
            $table->json('rpa_bot_settings')->nullable()->after('workflow_automation_templates');
            $table->json('event_driven_automation')->nullable()->after('rpa_bot_settings');
            $table->json('custom_script_policies')->nullable()->after('event_driven_automation');
            $table->json('integration_middleware_settings')->nullable()->after('custom_script_policies');
            $table->json('data_synchronization_rules')->nullable()->after('integration_middleware_settings');
            $table->json('notification_automation_settings')->nullable()->after('data_synchronization_rules');
            $table->json('approval_process_automation')->nullable()->after('notification_automation_settings');
            $table->json('document_generation_automation')->nullable()->after('approval_process_automation');

            // Compliance & Regulatory
            $table->boolean('soc2_compliance_enabled')->default(false)->after('document_generation_automation');
            $table->json('soc2_settings')->nullable()->after('soc2_compliance_enabled');
            $table->boolean('hipaa_compliance_enabled')->default(false)->after('soc2_settings');
            $table->json('hipaa_settings')->nullable()->after('hipaa_compliance_enabled');
            $table->boolean('pci_compliance_enabled')->default(false)->after('hipaa_settings');
            $table->json('pci_settings')->nullable()->after('pci_compliance_enabled');
            $table->boolean('gdpr_compliance_enabled')->default(false)->after('pci_settings');
            $table->json('gdpr_settings')->nullable()->after('gdpr_compliance_enabled');
            $table->json('industry_compliance_settings')->nullable()->after('gdpr_settings');
            $table->json('data_retention_policies')->nullable()->after('industry_compliance_settings');
            $table->json('data_destruction_policies')->nullable()->after('data_retention_policies');
            $table->json('risk_assessment_settings')->nullable()->after('data_destruction_policies');
            $table->json('vendor_compliance_settings')->nullable()->after('risk_assessment_settings');
            $table->json('incident_response_settings')->nullable()->after('vendor_compliance_settings');

            // Backup & Disaster Recovery
            $table->json('backup_policies')->nullable()->after('incident_response_settings');
            $table->json('backup_schedules')->nullable()->after('backup_policies');
            $table->integer('recovery_time_objective')->nullable()->after('backup_schedules');
            $table->integer('recovery_point_objective')->nullable()->after('recovery_time_objective');
            $table->json('disaster_recovery_procedures')->nullable()->after('recovery_point_objective');
            $table->json('data_replication_settings')->nullable()->after('disaster_recovery_procedures');
            $table->json('business_continuity_settings')->nullable()->after('data_replication_settings');
            $table->json('testing_validation_schedules')->nullable()->after('business_continuity_settings');
            $table->json('cloud_backup_settings')->nullable()->after('testing_validation_schedules');
            $table->json('ransomware_protection_settings')->nullable()->after('cloud_backup_settings');
            $table->json('recovery_documentation_settings')->nullable()->after('ransomware_protection_settings');

            // Performance & System Optimization
            $table->json('system_resource_monitoring')->nullable()->after('recovery_documentation_settings');
            $table->json('performance_tuning_settings')->nullable()->after('system_resource_monitoring');
            $table->json('caching_strategies')->nullable()->after('performance_tuning_settings');
            $table->json('database_optimization_settings')->nullable()->after('caching_strategies');
            $table->json('cdn_load_balancing_settings')->nullable()->after('database_optimization_settings');
            $table->json('api_performance_settings')->nullable()->after('cdn_load_balancing_settings');
            // Note: queue_management_settings already handled above
            $table->json('search_optimization_settings')->nullable()->after('api_performance_settings');
            $table->json('mobile_performance_settings')->nullable()->after('search_optimization_settings');
            $table->json('system_health_monitoring')->nullable()->after('mobile_performance_settings');

            // Reporting & Analytics
            $table->json('custom_dashboard_settings')->nullable()->after('system_health_monitoring');
            $table->json('report_templates')->nullable()->after('custom_dashboard_settings');
            $table->json('report_scheduling_settings')->nullable()->after('report_templates');
            $table->json('kpi_metric_definitions')->nullable()->after('report_scheduling_settings');
            $table->json('data_visualization_settings')->nullable()->after('kpi_metric_definitions');
            $table->json('executive_summary_settings')->nullable()->after('data_visualization_settings');
            $table->json('benchmarking_settings')->nullable()->after('executive_summary_settings');
            $table->json('predictive_analytics_settings')->nullable()->after('benchmarking_settings');
            $table->json('export_delivery_settings')->nullable()->after('predictive_analytics_settings');
            $table->json('data_warehouse_settings')->nullable()->after('export_delivery_settings');
            $table->json('business_intelligence_settings')->nullable()->after('data_warehouse_settings');

            // Notifications & Alerts
            $table->json('multichannel_notification_settings')->nullable()->after('business_intelligence_settings');
            $table->json('alert_escalation_policies')->nullable()->after('multichannel_notification_settings');
            $table->json('notification_quiet_hours')->nullable()->after('alert_escalation_policies');
            $table->json('notification_templates')->nullable()->after('notification_quiet_hours');
            $table->json('notification_frequency_rules')->nullable()->after('notification_templates');
            $table->json('notification_batching_rules')->nullable()->after('notification_frequency_rules');
            $table->json('emergency_notification_settings')->nullable()->after('notification_batching_rules');
            $table->json('mobile_push_settings')->nullable()->after('emergency_notification_settings');
            $table->json('webhook_notification_settings')->nullable()->after('mobile_push_settings');
            $table->json('custom_notification_rules')->nullable()->after('webhook_notification_settings');
            $table->json('notification_localization_settings')->nullable()->after('custom_notification_rules');

            // API & Integration Management
            $table->json('api_key_management_settings')->nullable()->after('notification_localization_settings');
            $table->json('api_rate_limiting_settings')->nullable()->after('api_key_management_settings');
            $table->json('api_throttling_settings')->nullable()->after('api_rate_limiting_settings');
            $table->json('webhook_configuration_settings')->nullable()->after('api_throttling_settings');
            $table->json('third_party_integration_settings')->nullable()->after('webhook_configuration_settings');
            $table->json('data_mapping_settings')->nullable()->after('third_party_integration_settings');
            $table->json('sync_scheduling_settings')->nullable()->after('data_mapping_settings');
            $table->json('integration_monitoring_settings')->nullable()->after('sync_scheduling_settings');
            $table->json('custom_connector_settings')->nullable()->after('integration_monitoring_settings');
            $table->json('marketplace_integration_settings')->nullable()->after('custom_connector_settings');
            $table->json('legacy_system_bridge_settings')->nullable()->after('marketplace_integration_settings');

            // Mobile & Remote Access
            $table->json('mobile_app_settings')->nullable()->after('legacy_system_bridge_settings');
            $table->json('offline_mode_settings')->nullable()->after('mobile_app_settings');
            $table->json('mobile_push_notification_settings')->nullable()->after('offline_mode_settings');
            $table->json('remote_access_policies')->nullable()->after('mobile_push_notification_settings');
            $table->json('gps_tracking_settings')->nullable()->after('remote_access_policies');
            $table->json('mobile_device_management_settings')->nullable()->after('gps_tracking_settings');
            $table->json('byod_policies')->nullable()->after('mobile_device_management_settings');
            $table->json('mobile_feature_settings')->nullable()->after('byod_policies');
            $table->json('mobile_sync_settings')->nullable()->after('mobile_feature_settings');
            $table->json('mobile_battery_optimization')->nullable()->after('mobile_sync_settings');

            // Training & Documentation
            $table->json('knowledge_base_settings')->nullable()->after('mobile_battery_optimization');
            $table->json('training_module_settings')->nullable()->after('knowledge_base_settings');
            $table->json('documentation_standards')->nullable()->after('training_module_settings');
            $table->json('video_library_settings')->nullable()->after('documentation_standards');
            $table->json('certification_tracking_settings')->nullable()->after('video_library_settings');
            $table->json('skills_assessment_settings')->nullable()->after('certification_tracking_settings');
            $table->json('learning_path_settings')->nullable()->after('skills_assessment_settings');
            $table->json('external_training_integration_settings')->nullable()->after('learning_path_settings');
            $table->json('documentation_versioning_settings')->nullable()->after('external_training_integration_settings');
            $table->json('search_discovery_settings')->nullable()->after('documentation_versioning_settings');
            
            // User Management Settings (consolidated)
            $table->json('user_management_settings')->nullable()->after('search_discovery_settings');

            // Add indexes for performance
            $table->index(['company_id', 'multi_currency_enabled']);
            $table->index(['company_id', 'two_factor_enabled']);
            $table->index(['company_id', 'audit_logging_enabled']);
            $table->index(['company_id', 'recurring_billing_enabled']);
            $table->index(['company_id', 'auto_create_tickets_from_alerts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Remove all the new columns in reverse order
            $table->dropColumn([
                'user_management_settings',
                'search_discovery_settings',
                'documentation_versioning_settings',
                'external_training_integration_settings',
                'learning_path_settings',
                'skills_assessment_settings',
                'certification_tracking_settings',
                'video_library_settings',
                'documentation_standards',
                'training_module_settings',
                'knowledge_base_settings',
                'mobile_battery_optimization',
                'mobile_sync_settings',
                'mobile_feature_settings',
                'byod_policies',
                'mobile_device_management_settings',
                'gps_tracking_settings',
                'remote_access_policies',
                'mobile_push_notification_settings',
                'offline_mode_settings',
                'mobile_app_settings',
                'legacy_system_bridge_settings',
                'marketplace_integration_settings',
                'custom_connector_settings',
                'integration_monitoring_settings',
                'sync_scheduling_settings',
                'data_mapping_settings',
                'third_party_integration_settings',
                'webhook_configuration_settings',
                'api_throttling_settings',
                'api_rate_limiting_settings',
                'api_key_management_settings',
                'notification_localization_settings',
                'custom_notification_rules',
                'webhook_notification_settings',
                'mobile_push_settings',
                'emergency_notification_settings',
                'notification_batching_rules',
                'notification_frequency_rules',
                'notification_templates',
                'notification_quiet_hours',
                'alert_escalation_policies',
                'multichannel_notification_settings',
                'business_intelligence_settings',
                'data_warehouse_settings',
                'export_delivery_settings',
                'predictive_analytics_settings',
                'benchmarking_settings',
                'executive_summary_settings',
                'data_visualization_settings',
                'kpi_metric_definitions',
                'report_scheduling_settings',
                'report_templates',
                'custom_dashboard_settings',
                'system_health_monitoring',
                'mobile_performance_settings',
                'search_optimization_settings',
                'queue_management_settings',
                'api_performance_settings',
                'cdn_load_balancing_settings',
                'database_optimization_settings',
                'caching_strategies',
                'performance_tuning_settings',
                'system_resource_monitoring',
                'recovery_documentation_settings',
                'ransomware_protection_settings',
                'cloud_backup_settings',
                'testing_validation_schedules',
                'business_continuity_settings',
                'data_replication_settings',
                'disaster_recovery_procedures',
                'recovery_point_objective',
                'recovery_time_objective',
                'backup_schedules',
                'backup_policies',
                'incident_response_settings',
                'vendor_compliance_settings',
                'risk_assessment_settings',
                'data_destruction_policies',
                'data_retention_policies',
                'industry_compliance_settings',
                'gdpr_settings',
                'gdpr_compliance_enabled',
                'pci_settings',
                'pci_compliance_enabled',
                'hipaa_settings',
                'hipaa_compliance_enabled',
                'soc2_settings',
                'soc2_compliance_enabled',
                'document_generation_automation',
                'approval_process_automation',
                'notification_automation_settings',
                'data_synchronization_rules',
                'integration_middleware_settings',
                'custom_script_policies',
                'event_driven_automation',
                'rpa_bot_settings',
                'workflow_automation_templates',
                'business_rule_engine_settings',
                'portal_dashboard_settings',
                'portal_mobile_settings',
                'portal_sso_settings',
                'portal_asset_visibility',
                'portal_payment_processing',
                'portal_invoice_access',
                'portal_knowledge_base_access',
                'portal_self_service_tickets',
                'portal_feature_toggles',
                'portal_access_controls',
                'portal_customization_settings',
                'portal_branding_settings',
                'asset_compliance_settings',
                'asset_relationship_settings',
                'mobile_asset_management_enabled',
                'barcode_settings',
                'barcode_scanning_enabled',
                'asset_tracking_settings',
                'asset_depreciation_settings',
                'vendor_management_settings',
                'procurement_settings',
                'hardware_warranty_settings',
                'software_license_settings',
                'asset_lifecycle_settings',
                'asset_discovery_rules',
                'document_management_settings',
                'project_collaboration_settings',
                'change_request_workflows',
                'profitability_tracking_settings',
                'budget_management_settings',
                'gantt_chart_settings',
                'deliverable_settings',
                'milestone_settings',
                'project_billing_settings',
                'project_time_tracking_enabled',
                'capacity_planning_settings',
                'resource_allocation_settings',
                'project_standardization_settings',
                'project_templates',
                'queue_management_settings',
                'multichannel_settings',
                'ticket_automation_rules',
                'ticket_templates',
                'csat_settings',
                'customer_satisfaction_enabled',
                'time_tracking_settings',
                'time_tracking_enabled',
                'approval_workflows',
                'routing_logic',
                'auto_assignment_rules',
                'sla_escalation_policies',
                'sla_definitions',
                'ticket_priority_rules',
                'ticket_categorization_rules',
                'alert_to_ticket_mapping',
                'auto_create_tickets_from_alerts',
                'remote_access_settings',
                'patch_management_settings',
                'asset_discovery_settings',
                'escalation_rules',
                'monitoring_alert_thresholds',
                'solarwinds_settings',
                'prtg_settings',
                'auvik_settings',
                'kaseya_vsa_settings',
                'ninja_rmm_settings',
                'datto_rmm_settings',
                'connectwise_automate_settings',
                'expense_approval_settings',
                'purchase_order_settings',
                'revenue_recognition_settings',
                'revenue_recognition_enabled',
                'sage_settings',
                'xero_settings',
                'quickbooks_settings',
                'accounting_integration_settings',
                'collection_settings',
                'late_fee_settings',
                'recurring_billing_settings',
                'recurring_billing_enabled',
                'ach_settings',
                'authorize_net_settings',
                'paypal_settings',
                'square_settings',
                'stripe_settings',
                'payment_gateway_settings',
                'tax_engine_provider',
                'tax_calculation_settings',
                'auto_update_exchange_rates',
                'exchange_rate_provider',
                'supported_currencies',
                'multi_currency_enabled',
                'quiet_hours_end',
                'quiet_hours_start',
                'communication_preferences',
                'video_conferencing_settings',
                'discord_settings',
                'teams_settings',
                'slack_settings',
                'voice_settings',
                'sms_settings',
                'email_tracking_enabled',
                'email_signatures',
                'email_templates',
                'email_retry_attempts',
                'smtp_timeout',
                'smtp_use_tls',
                'smtp_auth_required',
                'audit_retention_days',
                'audit_logging_enabled',
                'sso_settings',
                'allowed_countries',
                'geo_blocking_enabled',
                'blocked_ip_ranges',
                'allowed_ip_ranges',
                'lockout_duration_minutes',
                'max_login_attempts',
                'force_single_session',
                'session_timeout_minutes',
                'two_factor_methods',
                'two_factor_enabled',
                'password_history_count',
                'password_expiry_days',
                'password_require_uppercase',
                'password_require_numbers',
                'password_require_special',
                'password_min_length',
                'localization_settings',
                'custom_fields',
                'company_currency',
                'company_language',
                'company_holidays',
                'business_hours',
                'company_tax_id',
                'company_website',
                'company_phone',
                'company_country',
                'company_zip',
                'company_state',
                'company_city',
                'company_address',
                'company_colors',
                'company_logo'
            ]);
        });
    }
};