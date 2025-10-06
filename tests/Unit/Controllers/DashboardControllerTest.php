<?php

namespace Tests\Unit\Controllers;

use App\Domains\Core\Controllers\DashboardController;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardController $controller;
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $this->controller = new DashboardController();
    }

    public function test_get_user_context_returns_user_information(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUserContext');
        $method->setAccessible(true);

        $context = $method->invoke($this->controller, $this->user);

        $this->assertEquals($this->user->id, $context->id);
        $this->assertEquals($this->user->name, $context->name);
        $this->assertEquals($this->user->company_id, $context->company_id);
        $this->assertIsBool($context->isAdmin);
        $this->assertIsBool($context->isTech);
        $this->assertIsBool($context->isAccountant);
        $this->assertIsArray($context->permissions);
    }

    public function test_get_user_primary_role_returns_admin_for_admin_user(): void
    {
        $adminUser = User::factory()->create(['company_id' => $this->company->id]);
        $adminUser->assign('admin');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUserPrimaryRole');
        $method->setAccessible(true);

        $role = $method->invoke($this->controller, $adminUser);

        $this->assertEquals('admin', $role);
    }

    public function test_get_user_primary_role_returns_user_for_standard_user(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUserPrimaryRole');
        $method->setAccessible(true);

        $role = $method->invoke($this->controller, $this->user);

        $this->assertEquals('user', $role);
    }

    public function test_get_dashboard_stats_returns_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDashboardStats');
        $method->setAccessible(true);

        $stats = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_clients', $stats);
        $this->assertArrayHasKey('open_tickets', $stats);
        $this->assertArrayHasKey('overdue_invoices', $stats);
        $this->assertArrayHasKey('total_assets', $stats);
        $this->assertArrayHasKey('monthly_revenue', $stats);
        $this->assertArrayHasKey('pending_invoices_amount', $stats);
    }

    public function test_get_dashboard_stats_counts_clients_correctly(): void
    {
        Client::factory()->count(5)->create(['company_id' => $this->company->id]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDashboardStats');
        $method->setAccessible(true);

        $stats = $method->invoke($this->controller, $this->company->id);

        $this->assertEquals(5, $stats['total_clients']);
    }

    public function test_get_dashboard_stats_excludes_archived_clients(): void
    {
        Client::factory()->count(3)->create(['company_id' => $this->company->id]);
        Client::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'archived_at' => now(),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDashboardStats');
        $method->setAccessible(true);

        $stats = $method->invoke($this->controller, $this->company->id);

        $this->assertEquals(3, $stats['total_clients']);
    }

    public function test_get_recent_tickets_limits_to_specified_amount(): void
    {
        Ticket::factory()->count(20)->create(['company_id' => $this->company->id]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRecentTickets');
        $method->setAccessible(true);

        $tickets = $method->invoke($this->controller, $this->company->id, 5);

        $this->assertCount(5, $tickets);
    }

    public function test_get_recent_tickets_orders_by_created_at_desc(): void
    {
        $oldest = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subDays(5),
        ]);

        $newest = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now(),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRecentTickets');
        $method->setAccessible(true);

        $tickets = $method->invoke($this->controller, $this->company->id, 10);

        $this->assertEquals($newest->id, $tickets->first()->id);
    }

    public function test_get_recent_invoices_limits_to_specified_amount(): void
    {
        Invoice::factory()->count(20)->create(['company_id' => $this->company->id]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRecentInvoices');
        $method->setAccessible(true);

        $invoices = $method->invoke($this->controller, $this->company->id, 5);

        $this->assertCount(5, $invoices);
    }

    public function test_get_recent_invoices_orders_by_created_at_desc(): void
    {
        $oldest = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subDays(5),
        ]);

        $newest = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now(),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRecentInvoices');
        $method->setAccessible(true);

        $invoices = $method->invoke($this->controller, $this->company->id, 10);

        $this->assertEquals($newest->id, $invoices->first()->id);
    }

    public function test_get_ticket_chart_data_returns_correct_structure(): void
    {
        Ticket::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTicketChartData');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertIsArray($chartData['labels']);
        $this->assertIsArray($chartData['data']);
    }

    public function test_get_ticket_chart_data_groups_statuses_correctly(): void
    {
        Ticket::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
        ]);

        Ticket::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'In Progress',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTicketChartData');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id);

        $this->assertContains('Open', $chartData['labels']);
        $this->assertContains('In Progress', $chartData['labels']);
    }

    public function test_get_revenue_chart_data_returns_correct_structure(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 1000.00,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRevenueChartData');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
    }

    public function test_get_revenue_chart_data_includes_only_paid_invoices(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 1000.00,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'Draft',
            'amount' => 500.00,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRevenueChartData');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($chartData['data']);
    }

    public function test_get_performance_alerts_detects_overdue_invoices(): void
    {
        Invoice::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'Sent',
            'due_date' => now()->subDays(10),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getPerformanceAlerts');
        $method->setAccessible(true);

        $alerts = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($alerts);
        $this->assertNotEmpty($alerts);
        $this->assertEquals('warning', $alerts[0]['type']);
    }

    public function test_get_performance_alerts_detects_unassigned_tickets(): void
    {
        Ticket::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Open',
            'assigned_to' => null,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getPerformanceAlerts');
        $method->setAccessible(true);

        $alerts = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($alerts);
        $this->assertNotEmpty($alerts);
    }

    public function test_get_performance_alerts_returns_empty_when_no_issues(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getPerformanceAlerts');
        $method->setAccessible(true);

        $alerts = $method->invoke($this->controller, $this->company->id);

        $this->assertIsArray($alerts);
        $this->assertEmpty($alerts);
    }

    public function test_get_default_dashboard_config_returns_valid_structure(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDefaultDashboardConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->controller);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('layout', $config);
        $this->assertArrayHasKey('widgets', $config);
        $this->assertArrayHasKey('preferences', $config);
    }

    public function test_get_default_dashboard_config_includes_layout_settings(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDefaultDashboardConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->controller);

        $this->assertArrayHasKey('columns', $config['layout']);
        $this->assertArrayHasKey('rows', $config['layout']);
        $this->assertArrayHasKey('gap', $config['layout']);
    }

    public function test_get_default_dashboard_config_includes_widgets(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDefaultDashboardConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->controller);

        $this->assertIsArray($config['widgets']);
        $this->assertNotEmpty($config['widgets']);
        $this->assertArrayHasKey('type', $config['widgets'][0]);
    }

    public function test_user_can_access_action_allows_admin_all_actions(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('userCanAccessAction');
        $method->setAccessible(true);

        $canAccess = $method->invoke($this->controller, 'tickets.create', 'admin');

        $this->assertTrue($canAccess);
    }

    public function test_user_can_access_action_restricts_accountant_from_client_creation(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('userCanAccessAction');
        $method->setAccessible(true);

        $canAccess = $method->invoke($this->controller, 'clients.create', 'accountant');

        $this->assertFalse($canAccess);
    }

    public function test_user_can_access_action_restricts_tech_from_financial_operations(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('userCanAccessAction');
        $method->setAccessible(true);

        $canAccess = $method->invoke($this->controller, 'financial.invoices.create', 'tech');

        $this->assertFalse($canAccess);
    }

    public function test_get_ticket_priority_chart_groups_by_priority(): void
    {
        Ticket::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'priority' => 'High',
        ]);

        Ticket::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'priority' => 'Low',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTicketPriorityChart');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id, null);

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
    }

    public function test_get_payment_status_chart_groups_by_status(): void
    {
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'Paid',
            'amount' => 1000.00,
        ]);

        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'Sent',
            'amount' => 500.00,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getPaymentStatusChart');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id, null);

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
    }

    public function test_get_daily_activity_chart_groups_by_hour(): void
    {
        Ticket::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_at' => now(),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDailyActivityChart');
        $method->setAccessible(true);

        $chartData = $method->invoke($this->controller, $this->company->id, null);

        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertCount(24, $chartData['labels']);
    }

    public function test_get_admin_kpis_returns_admin_specific_kpis(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getAdminKPIs');
        $method->setAccessible(true);

        $kpis = $method->invoke($this->controller, 'default', null);

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_revenue', $kpis);
        $this->assertArrayHasKey('active_clients', $kpis);
        $this->assertArrayHasKey('open_tickets', $kpis);
        $this->assertArrayHasKey('monthly_revenue', $kpis);
    }

    public function test_get_tech_kpis_returns_tech_specific_kpis(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getTechKPIs');
        $method->setAccessible(true);

        $kpis = $method->invoke($this->controller, 'default', null);

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('my_open_tickets', $kpis);
        $this->assertArrayHasKey('resolved_today', $kpis);
        $this->assertArrayHasKey('avg_response_time', $kpis);
        $this->assertArrayHasKey('total_assets', $kpis);
    }

    public function test_get_accountant_kpis_returns_accountant_specific_kpis(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getAccountantKPIs');
        $method->setAccessible(true);

        $kpis = $method->invoke($this->controller, 'default', null);

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('outstanding_invoices', $kpis);
        $this->assertArrayHasKey('payments_this_month', $kpis);
        $this->assertArrayHasKey('overdue_amount', $kpis);
        $this->assertArrayHasKey('collection_rate', $kpis);
    }

    public function test_get_basic_kpis_returns_standard_kpis(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getBasicKPIs');
        $method->setAccessible(true);

        $kpis = $method->invoke($this->controller, 'default', null);

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('open_tickets', $kpis);
        $this->assertArrayHasKey('recent_activity', $kpis);
    }

    public function test_kpis_include_required_structure(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getAdminKPIs');
        $method->setAccessible(true);

        $kpis = $method->invoke($this->controller, 'default', null);
        $firstKpi = reset($kpis);

        $this->assertArrayHasKey('label', $firstKpi);
        $this->assertArrayHasKey('value', $firstKpi);
        $this->assertArrayHasKey('format', $firstKpi);
        $this->assertArrayHasKey('icon', $firstKpi);
        $this->assertArrayHasKey('trend', $firstKpi);
    }
}
