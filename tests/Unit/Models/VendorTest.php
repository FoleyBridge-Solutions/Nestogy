<?php

namespace Tests\Unit\Models;

use App\Domains\Project\Models\Vendor;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_vendor_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Vendor::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Vendor::class, $model);
    }

    public function test_vendor_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Vendor::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_vendor_has_fillable_attributes(): void
    {
        $model = new Vendor();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
