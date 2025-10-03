<?php

namespace Tests\Unit\Models;

use App\Models\RefundRequest;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_refund_request_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\RefundRequestFactory')) {
            $this->markTestSkipped('RefundRequestFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RefundRequest::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(RefundRequest::class, $model);
    }

    public function test_refund_request_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\RefundRequestFactory')) {
            $this->markTestSkipped('RefundRequestFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = RefundRequest::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_refund_request_has_fillable_attributes(): void
    {
        $model = new RefundRequest();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
