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
            'widget_type' => null,
            'widget_name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'dashboard_type' => null,
            'configuration' => null,
            'display_settings' => null,
            'data_source' => null,
            'refresh_settings' => null,
            'permissions' => null,
            'sort_order' => null,
            'grid_row' => null,
            'grid_column' => null,
            'grid_width' => null,
            'grid_height' => null,
            'is_visible' => true,
            'is_active' => true,
            'is_default' => true,
            'last_updated_at' => null,
            'metadata' => null,
            'created_by' => null
        ];
    }
}
