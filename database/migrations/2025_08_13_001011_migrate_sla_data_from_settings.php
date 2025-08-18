<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\Setting;
use App\Domains\Ticket\Models\SLA;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing SLA data from settings to new SLA table
        $companies = Company::all();
        
        foreach ($companies as $company) {
            $settings = $company->settings;
            
            if (!$settings) {
                // Create default SLA for companies without settings
                $this->createDefaultSLA($company);
                continue;
            }
            
            $slaData = $settings->sla_definitions;
            
            if ($slaData && is_array($slaData)) {
                $this->migrateSLAFromSettings($company, $settings, $slaData);
            } else {
                // Create default SLA for companies without SLA settings
                $this->createDefaultSLA($company);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all migrated SLAs
        SLA::truncate();
    }
    
    /**
     * Create default SLA for a company
     */
    private function createDefaultSLA(Company $company): void
    {
        SLA::create([
            'company_id' => $company->id,
            'name' => $company->name . ' - Default SLA',
            'description' => 'Default Service Level Agreement for ' . $company->name,
            'is_default' => true,
            'is_active' => true,
            'critical_response_minutes' => 60,
            'high_response_minutes' => 240,
            'medium_response_minutes' => 480,
            'low_response_minutes' => 1440,
            'critical_resolution_minutes' => 240,
            'high_resolution_minutes' => 1440,
            'medium_resolution_minutes' => 4320,
            'low_resolution_minutes' => 10080,
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => 'UTC',
            'coverage_type' => 'business_hours',
            'holiday_coverage' => false,
            'exclude_weekends' => true,
            'escalation_enabled' => true,
            'breach_warning_percentage' => 80,
            'uptime_percentage' => 99.50,
            'first_call_resolution_target' => 75.00,
            'customer_satisfaction_target' => 90.00,
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'effective_from' => now()->toDateString(),
        ]);
    }
    
    /**
     * Migrate SLA data from settings
     */
    private function migrateSLAFromSettings(Company $company, Setting $settings, array $slaData): void
    {
        // Extract response and resolution times
        $responseTimes = $slaData['response_times'] ?? [];
        $resolutionTimes = $slaData['resolution_times'] ?? [];
        
        SLA::create([
            'company_id' => $company->id,
            'name' => $company->name . ' - Migrated SLA',
            'description' => 'SLA migrated from company settings',
            'is_default' => true,
            'is_active' => $slaData['enabled'] ?? true,
            'critical_response_minutes' => $responseTimes['critical'] ?? 60,
            'high_response_minutes' => $responseTimes['high'] ?? 240,
            'medium_response_minutes' => $responseTimes['medium'] ?? 480,
            'low_response_minutes' => $responseTimes['low'] ?? 1440,
            'critical_resolution_minutes' => $resolutionTimes['critical'] ?? 240,
            'high_resolution_minutes' => $resolutionTimes['high'] ?? 1440,
            'medium_resolution_minutes' => $resolutionTimes['medium'] ?? 4320,
            'low_resolution_minutes' => $resolutionTimes['low'] ?? 10080,
            'business_hours_start' => $this->getBusinessHoursStart($settings),
            'business_hours_end' => $this->getBusinessHoursEnd($settings),
            'business_days' => $this->getBusinessDays($slaData),
            'timezone' => $settings->timezone ?? 'UTC',
            'coverage_type' => $slaData['business_hours_only'] ?? true ? 'business_hours' : '24/7',
            'holiday_coverage' => $slaData['exclude_holidays'] ?? false ? false : true,
            'exclude_weekends' => $slaData['exclude_weekends'] ?? true,
            'escalation_enabled' => $slaData['enabled'] ?? true,
            'breach_warning_percentage' => 80,
            'uptime_percentage' => 99.50,
            'first_call_resolution_target' => 75.00,
            'customer_satisfaction_target' => 90.00,
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'effective_from' => now()->toDateString(),
        ]);
    }
    
    /**
     * Get business hours start from settings
     */
    private function getBusinessHoursStart(Setting $settings): string
    {
        // Check for existing business hours in settings
        if ($settings->business_hours && is_array($settings->business_hours)) {
            return $settings->business_hours['start'] ?? '09:00';
        }
        
        return '09:00';
    }
    
    /**
     * Get business hours end from settings
     */
    private function getBusinessHoursEnd(Setting $settings): string
    {
        // Check for existing business hours in settings
        if ($settings->business_hours && is_array($settings->business_hours)) {
            return $settings->business_hours['end'] ?? '17:00';
        }
        
        return '17:00';
    }
    
    /**
     * Get business days from SLA data or default
     */
    private function getBusinessDays(array $slaData): array
    {
        if (isset($slaData['business_days']) && is_array($slaData['business_days'])) {
            return $slaData['business_days'];
        }
        
        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    }
};
