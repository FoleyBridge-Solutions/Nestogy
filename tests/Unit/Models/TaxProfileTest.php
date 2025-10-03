<?php

namespace Tests\Unit\Models;

use App\Models\TaxProfile;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tax_profile_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TaxProfileFactory')) {
            $this->markTestSkipped('TaxProfileFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxProfile::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(TaxProfile::class, $model);
    }

    public function test_tax_profile_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TaxProfileFactory')) {
            $this->markTestSkipped('TaxProfileFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = TaxProfile::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tax_profile_has_fillable_attributes(): void
    {
        $model = new TaxProfile();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
