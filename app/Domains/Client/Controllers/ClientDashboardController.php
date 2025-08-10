<?php

namespace App\Domains\Client\Controllers;

use App\Models\Client;
use App\Domains\Client\Services\ClientDashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ClientDashboardController extends Controller
{
    protected ClientDashboardService $dashboardService;

    public function __construct(ClientDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        
        // Apply middleware
        $this->middleware('auth');
        $this->middleware('permission:clients.view')->only(['index', 'show']);
        $this->middleware('permission:clients.dashboard')->only(['index', 'show']);
    }

    /**
     * Display the client dashboard
     */
    public function index(Client $client): View
    {
        // Authorize access
        $this->authorize('view', $client);

        // Cache key for this client's dashboard
        $cacheKey = "client_dashboard_{$client->id}_" . Auth::id();
        
        // Get dashboard data (with caching)
        $dashboardData = Cache::remember($cacheKey, 300, function () use ($client) {
            return $this->dashboardService->getDashboardData($client);
        });

        return view('clients.dashboard', compact('client', 'dashboardData'));
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getData(Client $client): JsonResponse
    {
        // Authorize access
        $this->authorize('view', $client);

        $dashboardData = $this->dashboardService->getDashboardData($client);

        return response()->json($dashboardData);
    }

    /**
     * Get ticket statistics
     */
    public function tickets(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $ticketStats = $this->dashboardService->getTicketStats($client);

        return response()->json($ticketStats);
    }

    /**
     * Get financial statistics
     */
    public function financial(Client $client): JsonResponse
    {
        $this->authorize('view', $client);
        $this->authorize('viewFinancial', $client);

        $financialStats = $this->dashboardService->getFinancialStats($client);

        return response()->json($financialStats);
    }

    /**
     * Get asset statistics
     */
    public function assets(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $assetStats = $this->dashboardService->getAssetStats($client);

        return response()->json($assetStats);
    }

    /**
     * Get project statistics
     */
    public function projects(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $projectStats = $this->dashboardService->getProjectStats($client);

        return response()->json($projectStats);
    }

    /**
     * Get recent activity
     */
    public function activity(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $activity = $this->dashboardService->getRecentActivity($client);

        return response()->json($activity);
    }

    /**
     * Get upcoming events
     */
    public function events(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $events = $this->dashboardService->getUpcomingEvents($client);

        return response()->json($events);
    }

    /**
     * Get expiring items
     */
    public function expiring(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $expiringItems = $this->dashboardService->getExpiringItems($client);

        return response()->json($expiringItems);
    }

    /**
     * Refresh dashboard cache
     */
    public function refresh(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        // Clear cache for this client's dashboard
        $cacheKey = "client_dashboard_{$client->id}_" . Auth::id();
        Cache::forget($cacheKey);

        // Get fresh data
        $dashboardData = $this->dashboardService->getDashboardData($client);

        return response()->json([
            'message' => 'Dashboard refreshed successfully',
            'data' => $dashboardData
        ]);
    }

    /**
     * Export dashboard data
     */
    public function export(Client $client, Request $request)
    {
        $this->authorize('view', $client);
        $this->authorize('export', $client);

        $format = $request->get('format', 'pdf');
        $dashboardData = $this->dashboardService->getDashboardData($client);

        switch ($format) {
            case 'pdf':
                return $this->exportPdf($client, $dashboardData);
            case 'excel':
                return $this->exportExcel($client, $dashboardData);
            case 'csv':
                return $this->exportCsv($client, $dashboardData);
            default:
                return response()->json(['error' => 'Invalid export format'], 400);
        }
    }

    /**
     * Export dashboard as PDF
     */
    protected function exportPdf(Client $client, array $dashboardData)
    {
        // TODO: Implement PDF export using DomPDF or Spatie PDF
        $pdf = \PDF::loadView('exports.client-dashboard', [
            'client' => $client,
            'data' => $dashboardData
        ]);

        return $pdf->download("client-dashboard-{$client->id}.pdf");
    }

    /**
     * Export dashboard as Excel
     */
    protected function exportExcel(Client $client, array $dashboardData)
    {
        // TODO: Implement Excel export using Maatwebsite Excel
        return \Excel::download(
            new \App\Exports\ClientDashboardExport($client, $dashboardData),
            "client-dashboard-{$client->id}.xlsx"
        );
    }

    /**
     * Export dashboard as CSV
     */
    protected function exportCsv(Client $client, array $dashboardData)
    {
        $filename = "client-dashboard-{$client->id}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($dashboardData) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, ['Metric', 'Value']);
            
            // Write overview data
            fputcsv($file, ['Client ID', $dashboardData['overview']['id']]);
            fputcsv($file, ['Client Name', $dashboardData['overview']['name']]);
            fputcsv($file, ['Status', $dashboardData['overview']['status']]);
            fputcsv($file, ['Total Contacts', $dashboardData['overview']['total_contacts']]);
            fputcsv($file, ['Total Locations', $dashboardData['overview']['total_locations']]);
            
            // Write ticket stats
            fputcsv($file, ['Total Tickets', $dashboardData['tickets']['total']]);
            fputcsv($file, ['Open Tickets', $dashboardData['tickets']['open']]);
            fputcsv($file, ['Closed Tickets', $dashboardData['tickets']['closed']]);
            fputcsv($file, ['SLA Compliance', $dashboardData['tickets']['sla_compliance'] . '%']);
            
            // Write financial stats
            fputcsv($file, ['Total Revenue', $dashboardData['financial']['total_revenue']]);
            fputcsv($file, ['Outstanding Balance', $dashboardData['financial']['outstanding_balance']]);
            fputcsv($file, ['MRR', $dashboardData['financial']['mrr']]);
            fputcsv($file, ['ARR', $dashboardData['financial']['arr']]);
            
            // Write asset stats
            fputcsv($file, ['Total Assets', $dashboardData['assets']['total_assets']]);
            fputcsv($file, ['Active Assets', $dashboardData['assets']['active_assets']]);
            fputcsv($file, ['Total Asset Value', $dashboardData['assets']['total_value']]);
            
            // Write project stats
            fputcsv($file, ['Total Projects', $dashboardData['projects']['total_projects']]);
            fputcsv($file, ['Active Projects', $dashboardData['projects']['active_projects']]);
            fputcsv($file, ['Completion Rate', $dashboardData['projects']['completion_rate'] . '%']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}