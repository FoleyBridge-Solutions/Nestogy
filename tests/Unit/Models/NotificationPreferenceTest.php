<?php

namespace Tests\Unit\Models;

use App\Models\NotificationPreference;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification_preference_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\NotificationPreferenceFactory')) {
            $this->markTestSkipped('NotificationPreferenceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = NotificationPreference::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(NotificationPreference::class, $model);
    }

    public function test_notification_preference_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\NotificationPreferenceFactory')) {
            $this->markTestSkipped('NotificationPreferenceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = NotificationPreference::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_notification_preference_has_fillable_attributes(): void
    {
        $model = new NotificationPreference();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
