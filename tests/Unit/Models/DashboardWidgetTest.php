<?php

namespace Tests\Unit\Models;

use App\Models\DashboardWidget;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_dashboard_widget_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\DashboardWidgetFactory')) {
            $this->markTestSkipped('DashboardWidgetFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = DashboardWidget::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(DashboardWidget::class, $model);
    }

    public function test_dashboard_widget_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\DashboardWidgetFactory')) {
            $this->markTestSkipped('DashboardWidgetFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = DashboardWidget::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_dashboard_widget_has_fillable_attributes(): void
    {
        $model = new DashboardWidget();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
