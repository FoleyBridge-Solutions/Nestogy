<?php

namespace Database\Factories\Domains\Ticket\Models;

use App\Domains\Ticket\Models\SLA;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SLA>
 */
class SLAFactory extends Factory
{
    protected $model = SLA::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' SLA',
            'description' => $this->faker->sentence(),
            'is_default' => false,
            'is_active' => true,
            
            // Response times in minutes
            'critical_response_minutes' => 60,
            'high_response_minutes' => 240,
            'medium_response_minutes' => 480,
            'low_response_minutes' => 1440,
            
            // Resolution times in minutes
            'critical_resolution_minutes' => 240,
            'high_resolution_minutes' => 1440,
            'medium_resolution_minutes' => 4320,
            'low_resolution_minutes' => 10080,
            
            // Business hours
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'business_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'timezone' => 'America/New_York',
            'coverage_type' => 'business_hours',
            'holiday_coverage' => false,
            'exclude_weekends' => true,
            
            // Escalation
            'escalation_enabled' => true,
            'escalation_levels' => [
                ['level' => 1, 'delay_minutes' => 60, 'role' => 'manager'],
                ['level' => 2, 'delay_minutes' => 120, 'role' => 'director'],
            ],
            'breach_warning_percentage' => 80,
            
            // Performance targets
            'uptime_percentage' => 99.9,
            'first_call_resolution_target' => 75.0,
            'customer_satisfaction_target' => 90.0,
            
            // Notifications
            'notify_on_breach' => true,
            'notify_on_warning' => true,
            'notification_emails' => [],
            
            // Effective dates
            'effective_from' => now()->toDateString(),
            'effective_to' => null,
        ];
    }

    /**
     * Indicate that the SLA is the default
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'name' => 'Default SLA',
        ]);
    }

    /**
     * Indicate that the SLA provides 24/7 coverage
     */
    public function twentyFourSeven(): static
    {
        return $this->state(fn (array $attributes) => [
            'coverage_type' => '24_7',
            'exclude_weekends' => false,
            'holiday_coverage' => true,
            'business_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ]);
    }

    /**
     * Indicate that the SLA is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
