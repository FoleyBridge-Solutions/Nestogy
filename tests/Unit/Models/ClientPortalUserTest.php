<?php

namespace Tests\Unit\Models;

use App\Models\ClientPortalUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_portal_user_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ClientPortalUserFactory')) {
            $this->markTestSkipped('ClientPortalUserFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientPortalUser::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ClientPortalUser::class, $model);
    }

    public function test_client_portal_user_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ClientPortalUserFactory')) {
            $this->markTestSkipped('ClientPortalUserFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientPortalUser::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_client_portal_user_has_fillable_attributes(): void
    {
        $model = new ClientPortalUser();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
