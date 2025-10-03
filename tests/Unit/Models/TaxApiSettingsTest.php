<?php

namespace Tests\Unit\Models;

use App\Models\TaxApiSettings;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxApiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tax_api_settings_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxApiSettingsFactory')) {
            $this->markTestSkipped('TaxApiSettingsFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxApiSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxApiSettings::class, $model);
    }

    public function test_tax_api_settings_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxApiSettingsFactory')) {
            $this->markTestSkipped('TaxApiSettingsFactory does not exist');
        }

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
