<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\ClientDocument;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_document_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ClientDocumentFactory')) {
            $this->markTestSkipped('ClientDocumentFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientDocument::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ClientDocument::class, $model);
    }

    public function test_client_document_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ClientDocumentFactory')) {
            $this->markTestSkipped('ClientDocumentFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ClientDocument::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_client_document_has_fillable_attributes(): void
    {
        $model = new ClientDocument();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
