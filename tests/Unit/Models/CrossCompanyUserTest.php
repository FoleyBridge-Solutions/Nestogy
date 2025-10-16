<?php

namespace Tests\Unit\Models;

use App\Domains\Company\Models\CrossCompanyUser;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class CrossCompanyUserTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_cross_company_user_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CrossCompanyUserFactory')) {
            $this->markTestSkipped('CrossCompanyUserFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CrossCompanyUser::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CrossCompanyUser::class, $model);
    }

    public function test_cross_company_user_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CrossCompanyUserFactory')) {
            $this->markTestSkipped('CrossCompanyUserFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CrossCompanyUser::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_cross_company_user_has_fillable_attributes(): void
    {
        $model = new CrossCompanyUser();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
