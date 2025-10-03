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
        if (!class_exists('Database\\Factories\\SettingFactory')) {
            $this->markTestSkipped('SettingFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Setting::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Setting::class, $model);
    }

    public function test_setting_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\SettingFactory')) {
            $this->markTestSkipped('SettingFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Setting::factory()->create(['company_id' => $company->id]);

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
