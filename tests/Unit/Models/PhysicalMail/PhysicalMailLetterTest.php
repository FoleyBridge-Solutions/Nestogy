<?php

namespace Tests\Unit\Models\PhysicalMail;

use App\Domains\PhysicalMail\Models\PhysicalMailLetter;
use App\Domains\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalMailLetterTest extends TestCase
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
        $model = PhysicalMailLetter::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertInstanceOf(PhysicalMailLetter::class, $model);
        $this->assertDatabaseHas('physical_mail_letter_s', ['id' => $model->id]);
    }

    public function test_belongs_to_company(): void
    {
        $model = PhysicalMailLetter::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertEquals($this->company->id, $model->company_id);
        $this->assertInstanceOf(Company::class, $model->company);
    }

    public function test_isolates_by_company(): void
    {
        $otherCompany = Company::factory()->create();
        
        $myModel = PhysicalMailLetter::factory()->create(['company_id' => $this->company->id]);
        $otherModel = PhysicalMailLetter::factory()->create(['company_id' => $otherCompany->id]);
        
        $this->assertNotEquals($myModel->company_id, $otherModel->company_id);
    }
}
