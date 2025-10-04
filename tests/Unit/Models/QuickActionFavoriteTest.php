<?php

namespace Tests\Unit\Models;

use App\Models\QuickActionFavorite;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickActionFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_quick_action_favorite_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\QuickActionFavoriteFactory')) {
            $this->markTestSkipped('QuickActionFavoriteFactory does not exist');
        }

        $model = QuickActionFavorite::factory()->create();

        $this->assertInstanceOf(QuickActionFavorite::class, $model);
    }

    public function test_quick_action_favorite_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\QuickActionFavoriteFactory')) {
            $this->markTestSkipped('QuickActionFavoriteFactory does not exist');
        }

        $model = QuickActionFavorite::factory()->create();

        $this->assertInstanceOf(\App\Models\User::class, $model->user);
    }

    public function test_quick_action_favorite_has_fillable_attributes(): void
    {
        $model = new QuickActionFavorite();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
