<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Asset;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get dashboard statistics
        $stats = $this->getDashboardStats($companyId);
        
        // Get recent activities
        $recentTickets = $this->getRecentTickets($companyId);
        $recentInvoices = $this->getRecentInvoices($companyId);
        $upcomingTasks = $this->getUpcomingTasks($companyId);
        
        // Get chart data
        $ticketChartData = $this->getTicketChartData($companyId);
        $revenueChartData = $this->getRevenueChartData($companyId);

        return view('dashboard', compact(
            'stats',
            'recentTickets',
            'recentInvoices',
            'upcomingTasks',
            'ticketChartData',
            'revenueChartData'
        ))->with([
            'recent_tickets' => $recentTickets,
            'recent_invoices' => $recentInvoices,
            'upcoming_tasks' => $upcomingTasks,
            'chart_data' => [
                'revenue' => $revenueChartData,
                'tickets' => $ticketChartData
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($companyId)
    {
        return [
            'total_clients' => Client::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->count(),
            
            'open_tickets' => Ticket::where('company_id', $companyId)
                ->whereIn('status', ['Open', 'In Progress', 'Waiting'])
                ->count(),
            
            'overdue_invoices' => Invoice::where('company_id', $companyId)
                ->where('status', 'Sent')
                ->where('due_date', '<', now())
                ->count(),
            
            'total_assets' => Asset::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->count(),
            
            'monthly_revenue' => Invoice::where('company_id', $companyId)
                ->where('status', 'Paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            
            'pending_invoices_amount' => Invoice::where('company_id', $companyId)
                ->whereIn('status', ['Draft', 'Sent'])
                ->sum('amount'),
        ];
    }

    /**
     * Get recent tickets
     */
    private function getRecentTickets($companyId, $limit = 10)
    {
        return Ticket::with(['client', 'contact', 'assignee'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent invoices
     */
    private function getRecentInvoices($companyId, $limit = 10)
    {
        return Invoice::with('client')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upcoming tasks/scheduled tickets
     */
    private function getUpcomingTasks($companyId, $limit = 10)
    {
        return Ticket::with(['client', 'contact'])
            ->where('company_id', $companyId)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get ticket chart data for the last 30 days
     */
    private function getTicketChartData($companyId)
    {
        $tickets = Ticket::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $tickets->pluck('date')->toArray(),
            'data' => $tickets->pluck('count')->toArray(),
        ];
    }

    /**
     * Get revenue chart data for the last 12 months
     */
    private function getRevenueChartData($companyId)
    {
        $revenue = Invoice::where('company_id', $companyId)
            ->where('status', 'Paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $revenue->map(function ($item) {
                return date('M Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            })->toArray(),
            'data' => $revenue->pluck('total')->toArray(),
        ];
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getData(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $type = $request->get('type');

        switch ($type) {
            case 'stats':
                return response()->json($this->getDashboardStats($companyId));
            
            case 'recent_tickets':
                return response()->json($this->getRecentTickets($companyId));
            
            case 'recent_invoices':
                return response()->json($this->getRecentInvoices($companyId));
            
            case 'ticket_chart':
                return response()->json($this->getTicketChartData($companyId));
            
            case 'revenue_chart':
                return response()->json($this->getRevenueChartData($companyId));
            
            default:
                return response()->json(['error' => 'Invalid data type'], 400);
        }
    }

    /**
     * Get notifications for the current user
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        
        $notifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('read_at', null)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, $id)
    {
        $user = Auth::user();
        
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}