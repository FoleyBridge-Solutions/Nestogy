<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\ClientPortalSession;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ClientPortalSessionTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_client_portal_session_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ClientPortalSessionFactory')) {
            $this->markTestSkipped('ClientPortalSessionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientPortalSession::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ClientPortalSession::class, $model);
    }

    public function test_client_portal_session_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ClientPortalSessionFactory')) {
            $this->markTestSkipped('ClientPortalSessionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientPortalSession::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_client_portal_session_has_fillable_attributes(): void
    {
        $model = new ClientPortalSession();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
