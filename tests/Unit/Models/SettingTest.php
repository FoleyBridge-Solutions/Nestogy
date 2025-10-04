<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_setting_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Setting::where('company_id', $company->id)->first();

        $this->assertInstanceOf(Setting::class, $model);
        $this->assertEquals($company->id, $model->company_id);
    }

    public function test_setting_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Setting::where('company_id', $company->id)->first();

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_setting_has_fillable_attributes(): void
    {
        $model = new Setting();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
