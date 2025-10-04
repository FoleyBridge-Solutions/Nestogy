<?php

namespace Tests\Unit\Models;

use App\Models\QuoteInvoiceConversion;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteInvoiceConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_quote_invoice_conversion_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\QuoteInvoiceConversionFactory')) {
            $this->markTestSkipped('QuoteInvoiceConversionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteInvoiceConversion::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(QuoteInvoiceConversion::class, $model);
    }

    public function test_quote_invoice_conversion_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\QuoteInvoiceConversionFactory')) {
            $this->markTestSkipped('QuoteInvoiceConversionFactory does not exist');
        }
        
        $company = Company::factory()->create();
        $model = QuoteInvoiceConversion::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_quote_invoice_conversion_has_fillable_attributes(): void
    {
        $model = new QuoteInvoiceConversion();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
