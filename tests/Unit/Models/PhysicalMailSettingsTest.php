<?php

namespace Tests\Unit\Models;

use App\Domains\PhysicalMail\Models\PhysicalMailSettings;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PhysicalMailSettingsTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_physical_mail_settings_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = PhysicalMailSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PhysicalMailSettings::class, $model);
    }

    public function test_physical_mail_settings_belongs_to_company(): void
    {
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
