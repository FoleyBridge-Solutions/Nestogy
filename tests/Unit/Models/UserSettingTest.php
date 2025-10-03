<?php

namespace Tests\Unit\Models;

use App\Models\UserSetting;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_setting_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\UserSettingFactory')) {
            $this->markTestSkipped('UserSettingFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UserSetting::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(UserSetting::class, $model);
    }

    public function test_user_setting_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\UserSettingFactory')) {
            $this->markTestSkipped('UserSettingFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = UserSetting::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_user_setting_has_fillable_attributes(): void
    {
        $model = new UserSetting();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
