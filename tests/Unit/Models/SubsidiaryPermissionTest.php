<?php

namespace Tests\Unit\Models;

use App\Domains\Company\Models\SubsidiaryPermission;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class SubsidiaryPermissionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_subsidiary_permission_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\SubsidiaryPermissionFactory')) {
            $this->markTestSkipped('SubsidiaryPermissionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = SubsidiaryPermission::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(SubsidiaryPermission::class, $model);
    }

    public function test_subsidiary_permission_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\SubsidiaryPermissionFactory')) {
            $this->markTestSkipped('SubsidiaryPermissionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = SubsidiaryPermission::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_subsidiary_permission_has_fillable_attributes(): void
    {
        $model = new SubsidiaryPermission();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
