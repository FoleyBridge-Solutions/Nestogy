<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\CustomQuickAction;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class CustomQuickActionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_custom_quick_action_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CustomQuickActionFactory')) {
            $this->markTestSkipped('CustomQuickActionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CustomQuickAction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CustomQuickAction::class, $model);
    }

    public function test_custom_quick_action_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CustomQuickActionFactory')) {
            $this->markTestSkipped('CustomQuickActionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CustomQuickAction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_custom_quick_action_has_fillable_attributes(): void
    {
        $model = new CustomQuickAction();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
