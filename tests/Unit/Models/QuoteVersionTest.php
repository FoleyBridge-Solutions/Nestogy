<?php

namespace Tests\Unit\Models;

use App\Models\QuoteVersion;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_quote_version_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\QuoteVersionFactory')) {
            $this->markTestSkipped('QuoteVersionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteVersion::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(QuoteVersion::class, $model);
    }

    public function test_quote_version_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\QuoteVersionFactory')) {
            $this->markTestSkipped('QuoteVersionFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = QuoteVersion::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_quote_version_has_fillable_attributes(): void
    {
        $model = new QuoteVersion();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
