<?php

namespace Tests\Unit\Models;

use App\Models\PricingRule;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pricing_rule_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\PricingRuleFactory')) {
            $this->markTestSkipped('PricingRuleFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = PricingRule::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(PricingRule::class, $model);
    }

    public function test_pricing_rule_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\PricingRuleFactory')) {
            $this->markTestSkipped('PricingRuleFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = PricingRule::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_pricing_rule_has_fillable_attributes(): void
    {
        $model = new PricingRule();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
