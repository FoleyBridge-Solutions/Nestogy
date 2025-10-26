<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\InAppNotification;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_in_app_notification_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = InAppNotification::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(InAppNotification::class, $model);
    }

    public function test_in_app_notification_belongs_to_company(): void
    {
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
