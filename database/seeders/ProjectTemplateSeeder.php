<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System project templates only - no demo data
        $templates = [
            [
                'company_id' => 1,
                'name' => 'Default Project',
                'description' => 'Standard project template for general projects',
                'category' => 'general',
                'default_tasks' => json_encode([
                    ['name' => 'Project Planning', 'estimated_hours' => 4],
                    ['name' => 'Requirements Gathering', 'estimated_hours' => 8],
                    ['name' => 'Implementation', 'estimated_hours' => 40],
                    ['name' => 'Testing', 'estimated_hours' => 8],
                    ['name' => 'Documentation', 'estimated_hours' => 4],
                    ['name' => 'Deployment', 'estimated_hours' => 4],
                    ['name' => 'Post-Deployment Review', 'estimated_hours' => 2],
                ]),
                'default_milestones' => json_encode([
                    ['name' => 'Project Kickoff', 'days_from_start' => 0],
                    ['name' => 'Requirements Complete', 'days_from_start' => 7],
                    ['name' => 'Implementation Complete', 'days_from_start' => 30],
                    ['name' => 'Go Live', 'days_from_start' => 45],
                    ['name' => 'Project Closure', 'days_from_start' => 60],
                ]),
                'estimated_duration_days' => 60,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Infrastructure Deployment',
                'description' => 'Template for infrastructure and server deployment projects',
                'category' => 'infrastructure',
                'default_tasks' => json_encode([
                    ['name' => 'Infrastructure Assessment', 'estimated_hours' => 8],
                    ['name' => 'Architecture Design', 'estimated_hours' => 16],
                    ['name' => 'Hardware/Software Procurement', 'estimated_hours' => 4],
                    ['name' => 'Environment Setup', 'estimated_hours' => 16],
                    ['name' => 'Configuration', 'estimated_hours' => 24],
                    ['name' => 'Security Hardening', 'estimated_hours' => 8],
                    ['name' => 'Performance Testing', 'estimated_hours' => 8],
                    ['name' => 'Migration/Deployment', 'estimated_hours' => 16],
                    ['name' => 'Documentation', 'estimated_hours' => 8],
                    ['name' => 'Training', 'estimated_hours' => 4],
                ]),
                'default_milestones' => json_encode([
                    ['name' => 'Assessment Complete', 'days_from_start' => 5],
                    ['name' => 'Design Approved', 'days_from_start' => 10],
                    ['name' => 'Environment Ready', 'days_from_start' => 20],
                    ['name' => 'Testing Complete', 'days_from_start' => 35],
                    ['name' => 'Production Deployment', 'days_from_start' => 45],
                ]),
                'estimated_duration_days' => 45,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'name' => 'Security Audit',
                'description' => 'Template for security assessment and audit projects',
                'category' => 'security',
                'default_tasks' => json_encode([
                    ['name' => 'Scope Definition', 'estimated_hours' => 4],
                    ['name' => 'Vulnerability Assessment', 'estimated_hours' => 16],
                    ['name' => 'Penetration Testing', 'estimated_hours' => 24],
                    ['name' => 'Policy Review', 'estimated_hours' => 8],
                    ['name' => 'Risk Assessment', 'estimated_hours' => 8],
                    ['name' => 'Report Generation', 'estimated_hours' => 8],
                    ['name' => 'Remediation Planning', 'estimated_hours' => 4],
                    ['name' => 'Executive Presentation', 'estimated_hours' => 2],
                ]),
                'default_milestones' => json_encode([
                    ['name' => 'Audit Kickoff', 'days_from_start' => 0],
                    ['name' => 'Assessment Complete', 'days_from_start' => 14],
                    ['name' => 'Final Report Delivered', 'days_from_start' => 21],
                    ['name' => 'Remediation Plan Approved', 'days_from_start' => 28],
                ]),
                'estimated_duration_days' => 28,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('project_templates')->insertOrIgnore($template);
        }
    }
}