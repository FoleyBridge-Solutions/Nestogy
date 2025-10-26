<?php

namespace Tests\Unit\Models;

use App\Domains\Core\Models\Tag;
use App\Domains\Company\Models\Company;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshesDatabase;

    public function test_can_create_tag_with_factory(): void
    {
        $company = Company::factory()->create();
        $model = Tag::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Tag::class, $model);
    }

    public function test_tag_belongs_to_company(): void
    {
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
