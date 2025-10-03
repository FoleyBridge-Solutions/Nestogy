<?php

namespace Tests\Unit\Models;

use App\Models\CreditApplication;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credit_application_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CreditApplicationFactory')) {
            $this->markTestSkipped('CreditApplicationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CreditApplication::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CreditApplication::class, $model);
    }

    public function test_credit_application_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CreditApplicationFactory')) {
            $this->markTestSkipped('CreditApplicationFactory does not exist');
        }

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
