<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\ClientDocument;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ClientDocumentTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_client_document_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = ClientDocument::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ClientDocument::class, $model);
    }

    public function test_client_document_belongs_to_company(): void
    {
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
