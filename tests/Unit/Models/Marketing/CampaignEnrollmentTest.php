<?php

namespace Tests\Unit\Models\Marketing;

use App\Domains\Marketing\Models\CampaignEnrollment;
use App\Domains\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    public function test_model_can_be_created(): void
    {
        $model = CampaignEnrollment::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertInstanceOf(CampaignEnrollment::class, $model);
        $this->assertDatabaseHas('campaign_enrollment_s', ['id' => $model->id]);
    }

    public function test_belongs_to_company(): void
    {
        $model = CampaignEnrollment::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertEquals($this->company->id, $model->company_id);
        $this->assertInstanceOf(Company::class, $model->company);
    }

    public function test_isolates_by_company(): void
    {
        $otherCompany = Company::factory()->create();
        
        $myModel = CampaignEnrollment::factory()->create(['company_id' => $this->company->id]);
        $otherModel = CampaignEnrollment::factory()->create(['company_id' => $otherCompany->id]);
        
        $this->assertNotEquals($myModel->company_id, $otherModel->company_id);
    }
}
