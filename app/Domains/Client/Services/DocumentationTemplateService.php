<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Models\ClientITDocumentation;

class DocumentationTemplateService
{
    /**
     * Available documentation tabs with configuration
     */
    public function getAvailableTabs(): array
    {
        return [
            'general' => [
                'id' => 'general',
                'name' => 'General Information',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'description' => 'Basic documentation details, metadata, and categorization',
                'required' => true,
                'fields' => ['name', 'description', 'it_category', 'status', 'effective_date', 'expiry_date'],
            ],
            'technical' => [
                'id' => 'technical',
                'name' => 'Technical Configuration',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'description' => 'System configurations, IP addresses, ports, APIs, and technical specifications',
                'required' => false,
                'fields' => ['ip_addresses', 'ports', 'software_versions', 'api_endpoints', 'ssl_certificates', 'dns_entries', 'firewall_rules', 'vpn_settings', 'hardware_references', 'environment_variables'],
            ],
            'procedures' => [
                'id' => 'procedures',
                'name' => 'Procedures & Workflows',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'description' => 'Step-by-step procedures, workflows, and rollback plans',
                'required' => false,
                'fields' => ['procedure_steps', 'procedure_diagram', 'rollback_procedures', 'prerequisites'],
            ],
            'network' => [
                'id' => 'network',
                'name' => 'Network Infrastructure',
                'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
                'description' => 'Network diagrams, topology, and infrastructure documentation',
                'required' => false,
                'fields' => ['network_diagram', 'system_references'],
            ],
            'compliance' => [
                'id' => 'compliance',
                'name' => 'Compliance & Security',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'description' => 'Compliance requirements, security controls, and audit documentation',
                'required' => false,
                'fields' => ['compliance_requirements', 'data_classification', 'encryption_required', 'audit_requirements', 'security_controls', 'access_level'],
            ],
            'resources' => [
                'id' => 'resources',
                'name' => 'Resources & Attachments',
                'icon' => 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'description' => 'External resources, vendor contacts, and support information',
                'required' => false,
                'fields' => ['external_resources', 'vendor_contacts', 'support_contracts', 'related_entities'],
            ],
            'testing' => [
                'id' => 'testing',
                'name' => 'Testing & Validation',
                'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                'description' => 'Test cases, validation checklists, and performance benchmarks',
                'required' => false,
                'fields' => ['test_cases', 'validation_checklist', 'performance_benchmarks', 'health_checks'],
            ],
            'automation' => [
                'id' => 'automation',
                'name' => 'Automation & Integration',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'description' => 'Automation scripts, integrations, webhooks, and scheduled tasks',
                'required' => false,
                'fields' => ['automation_scripts', 'integrations', 'webhooks', 'scheduled_tasks'],
            ],
            'monitoring' => [
                'id' => 'monitoring',
                'name' => 'Monitoring & Metrics',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'description' => 'SLA requirements, performance metrics, and alert configurations',
                'required' => false,
                'fields' => ['uptime_requirement', 'rto', 'rpo', 'performance_metrics', 'alert_thresholds', 'escalation_paths'],
            ],
            'history' => [
                'id' => 'history',
                'name' => 'History & Versioning',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'description' => 'Change history, approvals, and version control',
                'required' => false,
                'fields' => ['version', 'change_summary', 'change_log', 'requires_technical_review', 'requires_management_approval', 'approval_history', 'review_comments', 'review_schedule', 'last_reviewed_at', 'next_review_at'],
            ],
        ];
    }

    /**
     * Get default tab configuration based on documentation category
     */
    public function getDefaultTabsForCategory(string $category): array
    {
        $defaultConfigs = [
            'runbook' => ['general', 'technical', 'procedures', 'testing', 'monitoring'],
            'troubleshooting' => ['general', 'procedures', 'technical', 'resources'],
            'architecture' => ['general', 'technical', 'network', 'resources'],
            'backup_recovery' => ['general', 'procedures', 'technical', 'testing', 'monitoring'],
            'monitoring' => ['general', 'technical', 'monitoring', 'automation'],
            'change_management' => ['general', 'procedures', 'history', 'testing'],
            'business_continuity' => ['general', 'procedures', 'resources', 'testing', 'monitoring'],
            'user_guide' => ['general', 'procedures', 'resources'],
            'compliance' => ['general', 'compliance', 'procedures', 'history'],
            'vendor_procedure' => ['general', 'procedures', 'resources', 'technical'],
        ];

        return $defaultConfigs[$category] ?? ['general', 'technical', 'procedures'];
    }

    /**
     * Get predefined documentation templates
     */
    public function getTemplates(): array
    {
        return [
            'server_deployment' => [
                'name' => 'Server Deployment',
                'description' => 'Template for server deployment documentation',
                'category' => 'runbook',
                'enabled_tabs' => ['general', 'technical', 'procedures', 'testing', 'monitoring'],
                'template_data' => [
                    'procedure_steps' => [
                        ['order' => 1, 'title' => 'Pre-deployment Checklist', 'description' => 'Verify prerequisites', 'duration' => '15 minutes'],
                        ['order' => 2, 'title' => 'OS Installation', 'description' => 'Install operating system', 'duration' => '30 minutes'],
                        ['order' => 3, 'title' => 'Network Configuration', 'description' => 'Configure network settings', 'duration' => '20 minutes'],
                        ['order' => 4, 'title' => 'Security Hardening', 'description' => 'Apply security configurations', 'duration' => '45 minutes'],
                        ['order' => 5, 'title' => 'Application Installation', 'description' => 'Install required applications', 'duration' => '60 minutes'],
                        ['order' => 6, 'title' => 'Testing & Validation', 'description' => 'Run validation tests', 'duration' => '30 minutes'],
                    ],
                    'prerequisites' => [
                        'Valid server hardware or VM allocated',
                        'Network VLAN configured',
                        'IP address assigned',
                        'DNS entries prepared',
                        'Firewall rules documented',
                        'Backup strategy defined',
                    ],
                    'test_cases' => [
                        ['name' => 'Network Connectivity', 'description' => 'Verify network access', 'expected_result' => 'Ping successful'],
                        ['name' => 'Service Availability', 'description' => 'Check all services running', 'expected_result' => 'All services active'],
                        ['name' => 'Security Scan', 'description' => 'Run security vulnerability scan', 'expected_result' => 'No critical vulnerabilities'],
                    ],
                ],
            ],
            'database_backup' => [
                'name' => 'Database Backup & Recovery',
                'description' => 'Template for database backup procedures',
                'category' => 'backup_recovery',
                'enabled_tabs' => ['general', 'procedures', 'technical', 'automation', 'monitoring'],
                'template_data' => [
                    'procedure_steps' => [
                        ['order' => 1, 'title' => 'Pre-backup Validation', 'description' => 'Check database status', 'duration' => '5 minutes'],
                        ['order' => 2, 'title' => 'Backup Execution', 'description' => 'Run backup process', 'duration' => '30-120 minutes'],
                        ['order' => 3, 'title' => 'Backup Verification', 'description' => 'Verify backup integrity', 'duration' => '10 minutes'],
                        ['order' => 4, 'title' => 'Off-site Transfer', 'description' => 'Transfer to remote storage', 'duration' => '15-60 minutes'],
                        ['order' => 5, 'title' => 'Retention Management', 'description' => 'Apply retention policies', 'duration' => '5 minutes'],
                    ],
                    'rollback_procedures' => [
                        ['order' => 1, 'title' => 'Identify Recovery Point', 'description' => 'Select appropriate backup'],
                        ['order' => 2, 'title' => 'Prepare Recovery Environment', 'description' => 'Setup recovery database'],
                        ['order' => 3, 'title' => 'Restore Data', 'description' => 'Execute restore process'],
                        ['order' => 4, 'title' => 'Validate Recovery', 'description' => 'Verify data integrity'],
                        ['order' => 5, 'title' => 'Switch Production', 'description' => 'Redirect applications to recovered database'],
                    ],
                    'rto' => '4 hours',
                    'rpo' => '1 hour',
                    'uptime_requirement' => 99.9,
                ],
            ],
            'incident_response' => [
                'name' => 'Security Incident Response',
                'description' => 'Template for security incident handling',
                'category' => 'troubleshooting',
                'enabled_tabs' => ['general', 'procedures', 'compliance', 'resources', 'history'],
                'template_data' => [
                    'procedure_steps' => [
                        ['order' => 1, 'title' => 'Detection & Analysis', 'description' => 'Identify and assess incident', 'duration' => '30 minutes'],
                        ['order' => 2, 'title' => 'Containment', 'description' => 'Isolate affected systems', 'duration' => '15 minutes'],
                        ['order' => 3, 'title' => 'Eradication', 'description' => 'Remove threat from environment', 'duration' => '60-240 minutes'],
                        ['order' => 4, 'title' => 'Recovery', 'description' => 'Restore normal operations', 'duration' => '60-180 minutes'],
                        ['order' => 5, 'title' => 'Post-Incident Review', 'description' => 'Document lessons learned', 'duration' => '60 minutes'],
                    ],
                    'escalation_paths' => [
                        ['level' => 1, 'role' => 'Security Analyst', 'response_time' => '15 minutes'],
                        ['level' => 2, 'role' => 'Security Manager', 'response_time' => '30 minutes'],
                        ['level' => 3, 'role' => 'CISO', 'response_time' => '1 hour'],
                        ['level' => 4, 'role' => 'Executive Team', 'response_time' => '2 hours'],
                    ],
                    'compliance_requirements' => ['gdpr', 'hipaa', 'pci_dss'],
                ],
            ],
            'network_architecture' => [
                'name' => 'Network Architecture Documentation',
                'description' => 'Template for network design documentation',
                'category' => 'architecture',
                'enabled_tabs' => ['general', 'technical', 'network', 'compliance', 'resources'],
                'template_data' => [
                    'ip_addresses' => [
                        ['network' => '10.0.0.0/24', 'vlan' => 'Management', 'description' => 'Management network'],
                        ['network' => '10.1.0.0/24', 'vlan' => 'Production', 'description' => 'Production servers'],
                        ['network' => '10.2.0.0/24', 'vlan' => 'DMZ', 'description' => 'DMZ network'],
                        ['network' => '192.168.1.0/24', 'vlan' => 'Guest', 'description' => 'Guest WiFi'],
                    ],
                    'firewall_rules' => [
                        ['source' => 'Any', 'destination' => 'DMZ', 'port' => '443', 'protocol' => 'TCP', 'action' => 'Allow'],
                        ['source' => 'DMZ', 'destination' => 'Production', 'port' => '3306', 'protocol' => 'TCP', 'action' => 'Allow'],
                        ['source' => 'Management', 'destination' => 'Any', 'port' => 'Any', 'protocol' => 'Any', 'action' => 'Allow'],
                    ],
                    'dns_entries' => [
                        ['hostname' => 'app.domain.com', 'ip' => '10.1.0.10', 'type' => 'A'],
                        ['hostname' => 'db.domain.com', 'ip' => '10.1.0.20', 'type' => 'A'],
                        ['hostname' => 'mail.domain.com', 'ip' => '10.2.0.10', 'type' => 'MX'],
                    ],
                ],
            ],
            'gdpr_compliance' => [
                'name' => 'GDPR Compliance Documentation',
                'description' => 'Template for GDPR compliance documentation',
                'category' => 'compliance',
                'enabled_tabs' => ['general', 'compliance', 'procedures', 'resources', 'history'],
                'template_data' => [
                    'compliance_requirements' => ['gdpr'],
                    'data_classification' => 'confidential',
                    'encryption_required' => true,
                    'audit_requirements' => [
                        'Annual privacy impact assessment',
                        'Quarterly access reviews',
                        'Monthly data processing activity review',
                        'Incident response testing',
                    ],
                    'security_controls' => [
                        'Data encryption at rest and in transit',
                        'Access control with MFA',
                        'Data minimization policies',
                        'Regular security training',
                        'Incident response plan',
                        'Data breach notification procedures',
                    ],
                    'procedure_steps' => [
                        ['order' => 1, 'title' => 'Data Subject Request Receipt', 'description' => 'Log and acknowledge request', 'duration' => '1 day'],
                        ['order' => 2, 'title' => 'Identity Verification', 'description' => 'Verify requestor identity', 'duration' => '2 days'],
                        ['order' => 3, 'title' => 'Data Collection', 'description' => 'Gather all personal data', 'duration' => '5 days'],
                        ['order' => 4, 'title' => 'Review & Redaction', 'description' => 'Review and redact third-party data', 'duration' => '3 days'],
                        ['order' => 5, 'title' => 'Response Delivery', 'description' => 'Provide data to subject', 'duration' => '1 day'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Apply template to documentation
     */
    public function applyTemplate(ClientITDocumentation $documentation, string $templateKey): void
    {
        $templates = $this->getTemplates();

        if (! isset($templates[$templateKey])) {
            return;
        }

        $template = $templates[$templateKey];

        // Apply template configuration
        $documentation->it_category = $template['category'];
        $documentation->enabled_tabs = $template['enabled_tabs'];
        $documentation->template_used = $templateKey;

        // Apply template data
        foreach ($template['template_data'] as $field => $value) {
            if (in_array($field, $documentation->getFillable())) {
                $documentation->{$field} = $value;
            }
        }
    }

    /**
     * Calculate documentation completeness based on enabled tabs
     */
    public function calculateCompleteness(ClientITDocumentation $documentation): int
    {
        $enabledTabs = $documentation->enabled_tabs ?? $this->getDefaultTabsForCategory($documentation->it_category);
        $tabs = $this->getAvailableTabs();

        $totalFields = 0;
        $completedFields = 0;

        foreach ($enabledTabs as $tabId) {
            if (! isset($tabs[$tabId])) {
                continue;
            }

            $tabFields = $tabs[$tabId]['fields'];
            foreach ($tabFields as $field) {
                $totalFields++;

                $value = $documentation->{$field};
                if (! empty($value)) {
                    $completedFields++;
                }
            }
        }

        return $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;
    }

    /**
     * Get tab validation status
     */
    public function getTabValidation(ClientITDocumentation $documentation): array
    {
        $enabledTabs = $documentation->enabled_tabs ?? $this->getDefaultTabsForCategory($documentation->it_category);
        $tabs = $this->getAvailableTabs();
        $validation = [];

        foreach ($enabledTabs as $tabId) {
            if (! isset($tabs[$tabId])) {
                continue;
            }

            $tabFields = $tabs[$tabId]['fields'];
            $hasData = false;

            foreach ($tabFields as $field) {
                if (! empty($documentation->{$field})) {
                    $hasData = true;
                    break;
                }
            }

            $validation[$tabId] = $hasData;
        }

        return $validation;
    }
}
