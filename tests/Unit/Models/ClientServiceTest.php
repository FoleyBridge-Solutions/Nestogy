<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientService;
use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Recurring;
use App\Domains\Product\Models\Product;

class ClientServiceTest extends ModelTestCase
{
    public function test_can_create_client_service_with_factory(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertInstanceOf(ClientService::class, $service);
        $this->assertDatabaseHas('client_services', ['id' => $service->id]);
    }

    public function test_client_service_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertInstanceOf(Company::class, $service->company);
        $this->assertEquals($company->id, $service->company->id);
    }

    public function test_client_service_belongs_to_client(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertInstanceOf(Client::class, $service->client);
        $this->assertEquals($client->id, $service->client->id);
    }

    public function test_client_service_belongs_to_technician(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $technician = User::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'assigned_technician' => $technician->id,
        ]);

        $this->assertInstanceOf(User::class, $service->technician);
        $this->assertEquals($technician->id, $service->technician->id);
    }

    public function test_client_service_has_backup_technician(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $backup = User::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'backup_technician' => $backup->id,
        ]);

        $this->assertInstanceOf(User::class, $service->backupTechnician);
        $this->assertEquals($backup->id, $service->backupTechnician->id);
    }

    public function test_client_service_belongs_to_contract(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $contract = Contract::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'contract_id' => $contract->id,
        ]);

        $this->assertInstanceOf(Contract::class, $service->contract);
        $this->assertEquals($contract->id, $service->contract->id);
    }

    public function test_client_service_belongs_to_product(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Product::class, $service->product);
        $this->assertEquals($product->id, $service->product->id);
    }

    public function test_client_service_has_fillable_attributes(): void
    {
        $fillable = (new ClientService)->getFillable();

        $expectedFillable = [
            'company_id', 'client_id', 'contract_id', 'product_id',
            'name', 'description', 'service_type', 'category', 'status',
            'monthly_cost', 'setup_cost', 'billing_cycle',
        ];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_client_service_can_be_soft_deleted(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $serviceId = $service->id;
        $service->delete();

        $this->assertSoftDeleted('client_services', ['id' => $serviceId]);
    }

    public function test_scope_of_type_filters_by_service_type(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $managedService = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'service_type' => 'managed_services',
        ]);

        $cloudService = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'service_type' => 'cloud_services',
        ]);

        $results = ClientService::ofType('managed_services')->get();

        $this->assertTrue($results->contains($managedService));
        $this->assertFalse($results->contains($cloudService));
    }

    public function test_scope_in_category_filters_by_category(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $infrastructure = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category' => 'infrastructure',
        ]);

        $security = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'category' => 'security',
        ]);

        $results = ClientService::inCategory('infrastructure')->get();

        $this->assertTrue($results->contains($infrastructure));
        $this->assertFalse($results->contains($security));
    }

    public function test_scope_active_returns_only_active_services(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $active = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $pending = ClientService::factory()->pending()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $results = ClientService::active()->get();

        $this->assertTrue($results->contains($active));
        $this->assertFalse($results->contains($pending));
    }

    public function test_scope_with_status_filters_by_given_status(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $suspended = ClientService::factory()->suspended()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $active = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $results = ClientService::withStatus('suspended')->get();

        $this->assertTrue($results->contains($suspended));
        $this->assertFalse($results->contains($active));
    }

    public function test_scope_ending_soon_returns_services_ending_within_days(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $endingSoon = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'end_date' => now()->addDays(15),
        ]);

        $endingLater = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'end_date' => now()->addDays(60),
        ]);

        $results = ClientService::endingSoon(30)->get();

        $this->assertTrue($results->contains($endingSoon));
        $this->assertFalse($results->contains($endingLater));
    }

    public function test_scope_due_for_renewal_returns_services_renewing_soon(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $dueForRenewal = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'renewal_date' => now()->addDays(20),
        ]);

        $renewingLater = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'renewal_date' => now()->addDays(60),
        ]);

        $results = ClientService::dueForRenewal(30)->get();

        $this->assertTrue($results->contains($dueForRenewal));
        $this->assertFalse($results->contains($renewingLater));
    }

    public function test_scope_needing_review_returns_services_requiring_review(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $needsReview = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'next_review_date' => now()->subDays(10),
        ]);

        $reviewedRecently = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'next_review_date' => now()->addDays(30),
        ]);

        $results = ClientService::needingReview()->get();

        $this->assertTrue($results->contains($needsReview));
        $this->assertFalse($results->contains($reviewedRecently));
    }

    public function test_scope_monitored_returns_only_monitored_services(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $monitored = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monitoring_enabled' => true,
        ]);

        $notMonitored = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monitoring_enabled' => false,
        ]);

        $results = ClientService::monitored()->get();

        $this->assertTrue($results->contains($monitored));
        $this->assertFalse($results->contains($notMonitored));
    }

    public function test_is_ending_soon_returns_true_for_services_ending_within_days(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'end_date' => now()->addDays(20),
        ]);

        $this->assertTrue($service->isEndingSoon(30));
        $this->assertFalse($service->isEndingSoon(10));
    }

    public function test_is_due_for_renewal_returns_true_for_services_renewing_soon(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'renewal_date' => now()->addDays(20),
        ]);

        $this->assertTrue($service->isDueForRenewal(30));
        $this->assertFalse($service->isDueForRenewal(10));
    }

    public function test_needs_review_returns_true_when_review_overdue(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'next_review_date' => now()->subDays(10),
        ]);

        $this->assertTrue($service->needsReview());
    }

    public function test_needs_review_returns_true_when_no_review_date_set(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'next_review_date' => null,
        ]);

        $this->assertTrue($service->needsReview());
    }

    public function test_get_status_color_attribute_returns_correct_colors(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $active = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $pending = ClientService::factory()->pending()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $suspended = ClientService::factory()->suspended()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertEquals('success', $active->status_color);
        $this->assertEquals('warning', $pending->status_color);
        $this->assertEquals('danger', $suspended->status_color);
    }

    public function test_get_status_label_attribute_returns_correct_labels(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $active = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertEquals('Active', $active->status_label);
    }

    public function test_get_annual_revenue_attribute_calculates_correctly_for_monthly(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monthly_cost' => 1000.00,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertEquals(12000.00, $service->annual_revenue);
    }

    public function test_get_annual_revenue_attribute_calculates_correctly_for_quarterly(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monthly_cost' => 1000.00,
            'billing_cycle' => 'quarterly',
        ]);

        $this->assertEquals(4000.00, $service->annual_revenue);
    }

    public function test_get_annual_revenue_attribute_calculates_correctly_for_annually(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monthly_cost' => 1000.00,
            'billing_cycle' => 'annually',
        ]);

        $this->assertEquals(1000.00, $service->annual_revenue);
    }

    public function test_get_remaining_value_attribute_calculates_correctly(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monthly_cost' => 1000.00,
            'end_date' => now()->addMonths(6),
        ]);

        $remainingValue = $service->remaining_value;
        
        $this->assertGreaterThan(5000, $remainingValue);
        $this->assertLessThan(7000, $remainingValue);
    }

    public function test_get_remaining_value_attribute_returns_zero_for_past_end_date(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monthly_cost' => 1000.00,
            'end_date' => now()->subMonths(1),
        ]);

        $this->assertEquals(0, $service->remaining_value);
    }

    public function test_is_active_returns_true_for_active_services(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertTrue($service->isActive());
    }

    public function test_is_active_returns_false_for_pending_services(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->pending()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertFalse($service->isActive());
    }

    public function test_is_suspended_returns_true_for_suspended_services(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->suspended()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertTrue($service->isSuspended());
    }

    public function test_is_cancelled_returns_true_for_cancelled_services(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->cancelled()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertTrue($service->isCancelled());
    }

    public function test_is_provisioned_returns_true_when_provisioned_at_set(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'provisioned_at' => now(),
        ]);

        $this->assertTrue($service->isProvisioned());
    }

    public function test_is_activated_returns_true_when_activated_at_set(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertTrue($service->isActivated());
    }

    public function test_has_recurring_billing_returns_true_when_billing_id_set(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        // Create actual recurring billing record
        $recurring = \App\Domains\Financial\Models\Recurring::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'recurring_billing_id' => $recurring->id,
        ]);

        $this->assertTrue($service->hasRecurringBilling());
    }

    public function test_has_recurring_billing_returns_false_when_no_billing_id(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'recurring_billing_id' => null,
        ]);

        $this->assertFalse($service->hasRecurringBilling());
    }

    public function test_get_lifecycle_stage_returns_correct_stage_for_cancelled(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->cancelled()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertEquals('cancelled', $service->getLifecycleStage());
    }

    public function test_get_lifecycle_stage_returns_correct_stage_for_suspended(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->suspended()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertEquals('suspended', $service->getLifecycleStage());
    }

    public function test_get_lifecycle_stage_returns_correct_stage_for_active(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->active()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertEquals('active', $service->getLifecycleStage());
    }

    public function test_get_lifecycle_stage_returns_correct_stage_for_pending(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->pending()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertEquals('pending', $service->getLifecycleStage());
    }

    public function test_monetary_fields_cast_to_decimal(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'monthly_cost' => 1234.56,
            'setup_cost' => 500.00,
        ]);

        $this->assertEquals('1234.56', $service->monthly_cost);
        $this->assertEquals('500.00', $service->setup_cost);
    }

    public function test_date_fields_cast_to_carbon(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $service->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $service->created_at);
    }

    public function test_boolean_fields_cast_to_boolean(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'auto_renewal' => true,
            'monitoring_enabled' => false,
        ]);

        $this->assertIsBool($service->auto_renewal);
        $this->assertIsBool($service->monitoring_enabled);
        $this->assertTrue($service->auto_renewal);
        $this->assertFalse($service->monitoring_enabled);
    }

    public function test_array_fields_cast_to_array(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        
        $tags = ['vip', 'priority'];
        $service = ClientService::factory()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'tags' => $tags,
        ]);

        $this->assertIsArray($service->tags);
        $this->assertEquals($tags, $service->tags);
    }

    public function test_get_service_types_returns_array(): void
    {
        $types = ClientService::getServiceTypes();
        
        $this->assertIsArray($types);
        $this->assertArrayHasKey('managed_services', $types);
        $this->assertArrayHasKey('cloud_services', $types);
    }

    public function test_get_service_categories_returns_array(): void
    {
        $categories = ClientService::getServiceCategories();
        
        $this->assertIsArray($categories);
        $this->assertArrayHasKey('infrastructure', $categories);
        $this->assertArrayHasKey('security', $categories);
    }

    public function test_get_service_statuses_returns_array(): void
    {
        $statuses = ClientService::getServiceStatuses();
        
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('active', $statuses);
        $this->assertArrayHasKey('pending', $statuses);
        $this->assertArrayHasKey('suspended', $statuses);
    }

    public function test_get_billing_cycles_returns_array(): void
    {
        $cycles = ClientService::getBillingCycles();
        
        $this->assertIsArray($cycles);
        $this->assertArrayHasKey('monthly', $cycles);
        $this->assertArrayHasKey('annually', $cycles);
    }

    public function test_get_service_levels_returns_array(): void
    {
        $levels = ClientService::getServiceLevels();
        
        $this->assertIsArray($levels);
        $this->assertArrayHasKey('basic', $levels);
        $this->assertArrayHasKey('premium', $levels);
    }

    public function test_get_priority_levels_returns_array(): void
    {
        $priorities = ClientService::getPriorityLevels();
        
        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('low', $priorities);
        $this->assertArrayHasKey('critical', $priorities);
    }
}
