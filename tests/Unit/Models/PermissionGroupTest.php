<?php

namespace Tests\Unit\Models;

use App\Models\PermissionGroup;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_permission_group_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\PermissionGroupFactory')) {
            $this->markTestSkipped('PermissionGroupFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = PermissionGroup::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PermissionGroup::class, $model);
    }

    public function test_permission_group_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\PermissionGroupFactory')) {
            $this->markTestSkipped('PermissionGroupFactory does not exist');
        }

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
