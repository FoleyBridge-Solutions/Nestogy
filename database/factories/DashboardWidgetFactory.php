<?php

namespace Database\Factories;

use App\Models\DashboardWidget;
use Illuminate\Database\Eloquent\Factories\Factory;

class DashboardWidgetFactory extends Factory
{
    protected $model = DashboardWidget::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'widget_id' => 'widget-' . $this->faker->unique()->uuid,
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['statistics', 'charts', 'tables', 'alerts']),
            'type' => $this->faker->randomElement(['line_chart', 'bar_chart', 'pie_chart', 'table', 'stat']),
            'description' => $this->faker->optional()->sentence,
            'default_config' => json_encode(['size' => 'medium']),
            'available_sizes' => json_encode(['small', 'medium', 'large']),
            'data_source' => $this->faker->randomElement(['database', 'api', 'calculated']),
            'min_refresh_interval' => $this->faker->numberBetween(30, 300),
            'required_permissions' => json_encode([]),
            'is_active' => $this->faker->boolean(70),
            'widget_type' => $this->faker->randomElement(['chart', 'stat', 'table']),
            'widget_name' => $this->faker->words(3, true),
        ];
    }
}
