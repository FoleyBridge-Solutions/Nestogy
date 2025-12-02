<?php

namespace Tests\Unit\Models\Tax;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    public function test_can_create_tax(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Tax::class, $tax);
        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $tax->company);
        $this->assertEquals($this->company->id, $tax->company->id);
    }

    public function test_casts_rate_as_decimal(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'rate' => 8.75,
        ]);

        $this->assertEquals(8.75, $tax->rate);
    }

    public function test_casts_boolean_fields(): void
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertIsBool($tax->is_default);
        $this->assertIsBool($tax->is_active);
        $this->assertTrue($tax->is_default);
        $this->assertTrue($tax->is_active);
    }
}
