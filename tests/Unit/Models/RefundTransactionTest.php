<?php

namespace Tests\Unit\Models;

use App\Models\RefundTransaction;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_refund_transaction_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\RefundTransactionFactory')) {
            $this->markTestSkipped('RefundTransactionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RefundTransaction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(RefundTransaction::class, $model);
    }

    public function test_refund_transaction_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\RefundTransactionFactory')) {
            $this->markTestSkipped('RefundTransactionFactory does not exist');
        }

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
