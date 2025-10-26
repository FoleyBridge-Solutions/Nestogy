<?php

namespace Tests\Unit\Models;

use App\Domains\Company\Models\CreditApplication;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class CreditApplicationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_credit_application_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = CreditApplication::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CreditApplication::class, $model);
    }

    public function test_credit_application_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = CreditApplication::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_credit_application_has_fillable_attributes(): void
    {
        $model = new CreditApplication();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
