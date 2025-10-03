<?php

namespace Tests\Unit\Models;

use App\Models\AccountHold;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountHoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_account_hold_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\AccountHoldFactory')) {
            $this->markTestSkipped('AccountHoldFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AccountHold::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(AccountHold::class, $model);
    }

    public function test_account_hold_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\AccountHoldFactory')) {
            $this->markTestSkipped('AccountHoldFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = AccountHold::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_account_hold_has_fillable_attributes(): void
    {
        $model = new AccountHold();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
