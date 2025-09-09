<?php

namespace App\Http\Controllers;

use App\Services\CollectionAnalyticsService;
use App\Services\CollectionManagementService;
use App\Services\DunningAutomationService;
use App\Services\PaymentPlanService;
use App\Services\ComplianceService;
use App\Models\Client;
use App\Models\DunningCampaign;
use App\Models\PaymentPlan;
use App\Models\AccountHold;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Collection Dashboard Controller
 * 
 * Handles the main collection dashboard interface and API endpoints
 * for dunning management system with real-time data and analytics.
 */
class CollectionDashboardController extends Controller
{
    protected CollectionAnalyticsService $analyticsService;
    protected CollectionManagementService $collectionService;
    protected DunningAutomationService $dunningService;
    protected PaymentPlanService $paymentPlanService;
    protected ComplianceService $complianceService;

    public function __construct(
        CollectionAnalyticsService $analyticsService,
        CollectionManagementService $collectionService,
        DunningAutomationService $dunningService,
        PaymentPlanService $paymentPlanService,
        ComplianceService $complianceService
    ) {
        $this->analyticsService = $analyticsService;
        $this->collectionService = $collectionService;
        $this->dunningService = $dunningService;
        $this->paymentPlanService = $paymentPlanService;
        $this->complianceService = $complianceService;
    }

    /**
     * Display the main collection dashboard.
     */
    public function index(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        
        // Get dashboard data
        $dashboardData = $this->analyticsService->generateDashboard([
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end']
        ]);

        return view('collections.dashboard', [
            'dashboard' => $dashboardData,
            'dateRange' => $dateRange,
            'title' => 'Collection Dashboard'
        ]);
    }

    /**
     * Get dashboard data via API.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $data = $this->analyticsService->generateDashboard([
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end']
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'generated_at' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Get collection trends data.
     */
    public function getTrendsData(Request $request): JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $dashboard = $this->analyticsService->generateDashboard([
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end']
        ]);

        return response()->json([
            'success' => true,
            'trends' => $dashboard['collection_trends'],
            'kpis' => $dashboard['kpi_metrics']
        ]);
    }

    /**
     * Display client list with collection status.
     */
    public function clients(Request $request)
    {
        $query = Client::with(['invoices', 'payments', 'dunningActions'])
            ->whereHas('invoices', function ($q) {
                $q->where('status', '!=', 'paid');
            });

        // Apply filters
        if ($request->has('risk_level')) {
            // This would need to be implemented with a risk level field or calculated on-the-fly
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('account_number', 'LIKE', "%{$search}%");
            });
        }

        $clients = $query->paginate(25);

        // Add risk assessment for each client
        foreach ($clients as $client) {
            $client->risk_assessment = $this->collectionService->assessClientRisk($client);
        }

        return view('collections.clients', [
            'clients' => $clients,
            'filters' => $request->only(['risk_level', 'search']),
            'title' => 'Client Collection Status'
        ]);
    }

    /**
     * Display individual client collection details.
     */
    public function clientDetails(Request $request, Client $client)
    {
        $client->load(['invoices', 'payments', 'dunningActions', 'paymentPlans', 'collectionNotes', 'accountHolds']);

        // Get risk assessment
        $riskAssessment = $this->collectionService->assessClientRisk($client);

        // Get payment plan recommendations
        $paymentPlanRecommendations = $this->paymentPlanService->getPaymentPlanRecommendations($client);

        // Get compliance check
        $complianceCheck = $this->complianceService->performComplianceCheck($client);

        return view('collections.client-details', [
            'client' => $client,
            'riskAssessment' => $riskAssessment,
            'paymentPlanRecommendations' => $paymentPlanRecommendations,
            'complianceCheck' => $complianceCheck,
            'title' => "Client Details - {$client->name}"
        ]);
    }

    /**
     * Display dunning campaigns.
     */
    public function campaigns(Request $request)
    {
        $campaigns = DunningCampaign::with(['dunningSequences', 'dunningActions'])
            ->withCount('dunningActions')
            ->paginate(20);

        // Add performance metrics for each campaign
        foreach ($campaigns as $campaign) {
            $campaign->performance_metrics = $this->calculateCampaignMetrics($campaign);
        }

        return view('collections.campaigns', [
            'campaigns' => $campaigns,
            'title' => 'Dunning Campaigns'
        ]);
    }

    /**
     * Display campaign creation form.
     */
    public function createCampaign()
    {
        return view('collections.create-campaign', [
            'title' => 'Create Dunning Campaign'
        ]);
    }

    /**
     * Store new dunning campaign.
     */
    public function storeCampaign(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_criteria' => 'required|array',
            'risk_strategy' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $campaign = DunningCampaign::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'trigger_criteria' => $validated['trigger_criteria'],
            'risk_strategy' => $validated['risk_strategy'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id()
        ]);

        return redirect()->route('collections.campaigns')
            ->with('success', 'Dunning campaign created successfully.');
    }

    /**
     * Display payment plans.
     */
    public function paymentPlans(Request $request)
    {
        $query = PaymentPlan::with(['client', 'invoices'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $paymentPlans = $query->paginate(25);

        // Add performance metrics
        foreach ($paymentPlans as $plan) {
            $plan->performance_metrics = $this->paymentPlanService->calculatePerformanceMetrics($plan);
        }

        return view('collections.payment-plans', [
            'paymentPlans' => $paymentPlans,
            'filters' => $request->only(['status']),
            'title' => 'Payment Plans'
        ]);
    }

    /**
     * Display account holds.
     */
    public function accountHolds(Request $request)
    {
        $holds = AccountHold::with(['client'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('collections.account-holds', [
            'holds' => $holds,
            'title' => 'Account Holds'
        ]);
    }

    /**
     * Display analytics reports.
     */
    public function analytics(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        
        $executiveSummary = $this->analyticsService->generateExecutiveSummary([
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end']
        ]);

        return view('collections.analytics', [
            'executiveSummary' => $executiveSummary,
            'dateRange' => $dateRange,
            'title' => 'Collection Analytics'
        ]);
    }

    /**
     * Display compliance dashboard.
     */
    public function compliance(Request $request)
    {
        $complianceReport = $this->complianceService->generateComplianceReport([
            'start_date' => $request->input('start_date', Carbon::now()->subMonth()->toDateString()),
            'end_date' => $request->input('end_date', Carbon::now()->toDateString())
        ]);

        return view('collections.compliance', [
            'complianceReport' => $complianceReport,
            'title' => 'Compliance Dashboard'
        ]);
    }

    /**
     * Process batch dunning actions.
     */
    public function processBatchDunning(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'exists:clients,id',
            'campaign_id' => 'required|exists:dunning_campaigns,id'
        ]);

        try {
            $results = $this->dunningService->processBatchDunning(
                $validated['client_ids'],
                $validated['campaign_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch dunning processed successfully',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch dunning: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate service suspension for a client.
     */
    public function suspendServices(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'scheduled_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $hold = app(VoipCollectionService::class)->suspendVoipServices(
                $client,
                $validated['reason'],
                [
                    'scheduled_date' => $validated['scheduled_date'] ? Carbon::parse($validated['scheduled_date']) : null,
                    'notes' => $validated['notes']
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Service suspension initiated',
                'hold_id' => $hold->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create payment plan for a client.
     */
    public function createPaymentPlan(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1|max:24',
            'down_payment_percent' => 'nullable|numeric|min:0|max:50',
            'notes' => 'nullable|string',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id'
        ]);

        try {
            // Get optimal payment plan
            $planDetails = $this->paymentPlanService->createOptimalPaymentPlan(
                $client,
                $validated['total_amount'],
                [
                    'custom_duration' => $validated['duration_months'],
                    'down_payment_percent' => $validated['down_payment_percent'] ?? 15
                ]
            );

            // Create the payment plan
            $paymentPlan = $this->paymentPlanService->createPaymentPlan(
                $client,
                $validated['invoice_ids'],
                $planDetails,
                ['notes' => $validated['notes']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment plan created successfully',
                'payment_plan_id' => $paymentPlan->id,
                'plan_details' => $planDetails
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data.
     */
    public function exportAnalytics(Request $request)
    {
        $dateRange = $this->getDateRange($request);
        $format = $request->input('format', 'csv');

        $data = $this->analyticsService->generateDashboard([
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end']
        ]);

        $filename = "collection_analytics_{$dateRange['start']}_to_{$dateRange['end']}.{$format}";

        switch ($format) {
            case 'json':
                return response()->json($data)
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

            case 'csv':
            default:
                return $this->exportToCsv($data, $filename);
        }
    }

    /**
     * Helper method to get date range from request.
     */
    protected function getDateRange(Request $request): array
    {
        return [
            'start' => $request->input('start_date', Carbon::now()->subDays(30)->toDateString()),
            'end' => $request->input('end_date', Carbon::now()->toDateString())
        ];
    }

    /**
     * Calculate campaign performance metrics.
     */
    protected function calculateCampaignMetrics(DunningCampaign $campaign): array
    {
        $actions = $campaign->dunningActions;
        $totalActions = $actions->count();
        $successfulActions = $actions->where('status', 'completed')->whereNotNull('responded_at')->count();

        return [
            'total_actions' => $totalActions,
            'successful_actions' => $successfulActions,
            'success_rate' => $totalActions > 0 ? ($successfulActions / $totalActions) * 100 : 0,
            'avg_response_time' => $this->calculateAvgResponseTime($actions)
        ];
    }

    /**
     * Calculate average response time for actions.
     */
    protected function calculateAvgResponseTime($actions): float
    {
        $responseTimes = [];
        
        foreach ($actions as $action) {
            if ($action->responded_at && $action->created_at) {
                $responseTimes[] = Carbon::parse($action->responded_at)
                    ->diffInHours($action->created_at);
            }
        }

        return count($responseTimes) > 0 ? array_sum($responseTimes) / count($responseTimes) : 0;
    }

    /**
     * Export data to CSV format.
     */
    protected function exportToCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write summary data
            fputcsv($file, ['Metric', 'Value']);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($file, [ucfirst(str_replace('_', ' ', $key)), $value]);
            }
            
            fputcsv($file, []); // Empty row
            
            // Write KPI data
            fputcsv($file, ['KPI', 'Value', 'Target', 'Status']);
            foreach ($data['kpi_metrics'] as $kpi => $metrics) {
                fputcsv($file, [
                    ucfirst(str_replace('_', ' ', $kpi)),
                    $metrics['value'],
                    $metrics['target'],
                    $metrics['status']
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}