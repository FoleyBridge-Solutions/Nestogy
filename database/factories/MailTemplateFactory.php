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
            'company_id' => \App\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'display_name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['invoice', 'notification', 'reminder', 'welcome', 'other']),
            'subject' => $this->faker->sentence,
            'html_template' => $this->faker->randomHtml(),
            'text_template' => $this->faker->optional()->text,
            'available_variables' => $this->faker->optional()->passthrough(json_encode(['name', 'email', 'amount'])),
            'default_data' => $this->faker->optional()->passthrough(json_encode([])),
            'is_active' => $this->faker->boolean(80),
            'is_system' => $this->faker->boolean(30),
            'settings' => json_encode([]),
        ];
    }
}
