<?php

namespace Database\Factories\Domains\Project\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Project\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'prefix' => $this->faker->randomElement(['PRJ', 'PROJ', null]),
            'number' => $this->faker->unique()->numberBetween(1, 9999),
            'name' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'due' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'manager_id' => User::factory(),
            'client_id' => Client::factory(),
            'completed_at' => null,
            'archived_at' => null,
            'ai_summary' => null,
            'ai_risk_level' => null,
            'ai_risk_confidence' => null,
            'ai_progress_assessment' => null,
            'ai_recommendations' => null,
            'ai_analyzed_at' => null,
        ];
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the project is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the project is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due' => $this->faker->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }
}
