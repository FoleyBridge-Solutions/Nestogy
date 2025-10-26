<?php

namespace Tests\Unit\Models;

use App\Domains\Collections\Models\DunningAction;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class DunningActionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_dunning_action_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = DunningAction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(DunningAction::class, $model);
    }

    public function test_dunning_action_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = DunningAction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_dunning_action_has_fillable_attributes(): void
    {
        $model = new DunningAction();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
