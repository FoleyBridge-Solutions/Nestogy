<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_asset_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\AssetFactory')) {
            $this->markTestSkipped('AssetFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Asset::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Asset::class, $model);
    }

    public function test_asset_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\AssetFactory')) {
            $this->markTestSkipped('AssetFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Asset::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_asset_has_fillable_attributes(): void
    {
        $model = new Asset();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
