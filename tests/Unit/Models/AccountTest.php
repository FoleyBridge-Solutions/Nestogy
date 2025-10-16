<?php

namespace Tests\Unit\Models;

use App\Domains\Company\Models\Account;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_account_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Account::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Account::class, $model);
    }

    public function test_account_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Account::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_account_has_fillable_attributes(): void
    {
        $model = new Account();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
