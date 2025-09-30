<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\SLA;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SLASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting SLA Seeder...');

        DB::transaction(function () {
            $companies = Company::all();

            foreach ($companies as $company) {
                $this->command->info("Creating SLAs for company: {$company->name}");

                // Create 4 SLA levels per company
                $this->createPlatinumSLA($company);
                $this->createGoldSLA($company);
                $this->createSilverSLA($company);
                $this->createBronzeSLA($company);

                $this->command->info("Completed SLAs for company: {$company->name}");
            }
        });

        $this->command->info('SLA Seeder completed!');
    }

    /**
     * Create Platinum SLA (highest tier)
     */
    private function createPlatinumSLA($company)
    {
        SLA::create([
            'company_id' => $company->id,
            'name' => 'Platinum SLA',
            'description' => 'Premium 24/7 support with fastest response and resolution times. Includes proactive monitoring and dedicated account management.',
            'is_default' => false,
            'is_active' => true,

            // Response times in minutes
            'critical_response_minutes' => 15,      // 15 minutes
            'high_response_minutes' => 30,          // 30 minutes
            'medium_response_minutes' => 60,        // 1 hour
            'low_response_minutes' => 120,          // 2 hours

            // Resolution times in minutes
            'critical_resolution_minutes' => 60,     // 1 hour
            'high_resolution_minutes' => 240,        // 4 hours
            'medium_resolution_minutes' => 480,      // 8 hours
            'low_resolution_minutes' => 1440,        // 24 hours

            // Business hours and coverage
            'business_hours_start' => '00:00:00',
            'business_hours_end' => '23:59:59',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'timezone' => $company->timezone ?? 'America/New_York',
            'coverage_type' => '24/7',
            'holiday_coverage' => true,
            'exclude_weekends' => false,

            // Escalation settings
            'escalation_enabled' => true,
            'escalation_levels' => [
                [
                    'level' => 1,
                    'after_minutes' => 30,
                    'notify' => ['tech_lead', 'service_manager'],
                ],
                [
                    'level' => 2,
                    'after_minutes' => 60,
                    'notify' => ['operations_manager', 'cto'],
                ],
                [
                    'level' => 3,
                    'after_minutes' => 120,
                    'notify' => ['ceo', 'account_executive'],
                ],
            ],

            // Performance targets
            'breach_warning_percentage' => 75,
            'uptime_percentage' => 99.99,
            'first_call_resolution_target' => 85.00,
            'customer_satisfaction_target' => 95.00,

            // Notifications
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'notification_emails' => ['sla-alerts@'.strtolower(str_replace(' ', '', $company->name)).'.com'],

            // Effective dates
            'effective_from' => Carbon::now()->subYear(),
            'effective_to' => null,
        ]);
    }

    /**
     * Create Gold SLA (high tier)
     */
    private function createGoldSLA($company)
    {
        SLA::create([
            'company_id' => $company->id,
            'name' => 'Gold SLA',
            'description' => 'Extended business hours support with priority response times. Includes quarterly business reviews.',
            'is_default' => false,
            'is_active' => true,

            // Response times in minutes
            'critical_response_minutes' => 30,      // 30 minutes
            'high_response_minutes' => 60,          // 1 hour
            'medium_response_minutes' => 120,       // 2 hours
            'low_response_minutes' => 240,          // 4 hours

            // Resolution times in minutes
            'critical_resolution_minutes' => 120,    // 2 hours
            'high_resolution_minutes' => 480,        // 8 hours
            'medium_resolution_minutes' => 960,      // 16 hours
            'low_resolution_minutes' => 2880,        // 48 hours

            // Business hours and coverage
            'business_hours_start' => '07:00:00',
            'business_hours_end' => '22:00:00',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'timezone' => $company->timezone ?? 'America/New_York',
            'coverage_type' => 'business_hours',
            'holiday_coverage' => false,
            'exclude_weekends' => false,

            // Escalation settings
            'escalation_enabled' => true,
            'escalation_levels' => [
                [
                    'level' => 1,
                    'after_minutes' => 60,
                    'notify' => ['tech_lead'],
                ],
                [
                    'level' => 2,
                    'after_minutes' => 120,
                    'notify' => ['service_manager'],
                ],
                [
                    'level' => 3,
                    'after_minutes' => 240,
                    'notify' => ['operations_manager'],
                ],
            ],

            // Performance targets
            'breach_warning_percentage' => 80,
            'uptime_percentage' => 99.90,
            'first_call_resolution_target' => 75.00,
            'customer_satisfaction_target' => 90.00,

            // Notifications
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'notification_emails' => ['sla-alerts@'.strtolower(str_replace(' ', '', $company->name)).'.com'],

            // Effective dates
            'effective_from' => Carbon::now()->subYear(),
            'effective_to' => null,
        ]);
    }

    /**
     * Create Silver SLA (standard tier)
     */
    private function createSilverSLA($company)
    {
        SLA::create([
            'company_id' => $company->id,
            'name' => 'Silver SLA',
            'description' => 'Standard business hours support with reasonable response times. Best for small to medium businesses.',
            'is_default' => true, // Make this the default for most clients
            'is_active' => true,

            // Response times in minutes
            'critical_response_minutes' => 60,      // 1 hour
            'high_response_minutes' => 120,         // 2 hours
            'medium_response_minutes' => 240,       // 4 hours
            'low_response_minutes' => 480,          // 8 hours

            // Resolution times in minutes
            'critical_resolution_minutes' => 240,    // 4 hours
            'high_resolution_minutes' => 960,        // 16 hours
            'medium_resolution_minutes' => 1440,     // 24 hours
            'low_resolution_minutes' => 5760,        // 96 hours (4 days)

            // Business hours and coverage
            'business_hours_start' => '08:00:00',
            'business_hours_end' => '18:00:00',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => $company->timezone ?? 'America/New_York',
            'coverage_type' => 'business_hours',
            'holiday_coverage' => false,
            'exclude_weekends' => true,

            // Escalation settings
            'escalation_enabled' => true,
            'escalation_levels' => [
                [
                    'level' => 1,
                    'after_minutes' => 120,
                    'notify' => ['tech_lead'],
                ],
                [
                    'level' => 2,
                    'after_minutes' => 240,
                    'notify' => ['service_manager'],
                ],
            ],

            // Performance targets
            'breach_warning_percentage' => 85,
            'uptime_percentage' => 99.50,
            'first_call_resolution_target' => 65.00,
            'customer_satisfaction_target' => 85.00,

            // Notifications
            'notify_on_breach' => true,
            'notify_on_warning' => false,
            'notification_emails' => ['support@'.strtolower(str_replace(' ', '', $company->name)).'.com'],

            // Effective dates
            'effective_from' => Carbon::now()->subYear(),
            'effective_to' => null,
        ]);
    }

    /**
     * Create Bronze SLA (basic tier)
     */
    private function createBronzeSLA($company)
    {
        SLA::create([
            'company_id' => $company->id,
            'name' => 'Bronze SLA',
            'description' => 'Basic support during business hours. Best effort service for non-critical systems.',
            'is_default' => false,
            'is_active' => true,

            // Response times in minutes
            'critical_response_minutes' => 120,     // 2 hours
            'high_response_minutes' => 240,         // 4 hours
            'medium_response_minutes' => 480,       // 8 hours
            'low_response_minutes' => 960,          // 16 hours

            // Resolution times in minutes
            'critical_resolution_minutes' => 480,    // 8 hours
            'high_resolution_minutes' => 1920,       // 32 hours
            'medium_resolution_minutes' => 2880,     // 48 hours
            'low_resolution_minutes' => 11520,       // 192 hours (8 days)

            // Business hours and coverage
            'business_hours_start' => '09:00:00',
            'business_hours_end' => '17:00:00',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => $company->timezone ?? 'America/New_York',
            'coverage_type' => 'business_hours',
            'holiday_coverage' => false,
            'exclude_weekends' => true,

            // Escalation settings
            'escalation_enabled' => false,
            'escalation_levels' => null,

            // Performance targets
            'breach_warning_percentage' => 90,
            'uptime_percentage' => 99.00,
            'first_call_resolution_target' => 50.00,
            'customer_satisfaction_target' => 80.00,

            // Notifications
            'notify_on_breach' => true,
            'notify_on_warning' => false,
            'notification_emails' => ['support@'.strtolower(str_replace(' ', '', $company->name)).'.com'],

            // Effective dates
            'effective_from' => Carbon::now()->subYear(),
            'effective_to' => null,
        ]);
    }
}
