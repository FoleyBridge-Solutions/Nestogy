<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\Address;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_address_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Address::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Address::class, $model);
    }

    public function test_address_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Address::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_address_has_fillable_attributes(): void
    {
        $model = new Address();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
