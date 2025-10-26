<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\PermissionGroup;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PermissionGroupTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_permission_group_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = PermissionGroup::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PermissionGroup::class, $model);
    }

    public function test_permission_group_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = PermissionGroup::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_permission_group_has_fillable_attributes(): void
    {
        $model = new PermissionGroup();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
