<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\RefundTransaction;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class RefundTransactionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_refund_transaction_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = RefundTransaction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(RefundTransaction::class, $model);
    }

    public function test_refund_transaction_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = RefundTransaction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_refund_transaction_has_fillable_attributes(): void
    {
        $model = new RefundTransaction();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
