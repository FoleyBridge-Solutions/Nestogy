<?php

namespace Tests\Unit\Models;

use App\Models\PhysicalMailSettings;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_physical_mail_settings_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\PhysicalMailSettingsFactory')) {
            $this->markTestSkipped('PhysicalMailSettingsFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = PhysicalMailSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PhysicalMailSettings::class, $model);
    }

    public function test_physical_mail_settings_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\PhysicalMailSettingsFactory')) {
            $this->markTestSkipped('PhysicalMailSettingsFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = PhysicalMailSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_physical_mail_settings_has_fillable_attributes(): void
    {
        $model = new PhysicalMailSettings();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
