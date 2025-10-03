<?php

namespace Tests\Unit\Models;

use App\Models\DunningCampaign;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DunningCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_dunning_campaign_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\DunningCampaignFactory')) {
            $this->markTestSkipped('DunningCampaignFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = DunningCampaign::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(DunningCampaign::class, $model);
    }

    public function test_dunning_campaign_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\DunningCampaignFactory')) {
            $this->markTestSkipped('DunningCampaignFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = DunningCampaign::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_dunning_campaign_has_fillable_attributes(): void
    {
        $model = new DunningCampaign();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
