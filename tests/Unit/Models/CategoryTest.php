<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\Category;
use App\Domains\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function category_has_correct_fillable_fields()
    {
        $fillable = [
            'company_id',
            'name',
            'type',
            'code',
            'slug',
            'description',
            'color',
            'icon',
            'parent_id',
            'sort_order',
            'is_active',
            'metadata',
        ];

        $category = new Category();

        $this->assertEquals($fillable, $category->getFillable());
    }

    /** @test */
    public function metadata_cast_to_array()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertIsArray($category->metadata);
        $this->assertEquals('value', $category->metadata['key']);
    }

    /** @test */
    public function scope_expense_categories_filters_correctly()
    {
        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_EXPENSE_CATEGORY,
            'name' => 'Expense Cat',
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
            'name' => 'Product Cat',
        ]);

        $expenseCategories = Category::expenseCategories()->get();

        $this->assertCount(1, $expenseCategories);
        $this->assertEquals('Expense Cat', $expenseCategories->first()->name);
    }

    /** @test */
    public function scope_active_filters_correctly()
    {
        Category::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'name' => 'Active',
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
            'name' => 'Inactive',
        ]);

        $activeCategories = Category::active()->get();

        $this->assertCount(1, $activeCategories);
        $this->assertEquals('Active', $activeCategories->first()->name);
    }

    /** @test */
    public function scope_by_type_filters_correctly()
    {
        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_EXPENSE,
        ]);

        $productCategories = Category::byType(Category::TYPE_PRODUCT)->get();

        $this->assertCount(1, $productCategories);
    }

    /** @test */
    public function get_expense_settings_returns_correct_structure()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_EXPENSE_CATEGORY,
            'metadata' => [
                'requires_approval' => true,
                'approval_limit' => 500.00,
                'is_billable_default' => true,
                'markup_percentage_default' => 15.00,
            ],
        ]);

        $settings = $category->getExpenseSettings();

        $this->assertIsObject($settings);
        $this->assertTrue($settings->requires_approval);
        $this->assertEquals(500.00, $settings->approval_limit);
        $this->assertTrue($settings->is_billable_default);
        $this->assertEquals(15.00, $settings->markup_percentage_default);
    }

    /** @test */
    public function get_expense_settings_returns_null_for_non_expense_type()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
        ]);

        $settings = $category->getExpenseSettings();

        $this->assertNull($settings);
    }

    /** @test */
    public function requires_approval_for_amount_works_correctly()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_EXPENSE_CATEGORY,
            'metadata' => [
                'requires_approval' => true,
                'approval_limit' => 500.00,
            ],
        ]);

        $this->assertTrue($category->requiresApprovalForAmount(600.00));
        $this->assertFalse($category->requiresApprovalForAmount(400.00));
    }

    /** @test */
    public function requires_approval_returns_false_when_not_required()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_EXPENSE_CATEGORY,
            'metadata' => [
                'requires_approval' => false,
            ],
        ]);

        $this->assertFalse($category->requiresApprovalForAmount(1000.00));
    }

    /** @test */
    public function requires_approval_returns_false_for_non_expense_type()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
        ]);

        $this->assertFalse($category->requiresApprovalForAmount(1000.00));
    }

    /** @test */
    public function get_type_label_returns_correct_label()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
        ]);

        $this->assertEquals('Product', $category->getTypeLabel());
    }

    /** @test */
    public function get_color_returns_default_when_not_set()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
            'color' => null,
        ]);

        $defaultColor = Category::DEFAULT_COLORS[Category::TYPE_PRODUCT];
        $this->assertEquals($defaultColor, $category->getColor());
    }

    /** @test */
    public function get_color_returns_custom_color_when_set()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
            'color' => '#FF5733',
        ]);

        $this->assertEquals('#FF5733', $category->getColor());
    }

    /** @test */
    public function parent_relationship_works()
    {
        $parent = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Parent',
        ]);

        $child = Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($child->parent->is($parent));
    }

    /** @test */
    public function children_relationship_works()
    {
        $parent = Category::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $child = Category::factory()->create([
            'company_id' => $this->company->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertCount(1, $parent->children);
        $this->assertTrue($parent->children->first()->is($child));
    }

    /** @test */
    public function scope_ordered_sorts_by_sort_order()
    {
        Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'C',
            'sort_order' => 3,
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'A',
            'sort_order' => 1,
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'B',
            'sort_order' => 2,
        ]);

        $categories = Category::ordered()->pluck('name')->toArray();

        $this->assertEquals(['A', 'B', 'C'], $categories);
    }

    /** @test */
    public function has_children_returns_true_when_category_has_children()
    {
        $parent = Category::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($parent->hasChildren());
    }

    /** @test */
    public function has_children_returns_false_when_category_has_no_children()
    {
        $category = Category::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertFalse($category->hasChildren());
    }

    /** @test */
    public function scope_report_categories_filters_correctly()
    {
        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_REPORT,
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
        ]);

        $reportCategories = Category::reportCategories()->get();

        $this->assertCount(1, $reportCategories);
        $this->assertEquals(Category::TYPE_REPORT, $reportCategories->first()->type);
    }

    /** @test */
    public function scope_kb_categories_filters_correctly()
    {
        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_KB,
        ]);

        Category::factory()->create([
            'company_id' => $this->company->id,
            'type' => Category::TYPE_PRODUCT,
        ]);

        $kbCategories = Category::kbCategories()->get();

        $this->assertCount(1, $kbCategories);
        $this->assertEquals(Category::TYPE_KB, $kbCategories->first()->type);
    }
}
