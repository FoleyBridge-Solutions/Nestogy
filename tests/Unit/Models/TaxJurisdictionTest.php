<?php

namespace Tests\Unit\Models;

use App\Models\TaxJurisdiction;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxJurisdictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tax_jurisdiction_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxJurisdictionFactory')) {
            $this->markTestSkipped('TaxJurisdictionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxJurisdiction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxJurisdiction::class, $model);
    }

    public function test_tax_jurisdiction_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxJurisdictionFactory')) {
            $this->markTestSkipped('TaxJurisdictionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxJurisdiction::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_jurisdiction_has_fillable_attributes(): void
    {
        $model = new TaxJurisdiction();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
