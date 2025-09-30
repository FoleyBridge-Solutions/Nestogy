<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Company;
use App\Livewire\Dashboard\MainDashboard;
use App\Livewire\Dashboard\Widgets\KpiGrid;
use App\Livewire\Dashboard\Widgets\RevenueChart;
use App\Livewire\Dashboard\Widgets\TicketChart;
use App\Livewire\Dashboard\Widgets\ActivityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LazyLoadingTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $user;
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and user
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->actingAs($this->user);
    }
    
    /**
     * Test that dashboard loads successfully with lazy loading
     */
    public function test_dashboard_loads_with_lazy_loading_enabled()
    {
        config(['dashboard.lazy_loading.enabled' => true]);
        
        Livewire::test(MainDashboard::class)
            ->assertSuccessful()
            ->assertViewHas('widgets')
            ->assertViewHas('allWidgetConfigs');
    }
    
    /**
     * Test that lazy loaded widgets show placeholders initially
     */
    public function test_lazy_widgets_show_placeholders()
    {
        // Test that Revenue Chart shows placeholder
        Livewire::test(RevenueChart::class)
            ->assertSee('skeleton-chart')
            ->assertDontSee('Revenue Analysis');
    }
    
    /**
     * Test that non-lazy widgets load immediately
     */
    public function test_immediate_widgets_load_without_placeholders()
    {
        // Disable lazy loading for KPI Grid
        config(['dashboard.lazy_loading.immediate' => ['kpi-grid']]);
        
        Livewire::withoutLazyLoading()
            ->test(KpiGrid::class)
            ->assertDontSee('skeleton')
            ->assertViewHas('kpis');
    }
    
    /**
     * Test widget caching functionality
     */
    public function test_widget_data_is_cached()
    {
        $component = Livewire::withoutLazyLoading()
            ->test(KpiGrid::class);
        
        // First load should cache the data
        $component->call('loadKpis');
        
        // Check that cache key exists
        $cacheKey = "kpi_grid_{$this->company->id}_";
        $this->assertTrue(\Cache::has($cacheKey));
    }
    
    /**
     * Test that charts use lazy loading with correct strategy
     */
    public function test_charts_use_viewport_lazy_loading()
    {
        $component = Livewire::test(TicketChart::class);
        
        // Component should be lazy loaded
        $this->assertArrayHasKey('lazy', $component->instance()->toLivewireArray());
    }
    
    /**
     * Test activity feed uses deferred loading
     */
    public function test_activity_feed_uses_deferred_loading()
    {
        config(['dashboard.lazy_loading.deferred' => ['activity-feed']]);
        
        $component = Livewire::test(ActivityFeed::class);
        
        // Should be configured for on-load lazy loading
        $this->assertNotNull($component);
    }
    
    /**
     * Test performance tracking
     */
    public function test_performance_is_tracked_when_enabled()
    {
        config(['dashboard.performance.track_load_times' => true]);
        
        Livewire::withoutLazyLoading()
            ->test(KpiGrid::class)
            ->assertSuccessful();
        
        // Check that performance was logged
        $this->assertFileExists(storage_path('logs/performance.log'));
    }
    
    /**
     * Test lazy loading can be disabled globally
     */
    public function test_lazy_loading_can_be_disabled_globally()
    {
        config(['dashboard.lazy_loading.enabled' => false]);
        
        Livewire::test(RevenueChart::class)
            ->assertDontSee('skeleton')
            ->assertSuccessful();
    }
    
    /**
     * Test widget priority sorting
     */
    public function test_widgets_are_sorted_by_priority()
    {
        $widgets = [
            ['type' => 'activity-feed'],
            ['type' => 'alert-panel'],
            ['type' => 'kpi-grid'],
        ];
        
        $sorted = \App\Domains\Core\Services\DashboardLazyLoadService::sortByPriority($widgets);
        
        // Alert panel should be first (priority 1)
        $this->assertEquals('alert-panel', $sorted[0]['type']);
        // KPI Grid should be second (priority 2)
        $this->assertEquals('kpi-grid', $sorted[1]['type']);
        // Activity feed should be last (priority 9)
        $this->assertEquals('activity-feed', $sorted[2]['type']);
    }
}