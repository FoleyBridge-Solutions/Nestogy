<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientService;
use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Models\User;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Client\Models\ClientService>
 */
class ClientServiceFactory extends Factory
{
    protected $model = ClientService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceType = fake()->randomElement(array_keys(ClientService::getServiceTypes()));
        $category = fake()->randomElement(array_keys(ClientService::getServiceCategories()));
        $billingCycle = fake()->randomElement(array_keys(ClientService::getBillingCycles()));
        $serviceLevel = fake()->randomElement(array_keys(ClientService::getServiceLevels()));
        $priorityLevel = fake()->randomElement(array_keys(ClientService::getPriorityLevels()));

        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->dateTimeBetween('+6 months', '+2 years');
        $renewalDate = fake()->dateTimeBetween('+1 month', '+6 months');

        $monthlyCost = fake()->randomFloat(2, 500, 5000);
        $setupCost = fake()->randomFloat(2, 0, 1000);

        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'contract_id' => null,
            'product_id' => null,
            'name' => fake()->catchPhrase() . ' Service',
            'description' => fake()->optional(0.7)->paragraph(),
            'service_type' => $serviceType,
            'category' => $category,
            'status' => 'active',
            'provisioning_status' => 'completed',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'renewal_date' => $renewalDate,
            'billing_cycle' => $billingCycle,
            'monthly_cost' => $monthlyCost,
            'setup_cost' => $setupCost,
            'total_contract_value' => $monthlyCost * 12,
            'currency' => 'USD',
            'auto_renewal' => fake()->boolean(60),
            'contract_terms' => fake()->optional(0.6)->paragraph(),
            'sla_terms' => fake()->optional(0.8)->paragraph(),
            'service_level' => $serviceLevel,
            'priority_level' => $priorityLevel,
            'assigned_technician' => null,
            'backup_technician' => null,
            'escalation_contact' => fake()->optional(0.3)->name(),
            'service_hours' => [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
            ],
            'response_time' => fake()->randomElement([15, 30, 60, 120, 240]),
            'resolution_time' => fake()->randomElement([4, 8, 24, 48, 72]),
            'availability_target' => fake()->randomElement([99.0, 99.5, 99.9, 99.95, 99.99]),
            'performance_metrics' => [
                'uptime' => fake()->randomFloat(2, 95, 100),
                'avg_response_time' => fake()->numberBetween(10, 120),
            ],
            'monitoring_enabled' => fake()->boolean(70),
            'backup_schedule' => fake()->optional(0.5)->randomElement(['daily', 'weekly', 'monthly']),
            'maintenance_schedule' => fake()->optional(0.4)->randomElement(['monthly', 'quarterly', 'annual']),
            'last_review_date' => fake()->optional(0.5)->dateTimeBetween('-6 months', 'now'),
            'next_review_date' => fake()->optional(0.6)->dateTimeBetween('now', '+6 months'),
            'renewal_count' => fake()->numberBetween(0, 5),
            'client_satisfaction' => fake()->optional(0.6)->numberBetween(1, 10),
            'health_score' => fake()->numberBetween(60, 100),
            'sla_breaches_count' => fake()->numberBetween(0, 3),
            'notes' => fake()->optional(0.4)->paragraph(),
            'tags' => fake()->optional(0.5)->randomElements(['vip', 'priority', 'legacy', 'new', 'critical'], fake()->numberBetween(1, 3)),
            'cancellation_reason' => null,
            'cancellation_fee' => null,
            'recurring_billing_id' => null,
            'actual_monthly_revenue' => null,
            'provisioned_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'activated_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'suspended_at' => null,
            'cancelled_at' => null,
            'last_renewed_at' => fake()->optional(0.4)->dateTimeBetween('-6 months', 'now'),
            'last_health_check_at' => fake()->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'last_sla_breach_at' => fake()->optional(0.2)->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Indicate that the service is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'provisioning_status' => 'completed',
            'activated_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'provisioned_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'suspended_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the service is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'provisioning_status' => 'pending',
            'activated_at' => null,
            'provisioned_at' => null,
            'suspended_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the service is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'provisioning_status' => 'completed',
            'activated_at' => now()->subDays(fake()->numberBetween(30, 365)),
            'provisioned_at' => now()->subDays(fake()->numberBetween(30, 365)),
            'suspended_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'cancelled_at' => null,
            'notes' => (string) fake()->paragraph() . "\n\nSuspended: " . fake()->randomElement(['Non-payment', 'Contract breach', 'Client request']),
        ]);
    }

    /**
     * Indicate that the service is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'provisioning_status' => 'completed',
            'activated_at' => now()->subDays(fake()->numberBetween(60, 365)),
            'provisioned_at' => now()->subDays(fake()->numberBetween(60, 365)),
            'suspended_at' => null,
            'cancelled_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'cancellation_reason' => fake()->randomElement(['Client request', 'Contract ended', 'Migration to new service', 'Budget constraints']),
            'cancellation_fee' => fake()->optional(0.3)->randomFloat(2, 0, 1000),
            'end_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the service is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'provisioning_status' => 'completed',
            'activated_at' => now()->subYear(),
            'provisioned_at' => now()->subYear(),
            'suspended_at' => null,
            'cancelled_at' => null,
            'end_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the service is for managed services.
     */
    public function managedServices(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'managed_services',
            'category' => 'infrastructure',
            'name' => 'Managed IT Services',
            'monthly_cost' => fake()->randomFloat(2, 2000, 10000),
        ]);
    }

    /**
     * Indicate that the service is for cloud services.
     */
    public function cloudServices(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'cloud_services',
            'category' => 'cloud',
            'name' => 'Cloud Infrastructure Services',
            'monthly_cost' => fake()->randomFloat(2, 1000, 5000),
        ]);
    }

    /**
     * Indicate that the service is for backup services.
     */
    public function backupServices(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'backup_services',
            'category' => 'backup',
            'name' => 'Backup & Disaster Recovery',
            'monthly_cost' => fake()->randomFloat(2, 500, 2000),
            'backup_schedule' => 'daily',
        ]);
    }

    /**
     * Indicate that the service is due for renewal.
     */
    public function dueForRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'renewal_date' => now()->addDays(fake()->numberBetween(1, 29)),
            'auto_renewal' => false,
        ]);
    }

    /**
     * Indicate that the service is ending soon.
     */
    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'end_date' => now()->addDays(fake()->numberBetween(1, 29)),
        ]);
    }

    /**
     * Indicate that the service needs review.
     */
    public function needsReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'last_review_date' => now()->subDays(fake()->numberBetween(91, 365)),
            'next_review_date' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }

    /**
     * Indicate that the service has monitoring enabled.
     */
    public function monitored(): static
    {
        return $this->state(fn (array $attributes) => [
            'monitoring_enabled' => true,
            'last_health_check_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'health_score' => fake()->numberBetween(80, 100),
        ]);
    }

    /**
     * Indicate that the service has poor health.
     */
    public function poorHealth(): static
    {
        return $this->state(fn (array $attributes) => [
            'health_score' => fake()->numberBetween(20, 50),
            'sla_breaches_count' => fake()->numberBetween(3, 10),
            'client_satisfaction' => fake()->numberBetween(1, 5),
            'last_sla_breach_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    /**
     * Create a service for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $client->company_id,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Create a service with a contract.
     */
    public function withContract(Contract $contract): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $contract->company_id,
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
        ]);
    }

    /**
     * Create a service with a product.
     */
    public function withProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'monthly_cost' => $product->base_price,
        ]);
    }

    /**
     * Create a service with assigned technicians.
     */
    public function withTechnicians(User $primary, ?User $backup = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_technician' => $primary->id,
            'backup_technician' => $backup?->id,
        ]);
    }

    /**
     * Create a service with recurring billing.
     */
    public function withRecurringBilling(int $recurringBillingId): static
    {
        return $this->state(fn (array $attributes) => [
            'recurring_billing_id' => $recurringBillingId,
            'actual_monthly_revenue' => $attributes['monthly_cost'] ?? fake()->randomFloat(2, 500, 5000),
        ]);
    }

    /**
     * Create a service with auto-renewal enabled.
     */
    public function autoRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_renewal' => true,
            'renewal_date' => now()->addMonths(fake()->numberBetween(1, 12)),
        ]);
    }
}
