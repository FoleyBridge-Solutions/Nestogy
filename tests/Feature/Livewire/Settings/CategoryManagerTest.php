<?php

namespace Tests\Feature\Livewire\Settings;

use App\Livewire\Settings\CategoryManager;
use App\Domains\Financial\Models\Category;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function component_renders_successfully()
    {
        Livewire::test(CategoryManager::class)
            ->assertStatus(200)
            ->assertSee('Categories');
    }

    /** @test */
    public function displays_categories_table()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Category',
            'type' => Category::TYPE_PRODUCT,
        ]);

        Livewire::test(CategoryManager::class)
            ->assertSee('Test Category')
            ->assertSee(Category::TYPE_LABELS[Category::TYPE_PRODUCT]);
    }

    /** @test */
    public function can_filter_by_type()
    {
        $productCategory = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'UniqueProductName12345',
            'type' => Category::TYPE_PRODUCT,
        ]);

        $expenseCategory = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'UniqueExpenseName67890',
            'type' => Category::TYPE_EXPENSE,
        ]);

        $component = Livewire::test(CategoryManager::class)
            ->set('typeFilter', Category::TYPE_PRODUCT);

        // Verify the categories collection only contains the product category
        $categories = $component->viewData('categories');
        $this->assertTrue($categories->contains('id', $productCategory->id));
        $this->assertFalse($categories->contains('id', $expenseCategory->id));
        $this->assertEquals(1, $categories->count());

        // Check that Product category name appears in the output
        $component->assertSee('UniqueProductName12345');
    }

    /** @test */
    public function search_filters_categories()
    {
        $hardwareCategory = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Hardware Equipment',
        ]);

        $softwareCategory = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Software Licenses',
        ]);

        $component = Livewire::test(CategoryManager::class)
            ->set('search', 'Hardware');

        // Verify the categories collection only contains the hardware category
        $categories = $component->viewData('categories');
        $this->assertTrue($categories->contains('id', $hardwareCategory->id));
        $this->assertFalse($categories->contains('id', $softwareCategory->id));
        $this->assertEquals(1, $categories->count());

        // Check that Hardware category name appears in the output
        $component->assertSee('Hardware Equipment');
    }

    /** @test */
    public function search_is_debounced()
    {
        Livewire::test(CategoryManager::class)
            ->assertSet('search', '');
    }

    /** @test */
    public function can_open_create_modal()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->assertSet('showModal', true)
            ->assertSet('editing', null)
            ->assertSee('New Category');
    }

    /** @test */
    public function can_create_category()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'New Category')
            ->set('form.type', Category::TYPE_PRODUCT)
            ->set('form.description', 'Test description')
            ->set('form.is_active', true)
            ->call('save')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('categories', [
            'company_id' => $this->company->id,
            'name' => 'New Category',
            'type' => Category::TYPE_PRODUCT,
            'description' => 'Test description',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function create_requires_name_and_type()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.type', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.type']);
    }

    /** @test */
    public function can_edit_category()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'type' => Category::TYPE_PRODUCT,
        ]);

        Livewire::test(CategoryManager::class)
            ->call('edit', $category->id)
            ->assertSet('editing', $category->id)
            ->assertSet('form.name', 'Original Name')
            ->assertSet('showModal', true);
    }

    /** @test */
    public function can_update_category()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'type' => Category::TYPE_PRODUCT,
        ]);

        Livewire::test(CategoryManager::class)
            ->call('edit', $category->id)
            ->set('form.name', 'Updated Name')
            ->set('form.description', 'Updated description')
            ->call('save')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function can_toggle_active_status()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        Livewire::test(CategoryManager::class)
            ->call('toggleActive', $category->id);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function can_delete_category()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_KB, // Use a type that won't have associated records
        ]);

        Livewire::test(CategoryManager::class)
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
            'archived_at' => null,
        ]);
    }

    /** @test */
    public function cannot_delete_category_with_children()
    {
        $parent = Category::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $child = Category::factory()->create([
            'company_id' => $this->company->id,
            'parent_id' => $parent->id,
        ]);

        Livewire::test(CategoryManager::class)
            ->call('delete', $parent->id);

        // Category should still exist
        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
        ]);
    }

    /** @test */
    public function expense_metadata_saved_correctly()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Travel Expenses')
            ->set('form.type', Category::TYPE_EXPENSE_CATEGORY)
            ->set('form.metadata', [
                'requires_approval' => true,
                'approval_limit' => 500.00,
                'is_billable_default' => true,
                'markup_percentage_default' => 15.00,
            ])
            ->call('save');

        $category = Category::where('name', 'Travel Expenses')->first();

        $this->assertNotNull($category);
        $this->assertEquals(true, $category->metadata['requires_approval']);
        $this->assertEquals(500.00, $category->metadata['approval_limit']);
        $this->assertEquals(true, $category->metadata['is_billable_default']);
        $this->assertEquals(15.00, $category->metadata['markup_percentage_default']);
    }

    /** @test */
    public function modal_closes_after_save()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Test Category')
            ->set('form.type', Category::TYPE_PRODUCT)
            ->call('save')
            ->assertSet('showModal', false);
    }

    /** @test */
    public function form_resets_after_save()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Test Category')
            ->set('form.type', Category::TYPE_PRODUCT)
            ->call('save')
            ->assertSet('form.name', '')
            ->assertSet('form.type', '')
            ->assertSet('editing', null);
    }

    /** @test */
    public function categories_are_company_scoped()
    {
        $otherCompany = Company::factory()->create();

        $ourCategory = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Our Category',
        ]);

        $theirCategory = Category::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Their Category',
        ]);

        Livewire::test(CategoryManager::class)
            ->assertSee('Our Category')
            ->assertDontSee('Their Category');
    }

    /** @test */
    public function parent_category_dropdown_excludes_current()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Self Category',
            'is_active' => true,
        ]);

        $other = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Other Category',
            'is_active' => true,
        ]);

        $component = Livewire::test(CategoryManager::class)
            ->call('edit', $category->id);

        // Check that parent options don't include the current category
        $parentOptions = $component->viewData('parentOptions');
        $this->assertFalse($parentOptions->contains('id', $category->id));
        $this->assertTrue($parentOptions->contains('id', $other->id));
    }

    /** @test */
    public function color_picker_saves_valid_hex()
    {
        Livewire::test(CategoryManager::class)
            ->call('create')
            ->set('form.name', 'Colored Category')
            ->set('form.type', Category::TYPE_PRODUCT)
            ->set('form.color', '#FF5733')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'name' => 'Colored Category',
            'color' => '#FF5733',
        ]);
    }
}
