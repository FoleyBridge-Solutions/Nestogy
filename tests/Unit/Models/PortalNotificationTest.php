<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\PortalNotification;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PortalNotificationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_portal_notification_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = PortalNotification::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PortalNotification::class, $model);
    }

    public function test_portal_notification_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = PortalNotification::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_portal_notification_has_fillable_attributes(): void
    {
        $model = new PortalNotification();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
