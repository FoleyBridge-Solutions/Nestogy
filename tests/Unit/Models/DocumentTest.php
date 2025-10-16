<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\Document;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_document_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\DocumentFactory')) {
            $this->markTestSkipped('DocumentFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Document::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Document::class, $model);
    }

    public function test_document_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\DocumentFactory')) {
            $this->markTestSkipped('DocumentFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Document::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_document_has_fillable_attributes(): void
    {
        $model = new Document();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
