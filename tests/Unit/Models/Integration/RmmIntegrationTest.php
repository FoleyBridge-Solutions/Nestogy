<?php

namespace Tests\Unit\Models\Integration;

use App\Domains\Company\Models\Company;
use App\Domains\Integration\Models\RmmIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RmmIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    public function test_can_create_rmm_integration(): void
    {
        $integration = RmmIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(RmmIntegration::class, $integration);
        $this->assertDatabaseHas('rmm_integrations', [
            'id' => $integration->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $integration = RmmIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $integration->company);
        $this->assertEquals($this->company->id, $integration->company->id);
    }

    public function test_hides_sensitive_fields(): void
    {
        $integration = RmmIntegration::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $array = $integration->toArray();

        $this->assertArrayNotHasKey('api_key', $array);
        $this->assertArrayNotHasKey('api_secret', $array);
    }

    public function test_casts_boolean_fields(): void
    {
        $integration = RmmIntegration::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->assertIsBool($integration->is_active);
        $this->assertTrue($integration->is_active);
    }
}
