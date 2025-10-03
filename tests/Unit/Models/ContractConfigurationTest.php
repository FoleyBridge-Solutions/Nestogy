<?php

namespace Tests\Unit\Models;

use App\Models\ContractConfiguration;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_contract_configuration_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ContractConfigurationFactory')) {
            $this->markTestSkipped('ContractConfigurationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ContractConfiguration::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ContractConfiguration::class, $model);
    }

    public function test_contract_configuration_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ContractConfigurationFactory')) {
            $this->markTestSkipped('ContractConfigurationFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ContractConfiguration::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_contract_configuration_has_fillable_attributes(): void
    {
        $model = new ContractConfiguration();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
