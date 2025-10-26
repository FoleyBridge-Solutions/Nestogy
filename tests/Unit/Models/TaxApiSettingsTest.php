<?php

namespace Tests\Unit\Models;

use App\Domains\Tax\Models\TaxApiSettings;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TaxApiSettingsTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_tax_api_settings_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = TaxApiSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxApiSettings::class, $model);
    }

    public function test_tax_api_settings_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = TaxApiSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_api_settings_has_fillable_attributes(): void
    {
        $model = new TaxApiSettings();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
