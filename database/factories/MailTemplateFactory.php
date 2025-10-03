<?php

namespace Database\Factories;

use App\Models\MailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailTemplateFactory extends Factory
{
    protected $model = MailTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'display_name' => $this->faker->words(3, true),
            'category' => $this->faker->optional()->word,
            'subject' => $this->faker->optional()->word,
            'html_template' => $this->faker->optional()->word,
            'text_template' => $this->faker->optional()->word,
            'available_variables' => $this->faker->optional()->word,
            'default_data' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'is_system' => $this->faker->boolean(70),
            'settings' => $this->faker->optional()->word
        ];
    }
}
