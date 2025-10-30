<?php

namespace Tests\Unit\Services;

use App\Domains\Client\Events\ServiceActivated;
use App\Domains\Client\Events\ServiceCancelled;
use App\Domains\Client\Events\ServiceProvisioned;
use App\Domains\Client\Events\ServiceRenewed;
use App\Domains\Client\Events\ServiceResumed;
use App\Domains\Client\Events\ServiceSuspended;
use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientService;
use App\Domains\Client\Services\ClientServiceManagementService;
use App\Domains\Client\Services\ServiceBillingService;
use App\Domains\Client\Services\ServiceProvisioningService;
use App\Domains\Client\Services\ServiceRenewalService;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ClientServiceManagementServiceTest extends TestCase
{
    use RefreshesDatabase;

    protected ClientServiceManagementService $service;
    protected User $user;
    protected Company $company;
    protected Client $client;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'service',
        ]);

        $this->actingAs($this->user);
        $this->service = app(ClientServiceManagementService::class);
    }

    public function test_provision_service_creates_client_service_with_minimum_data(): void
    {
        Event::fake([ServiceProvisioned::class]);

        $service = $this->service->provisionService($this->client, $this->product);

        $this->assertInstanceOf(ClientService::class, $service);
        $this->assertEquals($this->company->id, $service->company_id);
        $this->assertEquals($this->client->id, $service->client_id);
        $this->assertEquals($this->product->id, $service->product_id);
        $this->assertEquals('pending', $service->status);
        $this->assertDatabaseHas('client_services', ['id' => $service->id]);

        Event::assertDispatched(ServiceProvisioned::class);
    }

    public function test_provision_service_creates_service_with_custom_config(): void
    {
        Event::fake([ServiceProvisioned::class]);

        $config = [
            'name' => 'Custom Managed Service',
            'service_type' => 'managed_services',
            'category' => 'infrastructure',
            'monthly_cost' => 2500.00,
            'setup_cost' => 500.00,
            'billing_cycle' => 'monthly',
            'service_level' => 'premium',
            'priority_level' => 'high',
            'auto_renewal' => true,
            'monitoring_enabled' => true,
        ];

        $service = $this->service->provisionService($this->client, $this->product, $config);

        $this->assertEquals('Custom Managed Service', $service->name);
        $this->assertEquals('managed_services', $service->service_type);
        $this->assertEquals(2500.00, (float) $service->monthly_cost);
        $this->assertEquals(500.00, (float) $service->setup_cost);
        $this->assertEquals('premium', $service->service_level);
        $this->assertTrue($service->auto_renewal);
        $this->assertTrue($service->monitoring_enabled);
    }

    public function test_provision_service_uses_product_defaults_when_no_config(): void
    {
        Event::fake();

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cloud Backup Service',
            'base_price' => 1500.00,
            'type' => 'service',
        ]);

        $service = $this->service->provisionService($this->client, $product);

        $this->assertEquals('Cloud Backup Service', $service->name);
        $this->assertEquals(1500.00, (float) $service->monthly_cost);
    }

    public function test_activate_service_updates_status_and_timestamps(): void
    {
        Event::fake([ServiceActivated::class]);

        $clientService = ClientService::factory()->pending()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->activateService($clientService);

        $this->assertTrue($result);
        $clientService->refresh();
        $this->assertEquals('active', $clientService->status);
        $this->assertNotNull($clientService->activated_at);

        Event::assertDispatched(ServiceActivated::class);
    }

    public function test_activate_service_returns_true_for_already_active_service(): void
    {
        Event::fake();

        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->activateService($clientService);

        $this->assertTrue($result);
        Event::assertNotDispatched(ServiceActivated::class);
    }

    public function test_suspend_service_updates_status_and_adds_notes(): void
    {
        Event::fake([ServiceSuspended::class]);

        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->suspendService($clientService, 'Non-payment');

        $this->assertTrue($result);
        $clientService->refresh();
        $this->assertEquals('suspended', $clientService->status);
        $this->assertNotNull($clientService->suspended_at);
        $this->assertStringContainsString('Suspended: Non-payment', $clientService->notes);

        Event::assertDispatched(ServiceSuspended::class);
    }

    public function test_suspend_service_returns_true_for_already_suspended_service(): void
    {
        Event::fake();

        $clientService = ClientService::factory()->suspended()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->suspendService($clientService, 'Test reason');

        $this->assertTrue($result);
        Event::assertNotDispatched(ServiceSuspended::class);
    }

    public function test_cancel_service_updates_status_and_effective_date(): void
    {
        Event::fake([ServiceCancelled::class]);

        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addYear(),
        ]);

        $effectiveDate = now()->addDays(30);
        $cancellationFee = $this->service->cancelService($clientService, $effectiveDate);

        $this->assertIsFloat($cancellationFee);
        $clientService->refresh();
        $this->assertEquals('cancelled', $clientService->status);
        $this->assertNotNull($clientService->cancelled_at);
        $this->assertEquals($effectiveDate->format('Y-m-d'), $clientService->end_date->format('Y-m-d'));

        Event::assertDispatched(ServiceCancelled::class);
    }

    public function test_cancel_service_uses_current_date_when_no_effective_date_provided(): void
    {
        Event::fake([ServiceCancelled::class]);

        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $cancellationFee = $this->service->cancelService($clientService);

        $clientService->refresh();
        $this->assertEquals('cancelled', $clientService->status);
        $this->assertEquals(now()->format('Y-m-d'), $clientService->cancelled_at->format('Y-m-d'));
    }

    public function test_renew_service_extends_dates_and_increments_count(): void
    {
        Event::fake([ServiceRenewed::class]);

        $originalRenewalDate = now()->addMonth();
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'renewal_date' => $originalRenewalDate,
            'end_date' => $originalRenewalDate,
            'renewal_count' => 1,
        ]);

        $renewed = $this->service->renewService($clientService, 12);

        $renewed->refresh();
        $this->assertEquals(2, $renewed->renewal_count);
        $this->assertNotNull($renewed->last_renewed_at);
        $this->assertTrue($renewed->renewal_date->greaterThan($originalRenewalDate));

        Event::assertDispatched(ServiceRenewed::class);
    }

    public function test_renew_service_handles_null_renewal_date(): void
    {
        Event::fake();

        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'renewal_date' => null,
            'end_date' => now()->addMonths(6),
            'renewal_count' => 0,
        ]);

        $renewed = $this->service->renewService($clientService, 12);

        $renewed->refresh();
        $this->assertNotNull($renewed->renewal_date);
        $this->assertEquals(1, $renewed->renewal_count);
    }

    public function test_get_due_for_renewal_returns_services_within_days(): void
    {
        $dueService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'renewal_date' => now()->addDays(15),
        ]);

        $laterService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'renewal_date' => now()->addDays(60),
        ]);

        $results = $this->service->getDueForRenewal(30);

        $this->assertTrue($results->contains($dueService));
        $this->assertFalse($results->contains($laterService));
    }

    public function test_get_ending_soon_returns_services_within_days(): void
    {
        $endingService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addDays(20),
        ]);

        $laterService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addDays(60),
        ]);

        $results = $this->service->getEndingSoon(30);

        $this->assertTrue($results->contains($endingService));
        $this->assertFalse($results->contains($laterService));
    }

    public function test_calculate_mrr_sums_active_services_for_company(): void
    {
        ClientService::factory()->active()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'monthly_cost' => 1000.00,
        ]);

        ClientService::factory()->suspended()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'monthly_cost' => 500.00,
        ]);

        $mrr = $this->service->calculateMRR();

        $this->assertEquals(3000.00, $mrr);
    }

    public function test_calculate_mrr_filters_by_client_when_provided(): void
    {
        $client2 = Client::factory()->create(['company_id' => $this->company->id]);

        ClientService::factory()->active()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'monthly_cost' => 1000.00,
        ]);

        ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $client2->id,
            'monthly_cost' => 500.00,
        ]);

        $mrr = $this->service->calculateMRR($this->client);

        $this->assertEquals(2000.00, $mrr);
    }

    public function test_get_service_health_calculates_score_based_on_factors(): void
    {
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'sla_breaches_count' => 2,
            'client_satisfaction' => 8,
            'last_review_date' => now()->subDays(30),
        ]);

        $health = $this->service->getServiceHealth($clientService);

        $this->assertIsArray($health);
        $this->assertArrayHasKey('score', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('factors', $health);
        $this->assertGreaterThan(0, $health['score']);
        $this->assertLessThanOrEqual(100, $health['score']);
    }

    public function test_get_service_health_considers_sla_breaches(): void
    {
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'sla_breaches_count' => 5,
        ]);

        $health = $this->service->getServiceHealth($clientService);

        $factors = collect($health['factors'])->pluck('name')->toArray();
        $this->assertContains('SLA Breaches', $factors);
    }

    public function test_get_service_health_considers_client_satisfaction(): void
    {
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'client_satisfaction' => 9,
            'sla_breaches_count' => 0,
        ]);

        $health = $this->service->getServiceHealth($clientService);

        $factors = collect($health['factors'])->pluck('name')->toArray();
        $this->assertContains('Client Satisfaction', $factors);
    }

    public function test_get_service_health_considers_overdue_review(): void
    {
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'last_review_date' => now()->subDays(120),
            'sla_breaches_count' => 0,
        ]);

        $health = $this->service->getServiceHealth($clientService);

        $factors = collect($health['factors'])->pluck('name')->toArray();
        $this->assertContains('Review Overdue', $factors);
    }

    public function test_get_service_health_updates_service_record(): void
    {
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'health_score' => null,
            'last_health_check_at' => null,
        ]);

        $health = $this->service->getServiceHealth($clientService);

        $clientService->refresh();
        $this->assertEquals($health['score'], $clientService->health_score);
        $this->assertNotNull($clientService->last_health_check_at);
    }

    public function test_get_service_health_returns_correct_status_for_healthy(): void
    {
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'sla_breaches_count' => 0,
            'client_satisfaction' => 10,
            'last_review_date' => now()->subDays(30),
        ]);

        $health = $this->service->getServiceHealth($clientService);

        $this->assertEquals('healthy', $health['status']);
    }

    public function test_transfer_to_client_updates_client_id_and_logs(): void
    {
        $newClient = Client::factory()->create(['company_id' => $this->company->id]);
        
        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $oldClientId = $clientService->client_id;

        $transferred = $this->service->transferToClient($clientService, $newClient);

        $this->assertEquals($newClient->id, $transferred->client_id);
        $this->assertStringContainsString("Transferred from client #{$oldClientId}", $transferred->notes);
    }

    public function test_get_client_services_returns_services_for_client(): void
    {
        ClientService::factory()->active()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $otherClient = Client::factory()->create(['company_id' => $this->company->id]);
        ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $otherClient->id,
        ]);

        $services = $this->service->getClientServices($this->client);

        $this->assertCount(3, $services);
        $services->each(function ($service) {
            $this->assertEquals($this->client->id, $service->client_id);
        });
    }

    public function test_get_client_services_filters_by_status(): void
    {
        ClientService::factory()->active()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        ClientService::factory()->suspended()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $services = $this->service->getClientServices($this->client, ['status' => 'active']);

        $this->assertCount(2, $services);
    }

    public function test_get_client_services_filters_by_service_type(): void
    {
        ClientService::factory()->managedServices()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        ClientService::factory()->cloudServices()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $services = $this->service->getClientServices($this->client, ['service_type' => 'managed_services']);

        $this->assertCount(2, $services);
    }

    public function test_get_client_services_filters_by_category(): void
    {
        ClientService::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category' => 'infrastructure',
        ]);

        ClientService::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'category' => 'security',
        ]);

        $services = $this->service->getClientServices($this->client, ['category' => 'infrastructure']);

        $this->assertCount(1, $services);
        $this->assertEquals('infrastructure', $services->first()->category);
    }

    public function test_resume_service_reactivates_suspended_service(): void
    {
        Event::fake([ServiceResumed::class]);

        $clientService = ClientService::factory()->suspended()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->resumeService($clientService);

        $this->assertTrue($result);
        $clientService->refresh();
        $this->assertEquals('active', $clientService->status);
        $this->assertNull($clientService->suspended_at);

        Event::assertDispatched(ServiceResumed::class);
    }

    public function test_resume_service_returns_false_for_non_suspended_service(): void
    {
        Event::fake();

        $clientService = ClientService::factory()->active()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->resumeService($clientService);

        $this->assertFalse($result);
        Event::assertNotDispatched(ServiceResumed::class);
    }

    public function test_provision_service_runs_in_transaction(): void
    {
        Event::fake();

        // Force a database error by using invalid client_id
        try {
            $this->service->provisionService(
                new Client(['id' => 999999, 'company_id' => $this->company->id]),
                $this->product
            );
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Transaction should rollback, no service should be created
            $this->assertEquals(0, ClientService::count());
        }
    }

    public function test_activate_service_runs_in_transaction(): void
    {
        $clientService = ClientService::factory()->pending()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->service->activateService($clientService);

        // Verify changes were committed
        $this->assertDatabaseHas('client_services', [
            'id' => $clientService->id,
            'status' => 'active',
        ]);
    }
}
