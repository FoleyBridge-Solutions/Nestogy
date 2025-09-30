<?php

namespace Database\Seeders;

use App\Domains\Ticket\Models\TimeEntryTemplate;
use Illuminate\Database\Seeder;

class TimeEntryTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default templates for company_id = 1 (assuming first company exists)
        $companyId = 1;

        $templates = [
            [
                'company_id' => $companyId,
                'name' => 'Password Reset',
                'description' => 'Reset user password and security questions',
                'work_type' => 'account_management',
                'default_hours' => 0.25,
                'category' => 'Account Support',
                'keywords' => ['password', 'reset', 'login', 'account', 'locked'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Email Setup',
                'description' => 'Configure email account and settings',
                'work_type' => 'email_support',
                'default_hours' => 0.5,
                'category' => 'Email Support',
                'keywords' => ['email', 'outlook', 'mail', 'setup', 'configure'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Network Troubleshooting',
                'description' => 'Diagnose and resolve network connectivity issues',
                'work_type' => 'network_support',
                'default_hours' => 1.0,
                'category' => 'Network Support',
                'keywords' => ['network', 'internet', 'connection', 'wifi', 'ethernet'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Software Installation',
                'description' => 'Install and configure software applications',
                'work_type' => 'software_support',
                'default_hours' => 0.75,
                'category' => 'Software Support',
                'keywords' => ['install', 'software', 'application', 'program', 'setup'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Backup/Recovery',
                'description' => 'Backup data or recover from backup',
                'work_type' => 'backup_recovery',
                'default_hours' => 1.5,
                'category' => 'Data Management',
                'keywords' => ['backup', 'recovery', 'restore', 'data', 'file'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Security Incident',
                'description' => 'Respond to security threats and malware',
                'work_type' => 'security_support',
                'default_hours' => 2.0,
                'category' => 'Security',
                'keywords' => ['virus', 'malware', 'security', 'threat', 'infected'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'System Maintenance',
                'description' => 'Routine system maintenance and updates',
                'work_type' => 'maintenance',
                'default_hours' => 2.0,
                'category' => 'Maintenance',
                'keywords' => ['maintenance', 'update', 'patch', 'system', 'routine'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Printer Setup',
                'description' => 'Install and configure printers',
                'work_type' => 'hardware_support',
                'default_hours' => 0.5,
                'category' => 'Hardware Support',
                'keywords' => ['printer', 'print', 'setup', 'install', 'configure'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Remote Support Session',
                'description' => 'General remote support and troubleshooting',
                'work_type' => 'troubleshooting',
                'default_hours' => 0.5,
                'category' => 'General Support',
                'keywords' => ['remote', 'support', 'help', 'assist', 'troubleshoot'],
                'is_billable' => true,
                'is_active' => true,
            ],
            [
                'company_id' => $companyId,
                'name' => 'Consultation Call',
                'description' => 'Phone or video consultation with client',
                'work_type' => 'consultation',
                'default_hours' => 0.5,
                'category' => 'Consultation',
                'keywords' => ['consultation', 'call', 'meeting', 'discuss', 'advice'],
                'is_billable' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            TimeEntryTemplate::create($template);
        }

        $this->command->info('Time entry templates seeded successfully!');
    }
}
