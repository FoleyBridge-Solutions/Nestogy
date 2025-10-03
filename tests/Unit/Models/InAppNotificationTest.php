<?php

namespace Tests\Unit\Models;

use App\Models\InAppNotification;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_in_app_notification_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\InAppNotificationFactory')) {
            $this->markTestSkipped('InAppNotificationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = InAppNotification::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(InAppNotification::class, $model);
    }

    public function test_in_app_notification_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\InAppNotificationFactory')) {
            $this->markTestSkipped('InAppNotificationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = InAppNotification::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_in_app_notification_has_fillable_attributes(): void
    {
        $model = new InAppNotification();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
