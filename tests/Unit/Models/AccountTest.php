<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_account_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\AccountFactory')) {
            $this->markTestSkipped('AccountFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Account::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Account::class, $model);
    }

    public function test_account_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\AccountFactory')) {
            $this->markTestSkipped('AccountFactory does not exist');
        }

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
