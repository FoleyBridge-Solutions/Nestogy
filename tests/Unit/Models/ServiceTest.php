<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\Service;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_service_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Service::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Service::class, $model);
    }

    public function test_service_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Service::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_service_has_fillable_attributes(): void
    {
        $model = new Service();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
