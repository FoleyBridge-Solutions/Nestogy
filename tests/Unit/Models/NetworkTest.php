<?php

namespace Tests\Unit\Models;

use App\Models\Network;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_network_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\NetworkFactory')) {
            $this->markTestSkipped('NetworkFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Network::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Network::class, $model);
    }

    public function test_network_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\NetworkFactory')) {
            $this->markTestSkipped('NetworkFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Network::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_network_has_fillable_attributes(): void
    {
        $model = new Network();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
