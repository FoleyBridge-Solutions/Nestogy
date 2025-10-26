<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\Network;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_network_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Network::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Network::class, $model);
    }

    public function test_network_belongs_to_company(): void
    {
        $model = Network::factory()->create();

        $this->assertInstanceOf(\App\Domains\Client\Models\Client::class, $model->client);
    }

    public function test_network_has_fillable_attributes(): void
    {
        $model = new Network();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
