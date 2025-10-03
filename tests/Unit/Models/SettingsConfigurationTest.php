<?php

namespace Tests\Unit\Models;

use App\Models\SettingsConfiguration;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_settings_configuration_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\SettingsConfigurationFactory')) {
            $this->markTestSkipped('SettingsConfigurationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = SettingsConfiguration::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(SettingsConfiguration::class, $model);
    }

    public function test_settings_configuration_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\SettingsConfigurationFactory')) {
            $this->markTestSkipped('SettingsConfigurationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = SettingsConfiguration::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_settings_configuration_has_fillable_attributes(): void
    {
        $model = new SettingsConfiguration();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
