<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\Quote;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_quote_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Quote::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Quote::class, $model);
    }

    public function test_quote_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $model = Quote::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_quote_has_fillable_attributes(): void
    {
        $model = new Quote();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
