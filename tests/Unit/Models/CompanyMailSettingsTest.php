<?php

namespace Tests\Unit\Models;

use App\Domains\Company\Models\CompanyMailSettings;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class CompanyMailSettingsTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_company_mail_settings_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = CompanyMailSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CompanyMailSettings::class, $model);
    }

    public function test_company_mail_settings_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = CompanyMailSettings::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_company_mail_settings_has_fillable_attributes(): void
    {
        $model = new CompanyMailSettings();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
