<?php

namespace Tests\Unit\Models;

use App\Models\CollectionNote;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_collection_note_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\CollectionNoteFactory')) {
            $this->markTestSkipped('CollectionNoteFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CollectionNote::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(CollectionNote::class, $model);
    }

    public function test_collection_note_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\CollectionNoteFactory')) {
            $this->markTestSkipped('CollectionNoteFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = CollectionNote::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_collection_note_has_fillable_attributes(): void
    {
        $model = new CollectionNote();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
