<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System IT documentation templates only - no demo data
        $templates = [
            [
                'company_id' => 1,
                'name' => 'Network Documentation',
                'description' => 'Standard template for network infrastructure documentation',
                'it_category' => 'network',
                'template_category' => 'infrastructure',
                'is_template' => true,
                'is_active' => true,
                'status' => 'active',
                'enabled_tabs' => json_encode(['overview', 'network', 'security', 'procedures']),
                'tab_configuration' => json_encode([
                    'overview' => ['enabled' => true, 'order' => 1],
                    'network' => ['enabled' => true, 'order' => 2],
                    'security' => ['enabled' => true, 'order' => 3],
                    'procedures' => ['enabled' => true, 'order' => 4],
                ]),
                'system_references' => json_encode([]),
                'ip_addresses' => json_encode([]),
                'software_versions' => json_encode([]),
                'ports' => json_encode([]),
                'dns_entries' => json_encode([]),
                'firewall_rules' => json_encode([]),
                'review_schedule' => 'quarterly',
                'documentation_completeness' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Server Documentation',
                'description' => 'Standard template for server documentation',
                'it_category' => 'server',
                'template_category' => 'infrastructure',
                'is_template' => true,
                'is_active' => true,
                'status' => 'active',
                'enabled_tabs' => json_encode(['overview', 'hardware', 'software', 'maintenance', 'security']),
                'tab_configuration' => json_encode([
                    'overview' => ['enabled' => true, 'order' => 1],
                    'hardware' => ['enabled' => true, 'order' => 2],
                    'software' => ['enabled' => true, 'order' => 3],
                    'maintenance' => ['enabled' => true, 'order' => 4],
                    'security' => ['enabled' => true, 'order' => 5],
                ]),
                'system_references' => json_encode([]),
                'hardware_references' => json_encode([]),
                'software_versions' => json_encode([]),
                'environment_variables' => json_encode([]),
                'scheduled_tasks' => json_encode([]),
                'review_schedule' => 'monthly',
                'documentation_completeness' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Disaster Recovery Plan',
                'description' => 'Template for disaster recovery documentation',
                'it_category' => 'disaster-recovery',
                'template_category' => 'procedures',
                'is_template' => true,
                'is_active' => true,
                'status' => 'active',
                'enabled_tabs' => json_encode(['overview', 'procedures', 'contacts', 'testing']),
                'tab_configuration' => json_encode([
                    'overview' => ['enabled' => true, 'order' => 1],
                    'procedures' => ['enabled' => true, 'order' => 2],
                    'contacts' => ['enabled' => true, 'order' => 3],
                    'testing' => ['enabled' => true, 'order' => 4],
                ]),
                'procedure_steps' => json_encode([]),
                'rollback_procedures' => json_encode([]),
                'escalation_paths' => json_encode([]),
                'vendor_contacts' => json_encode([]),
                'test_cases' => json_encode([]),
                'rto' => 240, // 4 hours in minutes
                'rpo' => 60,  // 1 hour in minutes
                'review_schedule' => 'quarterly',
                'requires_management_approval' => true,
                'documentation_completeness' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Standard Operating Procedure',
                'description' => 'Template for standard operating procedures',
                'it_category' => 'procedure',
                'template_category' => 'procedures',
                'is_template' => true,
                'is_active' => true,
                'status' => 'active',
                'enabled_tabs' => json_encode(['overview', 'procedures', 'validation']),
                'tab_configuration' => json_encode([
                    'overview' => ['enabled' => true, 'order' => 1],
                    'procedures' => ['enabled' => true, 'order' => 2],
                    'validation' => ['enabled' => true, 'order' => 3],
                ]),
                'procedure_steps' => json_encode([]),
                'prerequisites' => json_encode([]),
                'validation_checklist' => json_encode([]),
                'review_schedule' => 'annual',
                'requires_technical_review' => true,
                'documentation_completeness' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Security Policy',
                'description' => 'Template for security policy documentation',
                'it_category' => 'security',
                'template_category' => 'compliance',
                'is_template' => true,
                'is_active' => true,
                'status' => 'active',
                'enabled_tabs' => json_encode(['overview', 'policies', 'controls', 'compliance']),
                'tab_configuration' => json_encode([
                    'overview' => ['enabled' => true, 'order' => 1],
                    'policies' => ['enabled' => true, 'order' => 2],
                    'controls' => ['enabled' => true, 'order' => 3],
                    'compliance' => ['enabled' => true, 'order' => 4],
                ]),
                'compliance_requirements' => json_encode([]),
                'security_controls' => json_encode([]),
                'audit_requirements' => json_encode([]),
                'data_classification' => 'confidential',
                'encryption_required' => true,
                'review_schedule' => 'annual',
                'requires_management_approval' => true,
                'documentation_completeness' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert templates into client_it_documentation table
        foreach ($templates as $template) {
            // Set authored_by to the system admin user (ID 1)
            $template['authored_by'] = 1;
            $template['client_id'] = null; // Templates don't belong to specific clients
            $template['version'] = 1;

            DB::table('client_it_documentation')->insertOrIgnore($template);
        }
    }
}
