<?php

namespace Database\Factories\Domains\Lead\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'lead_source_id' => null,
            'assigned_user_id' => User::factory(),
            'client_id' => null,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company_name' => $this->faker->company(),
            'title' => $this->faker->jobTitle(),
            'website' => $this->faker->optional()->url(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip_code' => $this->faker->postcode(),
            'country' => 'United States',
            'status' => 'new',
            'priority' => 'normal',
            'industry' => $this->faker->optional()->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'company_size' => $this->faker->optional()->numberBetween(1, 1000),
            'estimated_value' => $this->faker->optional()->randomFloat(2, 1000, 100000),
            'notes' => $this->faker->optional()->paragraph(),
            'custom_fields' => null,
            'total_score' => 0,
            'demographic_score' => 0,
            'behavioral_score' => 0,
            'fit_score' => 0,
            'urgency_score' => 0,
            'last_scored_at' => null,
            'first_contact_date' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'last_contact_date' => null,
            'qualified_at' => null,
            'converted_at' => null,
            'utm_source' => $this->faker->optional()->randomElement(['google', 'facebook', 'linkedin', 'website']),
            'utm_medium' => $this->faker->optional()->randomElement(['cpc', 'organic', 'email', 'social']),
            'utm_campaign' => $this->faker->optional()->word(),
            'utm_content' => null,
            'utm_term' => null,
            'ai_summary' => null,
            'ai_quality_score' => null,
            'ai_conversion_likelihood' => null,
            'ai_suggested_approach' => null,
            'ai_key_insights' => null,
            'ai_analyzed_at' => null,
        ];
    }

    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'qualified',
            'qualified_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'total_score' => $this->faker->numberBetween(70, 100),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'qualified_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'converted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
