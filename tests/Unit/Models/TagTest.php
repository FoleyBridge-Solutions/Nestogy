<?php

namespace Tests\Unit\Models;

use App\Models\Tag;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tag_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\TagFactory')) {
            $this->markTestSkipped('TagFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Tag::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Tag::class, $model);
    }

    public function test_tag_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\TagFactory')) {
            $this->markTestSkipped('TagFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Tag::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_tag_has_fillable_attributes(): void
    {
        $model = new Tag();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
