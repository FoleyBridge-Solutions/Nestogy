<?php

namespace Tests\Unit\Models;

use App\Domains\Asset\Models\Asset;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_asset_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Asset::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Asset::class, $model);
    }

    public function test_asset_belongs_to_company(): void
    {
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
