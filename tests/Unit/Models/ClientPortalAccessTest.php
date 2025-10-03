<?php

namespace Tests\Unit\Models;

use App\Models\ClientPortalAccess;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_portal_access_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ClientPortalAccessFactory')) {
            $this->markTestSkipped('ClientPortalAccessFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientPortalAccess::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ClientPortalAccess::class, $model);
    }

    public function test_client_portal_access_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ClientPortalAccessFactory')) {
            $this->markTestSkipped('ClientPortalAccessFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientPortalAccess::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_client_portal_access_has_fillable_attributes(): void
    {
        $model = new ClientPortalAccess();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
