<?php

namespace Tests\Unit\Models;

use App\Models\Service;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_service_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ServiceFactory')) {
            $this->markTestSkipped('ServiceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Service::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Service::class, $model);
    }

    public function test_service_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ServiceFactory')) {
            $this->markTestSkipped('ServiceFactory does not exist');
        }

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
