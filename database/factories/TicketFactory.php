<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-6 months', 'now');
        
        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'prefix' => 'TKT',
            'number' => $this->faker->unique()->numberBetween(1000, 99999),
            'subject' => $this->faker->sentence(6),
            'details' => $this->faker->paragraphs(3, true),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'pending', 'resolved', 'closed']),
            'category' => $this->faker->randomElement(['Hardware', 'Software', 'Network', 'Security', 'Account', 'Other']),
            'source' => $this->faker->randomElement(['email', 'phone', 'portal', 'walk-in', 'monitoring']),
            'billable' => $this->faker->boolean(70),
            'onsite' => $this->faker->boolean(30),
            'scheduled_at' => null,
            'closed_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Indicate that the ticket is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Indicate that the ticket is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'assigned_to' => User::factory(),
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Indicate that the ticket is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'assigned_to' => User::factory(),
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Indicate that the ticket is resolved.
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $resolvedAt = $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            
            return [
                'status' => 'resolved',
                'assigned_to' => User::factory(),
                'resolved_at' => $resolvedAt,
                'closed_at' => null,
                'actual_hours' => $this->faker->randomFloat(1, 0.5, 20),
            ];
        });
    }

    /**
     * Indicate that the ticket is closed.
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $resolvedAt = $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            $closedAt = $this->faker->dateTimeBetween($resolvedAt, 'now');
            
            return [
                'status' => 'closed',
                'assigned_to' => User::factory(),
                'resolved_at' => $resolvedAt,
                'closed_at' => $closedAt,
                'actual_hours' => $this->faker->randomFloat(1, 0.5, 20),
            ];
        });
    }

    /**
     * Set specific priority.
     */
    public function priority(string $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    /**
     * Set as urgent priority.
     */
    public function urgent(): static
    {
        return $this->priority('urgent');
    }

    /**
     * Set as high priority.
     */
    public function high(): static
    {
        return $this->priority('high');
    }

    /**
     * Set as medium priority.
     */
    public function medium(): static
    {
        return $this->priority('medium');
    }

    /**
     * Set as low priority.
     */
    public function low(): static
    {
        return $this->priority('low');
    }

    /**
     * Set specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Assign to a specific user.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
            'company_id' => $user->company_id,
        ]);
    }

    /**
     * Create ticket for a specific client.
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'company_id' => $client->company_id,
        ]);
    }

    /**
     * Create ticket for a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Set with due date.
     */
    public function withDueDate(\DateTime $dueDate): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $dueDate,
        ]);
    }

    /**
     * Set as overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $dueDate = $this->faker->dateTimeBetween('-30 days', '-1 day');
            
            return [
                'due_date' => $dueDate,
                'status' => $this->faker->randomElement(['open', 'in_progress', 'pending']),
                'resolved_at' => null,
                'closed_at' => null,
            ];
        });
    }

    /**
     * Set with estimated hours.
     */
    public function withEstimatedHours(float $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'estimated_hours' => $hours,
        ]);
    }

    /**
     * Set with tags.
     */
    public function withTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => $tags,
        ]);
    }

    /**
     * Create recent ticket (last 7 days).
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = $this->faker->dateTimeBetween('-7 days', 'now');
            
            return [
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        });
    }

    /**
     * Set as scheduled.
     */
    public function scheduled(\DateTime $scheduledAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $scheduledAt ?? $this->faker->dateTimeBetween('now', '+7 days'),
        ]);
    }
}