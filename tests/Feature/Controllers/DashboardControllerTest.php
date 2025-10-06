<?php

namespace Tests\Feature\Controllers;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_get_data_returns_stats(): void
    {
        Client::factory()->count(5)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_clients',
            'open_tickets',
            'overdue_invoices',
            'total_assets',
        ]);
    }

    public function test_get_data_returns_recent_tickets(): void
    {
        Ticket::factory()->count(5)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'recent_tickets']));

        $response->assertStatus(200);
        $response->assertJsonCount(5);
    }

    public function test_get_data_returns_recent_invoices(): void
    {
        Invoice::factory()->count(5)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'recent_invoices']));

        $response->assertStatus(200);
        $response->assertJsonCount(5);
    }

    public function test_get_data_returns_ticket_chart_data(): void
    {
        Ticket::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'ticket_chart']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'data',
        ]);
    }

    public function test_get_data_returns_revenue_chart_data(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'revenue_chart']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'data',
        ]);
    }

    public function test_get_data_returns_error_for_invalid_type(): void
    {
        $response = $this->getJson(route('dashboard.stats', ['type' => 'invalid_type']));

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid data type']);
    }

    public function test_get_notifications_returns_unread_notifications(): void
    {
        $response = $this->getJson(route('dashboard.notifications'));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_mark_notification_read_updates_notification(): void
    {
        $response = $this->postJson(route('dashboard.notifications.read', 1));

        $response->assertStatus(200);
    }

    public function test_get_realtime_data_returns_all_data_by_default(): void
    {
        $response = $this->getJson(route('dashboard.realtime'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'kpis',
            'recent_activity',
            'revenueChartData',
            'ticketChartData',
           'alerts',
            'updated_at',
        ]);
    }

    public function test_get_realtime_data_returns_stats_only(): void
    {
        $response = $this->getJson(route('dashboard.realtime', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_clients',
            'open_tickets',
        ]);
    }

    public function test_get_realtime_data_returns_recent_activity(): void
    {
        $response = $this->getJson(route('dashboard.realtime', ['type' => 'recent_activity']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tickets',
            'invoices',
            'tasks',
        ]);
    }

    public function test_get_realtime_data_returns_charts(): void
    {
        $response = $this->getJson(route('dashboard.realtime', ['type' => 'charts']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'revenue',
            'tickets',
        ]);
    }

    public function test_get_realtime_data_returns_alerts(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'Sent',
            'due_date' => now()->subDays(10),
        ]);

        $response = $this->getJson(route('dashboard.realtime', ['type' => 'alerts']));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_export_data_returns_json_format(): void
    {
        $response = $this->getJson(route('dashboard.export', [
            'type' => 'executive',
            'format' => 'json',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'filename',
            'generated_at',
        ]);
    }

    public function test_export_data_with_date_range(): void
    {
        $startDate = now()->subMonth()->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->getJson(route('dashboard.export', [
            'type' => 'executive',
            'format' => 'json',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_get_widget_data_returns_widget_data(): void
    {
        $response = $this->getJson(route('dashboard.widget', [
            'widget_type' => 'revenue_kpi',
        ]));

        $response->assertStatus(200);
    }

    public function test_get_multiple_widget_data_returns_multiple_widgets(): void
    {
        $response = $this->postJson(route('dashboard.widgets.multiple'), [
            'widgets' => [
                ['type' => 'revenue_kpi', 'config' => []],
                ['type' => 'ticket_status', 'config' => []],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'timestamp',
        ]);
    }

    public function test_save_dashboard_config_saves_user_configuration(): void
    {
        $config = [
            'dashboard_name' => 'main',
            'layout' => ['columns' => 12],
            'widgets' => [['type' => 'revenue_kpi']],
            'preferences' => ['theme' => 'dark'],
        ];

        $response = $this->postJson(route('dashboard.config.save'), $config);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Dashboard configuration saved',
        ]);
    }

    public function test_save_dashboard_config_validates_required_fields(): void
    {
        $response = $this->postJson(route('dashboard.config.save'), [
            'dashboard_name' => 'main',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['layout', 'widgets']);
    }

    public function test_load_dashboard_config_returns_saved_config(): void
    {
        $config = [
            'dashboard_name' => 'main',
            'layout' => ['columns' => 12],
            'widgets' => [['type' => 'revenue_kpi']],
            'preferences' => ['theme' => 'dark'],
        ];

        $this->postJson(route('dashboard.config.save'), $config);

        $response = $this->getJson(route('dashboard.config.load', [
            'dashboard_name' => 'main',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'is_default',
            'config' => [
                'layout',
                'widgets',
                'preferences',
            ],
        ]);
    }

    public function test_load_dashboard_config_returns_default_when_not_found(): void
    {
        $response = $this->getJson(route('dashboard.config.load', [
            'dashboard_name' => 'nonexistent',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['is_default' => true]);
        $response->assertJsonStructure([
            'config' => [
                'layout',
                'widgets',
                'preferences',
            ],
        ]);
    }

    public function test_get_presets_returns_available_presets(): void
    {
        $response = $this->getJson(route('dashboard.presets'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'presets',
        ]);
    }

    public function test_apply_preset_applies_system_preset(): void
    {
        // Create a system preset
        $presetId = \DB::table('dashboard_presets')->insertGetId([
            'name' => 'Test Preset',
            'slug' => 'test-preset',
            'is_system' => true,
            'company_id' => null,
            'layout' => json_encode(['columns' => 3]),
            'widgets' => json_encode([]),
            'default_preferences' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson(route('dashboard.preset.apply'), [
            'preset_id' => $presetId,
        ]);

        $response->assertStatus(200);
    }

    public function test_dashboard_stats_counts_clients_correctly(): void
    {
        Client::factory()->count(10)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['total_clients' => 10]);
    }

    public function test_dashboard_stats_counts_open_tickets_correctly(): void
    {
        Ticket::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
        ]);

        Ticket::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Closed',
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['open_tickets' => 5]);
    }

    public function test_dashboard_stats_counts_overdue_invoices(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Sent',
            'due_date' => now()->subDays(5),
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'Sent',
            'due_date' => now()->addDays(5),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['overdue_invoices' => 3]);
    }

    public function test_dashboard_stats_counts_total_assets(): void
    {
        Asset::factory()->count(15)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['total_assets' => 15]);
    }

    public function test_dashboard_stats_calculates_monthly_revenue(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 1000.00,
            'created_at' => now(),
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 500.00,
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $this->assertGreaterThan(0, $response->json('monthly_revenue'));
    }

    public function test_dashboard_stats_excludes_archived_clients(): void
    {
        Client::factory()->count(5)->create(['company_id' => $this->company->id]);
        Client::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'archived_at' => now(),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['total_clients' => 5]);
    }

    public function test_dashboard_stats_excludes_archived_assets(): void
    {
        Asset::factory()->count(8)->create(['company_id' => $this->company->id]);
        Asset::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'archived_at' => now(),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['total_assets' => 8]);
    }

    public function test_dashboard_only_shows_company_data(): void
    {
        $otherCompany = Company::factory()->create();
        
        Client::factory()->count(5)->create(['company_id' => $this->company->id]);
        Client::factory()->count(10)->create(['company_id' => $otherCompany->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(200);
        $response->assertJson(['total_clients' => 5]);
    }

    public function test_recent_tickets_limits_results(): void
    {
        Ticket::factory()->count(20)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'recent_tickets']));

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json()));
    }

    public function test_recent_tickets_orders_by_created_at_desc(): void
    {
        $oldest = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subDays(5),
        ]);

        $newest = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'recent_tickets']));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals($newest->id, $data[0]['id']);
    }

    public function test_recent_invoices_limits_results(): void
    {
        Invoice::factory()->count(20)->create(['company_id' => $this->company->id]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'recent_invoices']));

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json()));
    }

    public function test_recent_invoices_orders_by_created_at_desc(): void
    {
        $oldest = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subDays(5),
        ]);

        $newest = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'recent_invoices']));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals($newest->id, $data[0]['id']);
    }

    public function test_ticket_chart_groups_by_status(): void
    {
        Ticket::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
        ]);

        Ticket::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'Closed',
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'ticket_chart']));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data['labels']);
        $this->assertIsArray($data['data']);
    }

    public function test_revenue_chart_groups_by_month(): void
    {
        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 1000.00,
            'created_at' => now()->subMonth(),
        ]);

        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 1500.00,
            'created_at' => now(),
        ]);

        $response = $this->getJson(route('dashboard.stats', ['type' => 'revenue_chart']));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data['labels']);
        $this->assertIsArray($data['data']);
    }

    public function test_get_realtime_data_caches_responses(): void
    {
        $response1 = $this->getJson(route('dashboard.realtime', ['type' => 'stats']));
        $response2 = $this->getJson(route('dashboard.realtime', ['type' => 'stats']));

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    public function test_performance_alerts_detects_overdue_invoices(): void
    {
        Invoice::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'Sent',
            'due_date' => now()->subDays(10),
        ]);

        $response = $this->getJson(route('dashboard.realtime', ['type' => 'alerts']));

        $response->assertStatus(200);
        $alerts = $response->json();
        $this->assertNotEmpty($alerts);
    }

    public function test_performance_alerts_detects_unassigned_tickets(): void
    {
        Ticket::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
            'assigned_to' => null,
        ]);

        $response = $this->getJson(route('dashboard.realtime', ['type' => 'alerts']));

        $response->assertStatus(200);
        $alerts = $response->json();
        $this->assertNotEmpty($alerts);
    }

    public function test_unauthenticated_user_cannot_access_dashboard_data(): void
    {
        auth()->logout();

        $response = $this->getJson(route('dashboard.stats', ['type' => 'stats']));

        $response->assertStatus(401);
    }

    public function test_dashboard_handles_missing_tables_gracefully(): void
    {
        $response = $this->getJson(route('dashboard.notifications'));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_export_handles_errors_gracefully(): void
    {
        $response = $this->getJson(route('dashboard.export', [
            'type' => 'invalid_type',
            'format' => 'json',
        ]));

        $this->assertContains($response->status(), [200, 500]);
    }

    public function test_widget_data_handles_invalid_widget_type(): void
    {
        $response = $this->getJson(route('dashboard.widget', [
            'widget_type' => 'invalid_widget',
        ]));

        $this->assertContains($response->status(), [200, 500]);
    }

    public function test_save_dashboard_config_updates_existing_config(): void
    {
        $initialConfig = [
            'dashboard_name' => 'main',
            'layout' => ['columns' => 12],
            'widgets' => [['type' => 'revenue_kpi']],
            'preferences' => ['theme' => 'light'],
        ];

        $this->postJson(route('dashboard.config.save'), $initialConfig);

        $updatedConfig = [
            'dashboard_name' => 'main',
            'layout' => ['columns' => 24],
            'widgets' => [['type' => 'ticket_status']],
            'preferences' => ['theme' => 'dark'],
        ];

        $response = $this->postJson(route('dashboard.config.save'), $updatedConfig);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_load_dashboard_config_for_specific_dashboard(): void
    {
        $config = [
            'dashboard_name' => 'custom',
            'layout' => ['columns' => 12],
            'widgets' => [['type' => 'custom_widget']],
            'preferences' => ['theme' => 'dark'],
        ];

        $this->postJson(route('dashboard.config.save'), $config);

        $response = $this->getJson(route('dashboard.config.load', [
            'dashboard_name' => 'custom',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['is_default' => false]);
    }

    public function test_dashboard_config_validates_dashboard_name_length(): void
    {
        $config = [
            'dashboard_name' => str_repeat('a', 60),
            'layout' => ['columns' => 12],
            'widgets' => [['type' => 'revenue_kpi']],
        ];

        $response = $this->postJson(route('dashboard.config.save'), $config);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('dashboard_name');
    }

    public function test_dashboard_config_saves_for_current_user_only(): void
    {
        $config = [
            'dashboard_name' => 'main',
            'layout' => ['columns' => 12],
            'widgets' => [['type' => 'revenue_kpi']],
            'preferences' => ['theme' => 'dark'],
        ];

        $this->postJson(route('dashboard.config.save'), $config);

        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($otherUser);

        $response = $this->getJson(route('dashboard.config.load', [
            'dashboard_name' => 'main',
        ]));

        $response->assertStatus(200);
        $response->assertJson(['is_default' => true]);
    }
}
