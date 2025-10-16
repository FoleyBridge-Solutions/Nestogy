<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\Location;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_location_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\LocationFactory')) {
            $this->markTestSkipped('LocationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Location::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Location::class, $model);
    }

    public function test_location_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\LocationFactory')) {
            $this->markTestSkipped('LocationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Location::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_location_has_fillable_attributes(): void
    {
        $model = new Location();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
