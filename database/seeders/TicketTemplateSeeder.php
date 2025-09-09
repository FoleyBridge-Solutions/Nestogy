<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System ticket templates only - no demo data
        $templates = [
            [
                'company_id' => 1,
                'name' => 'Default Ticket',
                'description' => 'Standard ticket template for general support requests',
                'category' => 'general',
                'priority' => 'medium',
                'default_fields' => json_encode([
                    'problem_description' => '',
                    'steps_to_reproduce' => '',
                    'expected_outcome' => '',
                    'actual_outcome' => '',
                    'priority_justification' => ''
                ]),
                'instructions' => 'Please provide detailed information about your issue including steps to reproduce and any error messages.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'System Maintenance',
                'description' => 'Template for scheduled system maintenance tickets',
                'category' => 'maintenance',
                'priority' => 'medium',
                'default_fields' => json_encode([
                    'maintenance_type' => '',
                    'affected_systems' => '',
                    'maintenance_window' => '',
                    'rollback_plan' => '',
                    'contact_person' => ''
                ]),
                'instructions' => 'Use this template for scheduling and tracking system maintenance activities.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Emergency Response',
                'description' => 'Template for critical system outages and emergencies',
                'category' => 'emergency',
                'priority' => 'urgent',
                'default_fields' => json_encode([
                    'incident_description' => '',
                    'systems_affected' => '',
                    'business_impact' => '',
                    'initial_response' => '',
                    'escalation_required' => ''
                ]),
                'instructions' => 'Use for critical incidents requiring immediate attention. Include all relevant details about the outage and impact.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Service Request',
                'description' => 'Template for new service or access requests',
                'category' => 'request',
                'priority' => 'low',
                'default_fields' => json_encode([
                    'request_type' => '',
                    'justification' => '',
                    'approval_required' => '',
                    'timeline' => '',
                    'additional_notes' => ''
                ]),
                'instructions' => 'Use for requesting new services, access, or resources.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('ticket_templates')->insertOrIgnore($template);
        }
    }
}