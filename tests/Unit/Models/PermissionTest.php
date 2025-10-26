<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\Permission;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_permission_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Permission::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Permission::class, $model);
    }

    public function test_permission_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Permission::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_permission_has_fillable_attributes(): void
    {
        $model = new Permission();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
