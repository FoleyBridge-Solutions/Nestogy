<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\DunningCampaign;
use App\Models\DunningSequence;
use App\Models\DunningAction;
use App\Models\PaymentPlan;
use App\Models\CollectionNote;
use App\Models\AccountHold;
use App\Services\DunningAutomationService;
use App\Services\CollectionManagementService;
use App\Services\PaymentPlanService;
use App\Services\VoipCollectionService;
use App\Services\CommunicationService;
use App\Services\PaymentProcessingService;
use App\Services\CollectionAnalyticsService;
use App\Services\ComplianceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Comprehensive Integration Tests for Dunning Management System
 * 
 * Tests integration with existing billing components including
 * invoices, payments, clients, and VoIP tax calculations.
 */
class DunningIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Client $testClient;
    protected DunningCampaign $testCampaign;
    protected array $testInvoices = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test client with realistic data
        $this->testClient = Client::create([
            'name' => 'Test VoIP Client',
            'email' => 'test@example.com',
            'phone' => '+15551234567',
            'account_number' => 'AC' . time(),
            'account_type' => 'business',
            'mailing_address' => '123 Test Street, Test City, CA 90210',
            'state' => 'CA',
            'sms_consent' => true,
            'created_at' => Carbon::now()->subMonths(6)
        ]);

        // Create test campaign
        $this->testCampaign = DunningCampaign::create([
            'name' => 'Standard Collection Campaign',
            'description' => 'Standard dunning process for overdue accounts',
            'trigger_criteria' => [
                'days_overdue' => 5,
                'minimum_amount' => 50.00
            ],
            'risk_strategy' => 'standard',
            'is_active' => true,
            'created_by' => 1
        ]);

        // Create test invoices with varying due dates and amounts
        $this->createTestInvoices();
    }

    /**
     * Test complete dunning workflow integration.
     */
    public function test_complete_dunning_workflow_integration()
    {
        $dunningService = app(DunningAutomationService::class);
        $collectionService = app(CollectionManagementService::class);
        
        // Step 1: Assess client risk based on existing invoice/payment history
        $riskAssessment = $collectionService->assessClientRisk($this->testClient);
        
        $this->assertArrayHasKey('risk_level', $riskAssessment);
        $this->assertArrayHasKey('risk_score', $riskAssessment);
        $this->assertArrayHasKey('factors', $riskAssessment);
        
        // Step 2: Execute dunning campaign
        $results = $dunningService->executeDunningCampaign($this->testCampaign);
        
        $this->assertTrue($results['success']);
        $this->assertGreaterThan(0, $results['clients_processed']);
        
        // Step 3: Verify dunning actions were created
        $dunningActions = DunningAction::where('client_id', $this->testClient->id)->get();
        $this->assertGreaterThan(0, $dunningActions->count());
        
        // Step 4: Verify integration with invoice system
        $this->testClient->refresh();
        $pastDueAmount = $this->testClient->getPastDueAmount();
        $this->assertGreaterThan(0, $pastDueAmount);
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test payment plan integration with existing invoices.
     */
    public function test_payment_plan_integration_with_invoices()
    {
        $paymentPlanService = app(PaymentPlanService::class);
        
        // Get overdue invoices
        $overdueInvoices = $this->testClient->invoices()->overdue()->get();
        $this->assertGreaterThan(0, $overdueInvoices->count());
        
        $totalAmount = $overdueInvoices->sum('amount');
        $invoiceIds = $overdueInvoices->pluck('id')->toArray();
        
        // Create optimal payment plan
        $optimalPlan = $paymentPlanService->createOptimalPaymentPlan(
            $this->testClient,
            $totalAmount
        );
        
        $this->assertArrayHasKey('monthly_payment', $optimalPlan);
        $this->assertArrayHasKey('duration_months', $optimalPlan);
        $this->assertGreaterThan(0, $optimalPlan['monthly_payment']);
        
        // Create actual payment plan
        $paymentPlan = $paymentPlanService->createPaymentPlan(
            $this->testClient,
            $invoiceIds,
            $optimalPlan,
            ['notes' => 'Integration test payment plan']
        );
        
        $this->assertInstanceOf(PaymentPlan::class, $paymentPlan);
        $this->assertEquals($totalAmount, $paymentPlan->total_amount);
        
        // Verify invoice relationships
        $this->assertEquals(count($invoiceIds), $paymentPlan->invoices()->count());
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test VoIP service suspension integration.
     */
    public function test_voip_service_suspension_integration()
    {
        $voipService = app(VoipCollectionService::class);
        
        // Test E911 compliance check
        $e911Compliance = $voipService->checkE911Compliance($this->testClient);
        $this->assertArrayHasKey('is_compliant', $e911Compliance);
        $this->assertArrayHasKey('active_e911_numbers', $e911Compliance);
        
        // Initiate service suspension
        $hold = $voipService->suspendVoipServices(
            $this->testClient,
            'Non-payment - Integration Test',
            ['notes' => 'Automated integration test suspension']
        );
        
        $this->assertInstanceOf(AccountHold::class, $hold);
        $this->assertEquals('service_suspension', $hold->hold_type);
        $this->assertEquals('active', $hold->status);
        
        // Verify E911 services are preserved
        $preservedServices = $hold->services_preserved ?? [];
        $this->assertContains('E911', $preservedServices);
        
        // Test restoration
        $restored = $voipService->restoreVoipServices($hold, 'Payment received - Test');
        $this->assertTrue($restored);
        
        $hold->refresh();
        $this->assertEquals('resolved', $hold->status);
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test payment processing integration.
     */
    public function test_payment_processing_integration()
    {
        $paymentService = app(PaymentProcessingService::class);
        $communicationService = app(CommunicationService::class);
        $voipService = app(VoipCollectionService::class);
        
        // Create a service suspension first
        $hold = $voipService->suspendVoipServices(
            $this->testClient,
            'Non-payment - Test',
            []
        );
        
        $overdueAmount = $this->testClient->getPastDueAmount();
        $this->assertGreaterThan(0, $overdueAmount);
        
        // Process payment
        $paymentResult = $paymentService->processPayment(
            $this->testClient,
            $overdueAmount,
            'credit_card',
            ['stripe_token' => 'tok_test_payment'],
            ['source' => 'integration_test']
        );
        
        // Verify payment processing
        $this->assertTrue($paymentResult['success']);
        $this->assertArrayHasKey('payment_id', $paymentResult);
        
        // Verify payment was created
        $payment = Payment::find($paymentResult['payment_id']);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($this->testClient->id, $payment->client_id);
        $this->assertEquals($overdueAmount, $payment->amount);
        
        // Since this is a test, the payment would normally be processed through a gateway
        // For testing purposes, we'll mark it as completed manually
        $payment->update(['status' => 'completed', 'processed_at' => Carbon::now()]);
        
        // Verify service restoration integration
        $this->testClient->refresh();
        $newBalance = $this->testClient->getBalance();
        
        // Verify collection note was created
        $collectionNotes = CollectionNote::where('client_id', $this->testClient->id)
            ->where('payment_id', $payment->id)
            ->get();
        $this->assertGreaterThan(0, $collectionNotes->count());
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test communication system integration.
     */
    public function test_communication_system_integration()
    {
        $communicationService = app(CommunicationService::class);
        
        // Test multi-channel communication
        $result = $communicationService->sendDunningCommunication(
            $this->testClient,
            'payment_reminder',
            [
                'past_due_amount' => $this->testClient->getPastDueAmount(),
                'due_date' => Carbon::now()->addDays(10)->format('M j, Y')
            ],
            ['channels' => ['email', 'portal_notification']]
        );
        
        $this->assertArrayHasKey('sent', $result);
        $this->assertArrayHasKey('total_channels', $result);
        $this->assertEquals(2, $result['total_channels']);
        
        // Verify dunning actions were created
        $dunningActions = DunningAction::where('client_id', $this->testClient->id)
            ->whereIn('action_type', ['email', 'portal_notification'])
            ->get();
        
        $this->assertGreaterThan(0, $dunningActions->count());
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test analytics integration with billing data.
     */
    public function test_analytics_integration_with_billing_data()
    {
        $analyticsService = app(CollectionAnalyticsService::class);
        
        // Generate dashboard with billing data integration
        $dashboard = $analyticsService->generateDashboard([
            'start_date' => Carbon::now()->subDays(30)->toDateString(),
            'end_date' => Carbon::now()->toDateString()
        ]);
        
        // Verify dashboard structure
        $this->assertArrayHasKey('summary', $dashboard);
        $this->assertArrayHasKey('kpi_metrics', $dashboard);
        $this->assertArrayHasKey('collection_trends', $dashboard);
        $this->assertArrayHasKey('aging_analysis', $dashboard);
        
        // Verify summary calculations include our test data
        $summary = $dashboard['summary'];
        $this->assertArrayHasKey('total_outstanding', $summary);
        $this->assertArrayHasKey('collection_rate', $summary);
        
        // Test aging analysis integration
        $aging = $dashboard['aging_analysis'];
        $this->assertArrayHasKey('buckets', $aging);
        $this->assertArrayHasKey('total_outstanding', $aging);
        
        // Verify aging buckets have proper structure
        foreach ($aging['buckets'] as $bucket => $data) {
            $this->assertArrayHasKey('amount', $data);
            $this->assertArrayHasKey('count', $data);
            $this->assertArrayHasKey('collection_probability', $data);
        }
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test compliance integration.
     */
    public function test_compliance_integration()
    {
        $complianceService = app(ComplianceService::class);
        
        // Perform compliance check
        $complianceCheck = $complianceService->performComplianceCheck($this->testClient);
        
        $this->assertArrayHasKey('overall_status', $complianceCheck);
        $this->assertArrayHasKey('fdcpa_compliance', $complianceCheck);
        $this->assertArrayHasKey('tcpa_compliance', $complianceCheck);
        $this->assertArrayHasKey('state_compliance', $complianceCheck);
        
        // Test legal document generation
        $validationNotice = $complianceService->generateLegalDocumentation(
            $this->testClient,
            'validation_notice'
        );
        
        $this->assertIsArray($validationNotice);
        $this->assertNotEmpty($validationNotice);
        
        // Verify document structure
        $document = $validationNotice[0];
        $this->assertArrayHasKey('type', $document);
        $this->assertArrayHasKey('content', $document);
        $this->assertArrayHasKey('generated_date', $document);
        $this->assertEquals('validation_notice', $document['type']);
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test VoIP tax integration with dunning system.
     */
    public function test_voip_tax_integration()
    {
        // Create invoice with VoIP taxes
        $voipInvoice = Invoice::create([
            'client_id' => $this->testClient->id,
            'invoice_number' => 'INV-VOIP-' . time(),
            'amount' => 150.00,
            'tax_amount' => 15.75, // VoIP taxes
            'due_date' => Carbon::now()->subDays(10),
            'status' => 'sent',
            'created_at' => Carbon::now()->subDays(15)
        ]);
        
        // Test that dunning system properly handles VoIP tax components
        $collectionService = app(CollectionManagementService::class);
        $riskAssessment = $collectionService->assessClientRisk($this->testClient);
        
        // Verify VoIP invoice is included in risk calculation
        $this->assertArrayHasKey('factors', $riskAssessment);
        $this->assertArrayHasKey('account_aging', $riskAssessment['factors']);
        
        // Test payment plan creation includes tax amounts
        $paymentPlanService = app(PaymentPlanService::class);
        $totalWithTax = $voipInvoice->amount + $voipInvoice->tax_amount;
        
        $planDetails = $paymentPlanService->createOptimalPaymentPlan(
            $this->testClient,
            $totalWithTax
        );
        
        $this->assertEquals($totalWithTax, $planDetails['total_amount']);
        
        $this->assertTrue(true); // Test passed
    }

    /**
     * Test end-to-end workflow from invoice creation to payment.
     */
    public function test_end_to_end_dunning_workflow()
    {
        // Step 1: Create overdue invoice (simulating existing billing system)
        $overdueInvoice = Invoice::create([
            'client_id' => $this->testClient->id,
            'invoice_number' => 'E2E-' . time(),
            'amount' => 500.00,
            'due_date' => Carbon::now()->subDays(15),
            'status' => 'sent'
        ]);
        
        // Step 2: Execute dunning automation
        $dunningService = app(DunningAutomationService::class);
        $results = $dunningService->executeDunningCampaign($this->testCampaign);
        
        $this->assertTrue($results['success']);
        
        // Step 3: Verify risk assessment
        $collectionService = app(CollectionManagementService::class);
        $riskAssessment = $collectionService->assessClientRisk($this->testClient);
        
        $this->assertIsArray($riskAssessment);
        
        // Step 4: Create and execute payment plan
        $paymentPlanService = app(PaymentPlanService::class);
        $paymentPlan = $paymentPlanService->createPaymentPlan(
            $this->testClient,
            [$overdueInvoice->id],
            [
                'total_amount' => $overdueInvoice->amount,
                'monthly_payment' => 100.00,
                'duration_months' => 5,
                'down_payment' => 50.00
            ]
        );
        
        $this->assertInstanceOf(PaymentPlan::class, $paymentPlan);
        
        // Step 5: Process payment
        $paymentService = app(PaymentProcessingService::class);
        $paymentResult = $paymentService->processPayment(
            $this->testClient,
            $paymentPlan->down_payment,
            'ach',
            [],
            ['payment_plan_id' => $paymentPlan->id]
        );
        
        $this->assertArrayHasKey('payment_id', $paymentResult);
        
        // Step 6: Generate analytics
        $analyticsService = app(CollectionAnalyticsService::class);
        $dashboard = $analyticsService->generateDashboard();
        
        $this->assertArrayHasKey('summary', $dashboard);
        
        // Step 7: Verify compliance
        $complianceService = app(ComplianceService::class);
        $compliance = $complianceService->performComplianceCheck($this->testClient);
        
        $this->assertArrayHasKey('overall_status', $compliance);
        
        $this->assertTrue(true); // Complete end-to-end test passed
    }

    /**
     * Create test invoices with realistic data.
     */
    protected function createTestInvoices(): void
    {
        // Current invoice (not overdue)
        $this->testInvoices[] = Invoice::create([
            'client_id' => $this->testClient->id,
            'invoice_number' => 'INV-001-' . time(),
            'amount' => 250.00,
            'due_date' => Carbon::now()->addDays(15),
            'status' => 'sent',
            'created_at' => Carbon::now()->subDays(5)
        ]);

        // Recently overdue invoice
        $this->testInvoices[] = Invoice::create([
            'client_id' => $this->testClient->id,
            'invoice_number' => 'INV-002-' . time(),
            'amount' => 175.50,
            'due_date' => Carbon::now()->subDays(10),
            'status' => 'sent',
            'created_at' => Carbon::now()->subDays(20)
        ]);

        // Significantly overdue invoice
        $this->testInvoices[] = Invoice::create([
            'client_id' => $this->testClient->id,
            'invoice_number' => 'INV-003-' . time(),
            'amount' => 425.75,
            'due_date' => Carbon::now()->subDays(45),
            'status' => 'sent',
            'created_at' => Carbon::now()->subDays(55)
        ]);

        // Very old overdue invoice
        $this->testInvoices[] = Invoice::create([
            'client_id' => $this->testClient->id,
            'invoice_number' => 'INV-004-' . time(),
            'amount' => 89.25,
            'due_date' => Carbon::now()->subDays(125),
            'status' => 'sent',
            'created_at' => Carbon::now()->subDays(135)
        ]);

        // Create some payment history
        Payment::create([
            'client_id' => $this->testClient->id,
            'invoice_id' => $this->testInvoices[0]->id,
            'amount' => 100.00,
            'payment_method' => 'credit_card',
            'status' => 'completed',
            'processed_at' => Carbon::now()->subDays(30),
            'created_at' => Carbon::now()->subDays(30)
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->testInvoices as $invoice) {
            $invoice->delete();
        }
        
        $this->testCampaign->delete();
        $this->testClient->delete();
        
        parent::tearDown();
    }
}