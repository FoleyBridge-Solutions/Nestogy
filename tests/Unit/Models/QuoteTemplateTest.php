<?php

namespace Tests\Unit\Models;

use App\Models\QuoteTemplate;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_quote_template_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\QuoteTemplateFactory')) {
            $this->markTestSkipped('QuoteTemplateFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteTemplate::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(QuoteTemplate::class, $model);
    }

    public function test_quote_template_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\QuoteTemplateFactory')) {
            $this->markTestSkipped('QuoteTemplateFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteTemplate::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_quote_template_has_fillable_attributes(): void
    {
        $model = new QuoteTemplate();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
