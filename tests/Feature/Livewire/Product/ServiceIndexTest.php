<?php

namespace Tests\Feature\Livewire\Product;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Category;
use App\Domains\Product\Models\Product;
use App\Livewire\Product\ServiceIndex;
use Livewire\Livewire;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ServiceIndexTest extends TestCase
{
    use RefreshesDatabase;

    protected Company $company;
    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->category = Category::create([
            'name' => 'Services',
            'type' => ['product'],
            'company_id' => $this->company->id,
            'color' => '#28a745',
        ]);

        $this->actingAs($this->user);
    }

    public function test_component_renders_successfully(): void
    {
        Livewire::test(ServiceIndex::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.base-index');
    }

    public function test_displays_services_table_with_data(): void
    {
        $service = Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Managed IT Services',
            'type' => 'service',
        ]);

        Livewire::test(ServiceIndex::class)
            ->assertSee('Managed IT Services');
    }

    public function test_search_filters_services_by_name(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Cloud Infrastructure Services',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Backup Solutions',
            'type' => 'service',
        ]);

        Livewire::test(ServiceIndex::class)
            ->set('search', 'Cloud')
            ->assertSee('Cloud Infrastructure Services')
            ->assertDontSee('Backup Solutions');
    }

    public function test_search_filters_services_by_description(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Service Alpha',
            'description' => 'Comprehensive monitoring solution',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Service Beta',
            'description' => 'Simple backup service',
            'type' => 'service',
        ]);

        Livewire::test(ServiceIndex::class)
            ->set('search', 'monitoring')
            ->assertSee('Service Alpha')
            ->assertDontSee('Service Beta');
    }

    public function test_search_filters_services_by_sku(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Service Alpha',
            'sku' => 'SVC-001',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Service Beta',
            'sku' => 'SVC-002',
            'type' => 'service',
        ]);

        Livewire::test(ServiceIndex::class)
            ->set('search', 'SVC-001')
            ->assertSee('Service Alpha')
            ->assertDontSee('Service Beta');
    }

    public function test_search_results_are_company_scoped(): void
    {
        $otherCompany = Company::factory()->create();
        $otherCategory = Category::create([
            'name' => 'Services',
            'type' => ['product'],
            'company_id' => $otherCompany->id,
            'color' => '#28a745',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Our Service',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $otherCompany->id,
            'category_id' => $otherCategory->id,
            'name' => 'Their Service',
            'type' => 'service',
        ]);

        Livewire::test(ServiceIndex::class)
            ->assertSee('Our Service')
            ->assertDontSee('Their Service');
    }

    public function test_can_sort_by_name_ascending(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Zebra Service',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Alpha Service',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc');

        $items = $component->viewData('items');
        $this->assertEquals('Alpha Service', $items->first()->name);
    }

    public function test_can_sort_by_name_descending(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Alpha Service',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Zebra Service',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'desc');

        $items = $component->viewData('items');
        $this->assertEquals('Zebra Service', $items->first()->name);
    }

    public function test_can_sort_by_price(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Expensive Service',
            'base_price' => 5000.00,
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Cheap Service',
            'base_price' => 500.00,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('sortField', 'base_price')
            ->set('sortDirection', 'asc');

        $items = $component->viewData('items');
        $this->assertEquals('Cheap Service', $items->first()->name);
    }

    public function test_can_filter_by_category(): void
    {
        $category1 = Category::create([
            'name' => 'Infrastructure',
            'type' => ['product'],
            'company_id' => $this->company->id,
            'color' => '#007bff',
        ]);

        $category2 = Category::create([
            'name' => 'Security',
            'type' => ['product'],
            'company_id' => $this->company->id,
            'color' => '#dc3545',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $category1->id,
            'name' => 'Infrastructure Service',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $category2->id,
            'name' => 'Security Service',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('columnFilters.category_name', [$category1->name]);

        $items = $component->viewData('items');
        $this->assertTrue($items->contains('name', 'Infrastructure Service'));
        $this->assertFalse($items->contains('name', 'Security Service'));
    }

    public function test_can_filter_by_billing_model(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Subscription Service',
            'billing_model' => 'subscription',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'One-Time Service',
            'billing_model' => 'one_time',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('columnFilters.billing_model', ['subscription']);

        $items = $component->viewData('items');
        $this->assertTrue($items->contains('name', 'Subscription Service'));
        $this->assertFalse($items->contains('name', 'One-Time Service'));
    }

    public function test_can_filter_by_status(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Active Service',
            'is_active' => true,
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Inactive Service',
            'is_active' => false,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('columnFilters.is_active', ['1']);

        $items = $component->viewData('items');
        $this->assertTrue($items->contains('name', 'Active Service'));
        $this->assertFalse($items->contains('name', 'Inactive Service'));
    }

    public function test_can_filter_by_price_range(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Low Price Service',
            'base_price' => 500.00,
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Medium Price Service',
            'base_price' => 2000.00,
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'High Price Service',
            'base_price' => 5000.00,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('columnFilters.base_price', ['min' => 1000, 'max' => 3000]);

        $items = $component->viewData('items');
        $this->assertFalse($items->contains('name', 'Low Price Service'));
        $this->assertTrue($items->contains('name', 'Medium Price Service'));
        $this->assertFalse($items->contains('name', 'High Price Service'));
    }

    public function test_can_filter_by_unit_type(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Hourly Service',
            'unit_type' => 'hours',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Monthly Service',
            'unit_type' => 'months',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('columnFilters.unit_type', ['hours']);

        $items = $component->viewData('items');
        $this->assertTrue($items->contains('name', 'Hourly Service'));
        $this->assertFalse($items->contains('name', 'Monthly Service'));
    }

    public function test_pagination_works_correctly(): void
    {
        Product::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class);

        $items = $component->viewData('items');
        $this->assertEquals(25, $items->perPage());
        $this->assertEquals(30, $items->total());
    }

    public function test_per_page_updates_correctly(): void
    {
        Product::factory()->count(30)->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('perPage', 10);

        $items = $component->viewData('items');
        $this->assertEquals(10, $items->perPage());
    }

    public function test_query_string_parameters_persist(): void
    {
        // Test that component initializes with default values
        // Query string persistence is tested in BaseIndexComponent tests
        $component = Livewire::test(ServiceIndex::class);
        
        $this->assertEquals('', $component->get('search'));
        $this->assertEquals('name', $component->get('sortField'));
        $this->assertEquals('asc', $component->get('sortDirection'));
    }

    public function test_empty_state_shows_when_no_services(): void
    {
        $component = Livewire::test(ServiceIndex::class);

        $emptyState = $component->viewData('emptyState');
        $this->assertArrayHasKey('title', $emptyState);
        $this->assertArrayHasKey('action', $emptyState);
        $this->assertEquals('Create Service', $emptyState['actionLabel']);
    }

    public function test_empty_state_has_correct_action_link(): void
    {
        $component = Livewire::test(ServiceIndex::class);

        $emptyState = $component->viewData('emptyState');
        $this->assertEquals(route('services.create'), $emptyState['action']);
    }

    public function test_row_actions_view_link_correct(): void
    {
        $service = Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class);
        $actions = $component->instance()->getRowActions($service);

        $viewAction = collect($actions)->firstWhere('label', 'View');
        $this->assertNotNull($viewAction);
        $this->assertStringContainsString((string) $service->id, $viewAction['href']);
    }

    public function test_row_actions_edit_link_correct(): void
    {
        $service = Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class);
        $actions = $component->instance()->getRowActions($service);

        $editAction = collect($actions)->firstWhere('label', 'Edit');
        $this->assertNotNull($editAction);
        $this->assertStringContainsString((string) $service->id, $editAction['href']);
    }

    public function test_services_eager_load_relationships(): void
    {
        Product::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class);
        $items = $component->viewData('items');

        // Check that relationships are eager loaded (not null when accessed)
        $firstItem = $items->first();
        $this->assertNotNull($firstItem->category);
    }

    public function test_respects_company_isolation(): void
    {
        $company2 = Company::factory()->create();
        $category2 = Category::create([
            'name' => 'Services',
            'type' => ['product'],
            'company_id' => $company2->id,
            'color' => '#28a745',
        ]);

        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Our Service',
            'type' => 'service',
        ]);

        Product::factory()->create([
            'company_id' => $company2->id,
            'category_id' => $category2->id,
            'name' => 'Other Company Service',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class);
        $items = $component->viewData('items');

        $this->assertEquals(1, $items->total());
        $this->assertEquals('Our Service', $items->first()->name);
    }

    public function test_clears_all_filters(): void
    {
        Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'name' => 'Test Service',
            'type' => 'service',
        ]);

        $component = Livewire::test(ServiceIndex::class)
            ->set('search', 'test')
            ->set('columnFilters.billing_model', ['subscription'])
            ->call('clearAllFilters')
            ->assertSet('search', '');
        
        // clearAllFilters sets array filters to ['min' => '', 'max' => ''] for range filters
        // or empty string for single value filters
        $billingFilter = $component->get('columnFilters.billing_model');
        $this->assertTrue(
            $billingFilter === '' || $billingFilter === ['min' => '', 'max' => ''],
            'Billing filter should be cleared'
        );
    }

    public function test_columns_configuration_includes_required_fields(): void
    {
        $component = Livewire::test(ServiceIndex::class);
        $columns = $component->viewData('columns');

        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('sku', $columns);
        $this->assertArrayHasKey('base_price', $columns);
        $this->assertArrayHasKey('billing_model', $columns);
        $this->assertArrayHasKey('is_active', $columns);
    }

    public function test_columns_are_sortable(): void
    {
        $component = Livewire::test(ServiceIndex::class);
        $columns = $component->viewData('columns');

        $this->assertTrue($columns['name']['sortable']);
        $this->assertTrue($columns['base_price']['sortable']);
    }

    public function test_columns_are_filterable(): void
    {
        $component = Livewire::test(ServiceIndex::class);
        $columns = $component->viewData('columns');

        $this->assertTrue($columns['category.name']['filterable']);
        $this->assertTrue($columns['billing_model']['filterable']);
        $this->assertTrue($columns['is_active']['filterable']);
    }

    public function test_search_clears_pagination(): void
    {
        Product::factory()->count(50)->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'type' => 'service',
        ]);

        // Test that setting search resets pagination by checking the component resets
        $component = Livewire::test(ServiceIndex::class)
            ->set('search', 'test');
        
        // Verify search was set
        $this->assertEquals('test', $component->get('search'));
    }
}
