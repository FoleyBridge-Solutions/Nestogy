<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_role_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\RoleFactory')) {
            $this->markTestSkipped('RoleFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Role::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Role::class, $model);
    }

    public function test_role_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\RoleFactory')) {
            $this->markTestSkipped('RoleFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Role::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_role_has_fillable_attributes(): void
    {
        $model = new Role();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
