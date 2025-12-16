<?php

namespace Tests\Unit\Models\Asset;

use App\Domains\Asset\Models\Asset;
use App\Domains\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
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
        $model = Asset::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertInstanceOf(Asset::class, $model);
        $this->assertDatabaseHas('assets', ['id' => $model->id]);
    }

    public function test_belongs_to_company(): void
    {
        $model = Asset::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertEquals($this->company->id, $model->company_id);
        $this->assertInstanceOf(Company::class, $model->company);
    }

    public function test_isolates_by_company(): void
    {
        $otherCompany = Company::factory()->create();
        
        $myModel = Asset::factory()->create(['company_id' => $this->company->id]);
        $otherModel = Asset::factory()->create(['company_id' => $otherCompany->id]);
        
        $this->assertNotEquals($myModel->company_id, $otherModel->company_id);
    }
}
