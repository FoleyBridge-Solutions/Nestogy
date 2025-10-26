<?php

namespace Tests\Unit\Models;

use App\Domains\Contract\Models\ContractConfiguration;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ContractConfigurationTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_contract_configuration_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = ContractConfiguration::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ContractConfiguration::class, $model);
    }

    public function test_contract_configuration_belongs_to_company(): void
    {
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
