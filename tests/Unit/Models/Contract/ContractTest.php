<?php

namespace Tests\Unit\Models\Contract;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_can_create_contract(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $contract->company);
        $this->assertEquals($this->company->id, $contract->company->id);
    }

    public function test_belongs_to_client(): void
    {
        $contract = Contract::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Client::class, $contract->client);
        $this->assertEquals($this->client->id, $contract->client->id);
    }

    public function test_casts_boolean_fields(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'auto_renew' => true,
            'is_active' => true,
        ]);

        $this->assertIsBool($contract->auto_renew);
        $this->assertIsBool($contract->is_active);
        $this->assertTrue($contract->auto_renew);
        $this->assertTrue($contract->is_active);
    }

    public function test_casts_date_fields(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $contract->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $contract->end_date);
    }

    public function test_casts_value_as_decimal(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'value' => 15000.50,
        ]);

        $this->assertEquals(15000.50, $contract->value);
    }
}
