<?php

namespace Database\Factories;

use App\Models\DashboardWidget;
use Illuminate\Database\Eloquent\Factories\Factory;

class DashboardWidgetFactory extends Factory
{
    protected $model = DashboardWidget::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'widget_type' => $this->faker->numberBetween(1, 5),
            'widget_name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'dashboard_type' => $this->faker->numberBetween(1, 5),
            'configuration' => $this->faker->optional()->word,
            'display_settings' => $this->faker->optional()->word,
            'data_source' => $this->faker->optional()->word,
            'refresh_settings' => $this->faker->optional()->word,
            'permissions' => $this->faker->optional()->word,
            'sort_order' => $this->faker->optional()->word,
            'grid_row' => $this->faker->optional()->word,
            'grid_column' => $this->faker->optional()->word,
            'grid_width' => $this->faker->optional()->word,
            'grid_height' => $this->faker->optional()->word,
            'is_visible' => $this->faker->boolean(70),
            'is_active' => $this->faker->boolean(70),
            'is_default' => $this->faker->boolean(70),
            'last_updated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'metadata' => $this->faker->optional()->word,
            'created_by' => $this->faker->optional()->word
        ];
    }
}
