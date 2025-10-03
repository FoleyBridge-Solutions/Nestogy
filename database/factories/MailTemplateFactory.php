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
            'category' => null,
            'subject' => null,
            'html_template' => null,
            'text_template' => null,
            'available_variables' => null,
            'default_data' => null,
            'is_active' => true,
            'is_system' => true,
            'settings' => null
        ];
    }
}
