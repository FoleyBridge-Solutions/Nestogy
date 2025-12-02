<?php

namespace Tests\Unit\Models\Knowledge;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Knowledge\Models\KbArticle;
use App\Domains\Knowledge\Models\KbCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KbArticleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_can_create_kb_article(): void
    {
        $article = KbArticle::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(KbArticle::class, $article);
        $this->assertDatabaseHas('kb_articles', [
            'id' => $article->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $article = KbArticle::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $article->company);
        $this->assertEquals($this->company->id, $article->company->id);
    }

    public function test_belongs_to_category(): void
    {
        $category = KbCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $article = KbArticle::factory()->create([
            'company_id' => $this->company->id,
            'kb_category_id' => $category->id,
        ]);

        $this->assertInstanceOf(KbCategory::class, $article->category);
        $this->assertEquals($category->id, $article->category->id);
    }

    public function test_belongs_to_author(): void
    {
        $article = KbArticle::factory()->create([
            'company_id' => $this->company->id,
            'author_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $article->author);
        $this->assertEquals($this->user->id, $article->author->id);
    }

    public function test_casts_boolean_fields(): void
    {
        $article = KbArticle::factory()->create([
            'company_id' => $this->company->id,
            'is_published' => true,
            'is_featured' => false,
        ]);

        $this->assertIsBool($article->is_published);
        $this->assertIsBool($article->is_featured);
        $this->assertTrue($article->is_published);
        $this->assertFalse($article->is_featured);
    }

    public function test_casts_datetime_fields(): void
    {
        $article = KbArticle::factory()->create([
            'company_id' => $this->company->id,
            'published_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->published_at);
    }
}
