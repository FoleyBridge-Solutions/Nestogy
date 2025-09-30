<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;

/**
 * Centralized dashboard data caching service
 * Prevents duplicate queries across multiple dashboard widgets
 */
class DashboardCacheService
{
    protected static array $requestCache = [];
    
    /**
     * Get aggregated invoice stats for a date range with caching
     */
    public static function getInvoiceStats(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "invoice_stats_{$companyId}_{$startDate->timestamp}_{$endDate->timestamp}";
        
        // Check request-level cache first
        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }
        
        // Check persistent cache
        $data = Cache::remember($cacheKey, 60, function() use ($companyId, $startDate, $endDate) {
            return DB::selectOne("
                SELECT 
                    SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'Sent' THEN amount ELSE 0 END) as sent_amount,
                    SUM(CASE WHEN status = 'Draft' THEN amount ELSE 0 END) as draft_amount,
                    SUM(CASE WHEN status IN ('Sent', 'Viewed', 'Partial') THEN amount ELSE 0 END) as outstanding_amount,
                    COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status = 'Sent' THEN 1 END) as sent_count,
                    COUNT(CASE WHEN status = 'Draft' THEN 1 END) as draft_count,
                    COUNT(*) as total_count,
                    AVG(amount) as average_amount,
                    SUM(amount) as total_amount
                FROM invoices
                WHERE company_id = ? 
                    AND date BETWEEN ? AND ?
                    AND archived_at IS NULL
            ", [$companyId, $startDate->toDateString(), $endDate->toDateString()]);
        });
        
        // Store in request cache
        self::$requestCache[$cacheKey] = (array) $data;
        
        return self::$requestCache[$cacheKey];
    }
    
    /**
     * Get aggregated payment stats for a date range with caching
     */
    public static function getPaymentStats(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "payment_stats_{$companyId}_{$startDate->timestamp}_{$endDate->timestamp}";
        
        // Check request-level cache first
        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }
        
        // Check persistent cache
        $data = Cache::remember($cacheKey, 60, function() use ($companyId, $startDate, $endDate) {
            return DB::selectOne("
                SELECT 
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                    COUNT(*) as total_count,
                    AVG(amount) as average_amount,
                    SUM(amount) as total_amount
                FROM payments
                WHERE company_id = ? 
                    AND payment_date BETWEEN ? AND ?
                    AND deleted_at IS NULL
            ", [$companyId, $startDate->toDateString(), $endDate->toDateString()]);
        });
        
        // Store in request cache
        self::$requestCache[$cacheKey] = (array) $data;
        
        return self::$requestCache[$cacheKey];
    }
    
    /**
     * Get client stats with caching
     */
    public static function getClientStats(int $companyId, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? Carbon::now();
        $cacheKey = "client_stats_{$companyId}_{$asOfDate->timestamp}";
        
        // Check request-level cache first
        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }
        
        // Check persistent cache
        $data = Cache::remember($cacheKey, 60, function() use ($companyId, $asOfDate) {
            return DB::selectOne("
                SELECT 
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_count,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_this_month,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_this_quarter,
                    COUNT(*) as total_count
                FROM clients
                WHERE company_id = ? 
                    AND deleted_at IS NULL
                    AND created_at <= ?
            ", [
                $asOfDate->copy()->startOfMonth(),
                $asOfDate->copy()->startOfQuarter(),
                $companyId,
                $asOfDate
            ]);
        });
        
        // Store in request cache
        self::$requestCache[$cacheKey] = (array) $data;
        
        return self::$requestCache[$cacheKey];
    }
    
    /**
     * Get daily revenue/invoice data for charts
     */
    public static function getDailyChartData(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "daily_chart_{$companyId}_{$startDate->timestamp}_{$endDate->timestamp}";
        
        // Check request-level cache first
        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }
        
        // Check persistent cache
        $data = Cache::remember($cacheKey, 300, function() use ($companyId, $startDate, $endDate) {
            // Get invoice data
            $invoices = DB::select("
                SELECT 
                    DATE(date) as date,
                    SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
                    SUM(amount) as total_amount,
                    COUNT(*) as count
                FROM invoices
                WHERE company_id = ? 
                    AND date BETWEEN ? AND ?
                    AND archived_at IS NULL
                GROUP BY DATE(date)
            ", [$companyId, $startDate->toDateString(), $endDate->toDateString()]);
            
            // Get payment data
            $payments = DB::select("
                SELECT 
                    DATE(payment_date) as date,
                    SUM(amount) as amount,
                    COUNT(*) as count
                FROM payments
                WHERE company_id = ? 
                    AND payment_date BETWEEN ? AND ?
                    AND status = 'completed'
                    AND deleted_at IS NULL
                GROUP BY DATE(payment_date)
            ", [$companyId, $startDate->toDateString(), $endDate->toDateString()]);
            
            // Convert to keyed arrays
            $invoicesByDate = [];
            foreach ($invoices as $row) {
                $invoicesByDate[$row->date] = $row;
            }
            
            $paymentsByDate = [];
            foreach ($payments as $row) {
                $paymentsByDate[$row->date] = $row;
            }
            
            return [
                'invoices' => $invoicesByDate,
                'payments' => $paymentsByDate
            ];
        });
        
        // Store in request cache
        self::$requestCache[$cacheKey] = $data;
        
        return self::$requestCache[$cacheKey];
    }
    
    /**
     * Clear cache for a specific company
     */
    public static function clearCompanyCache(int $companyId): void
    {
        // Clear all cached keys for this company
        $pattern = "*_{$companyId}_*";
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear request cache
        self::$requestCache = [];
    }
    
    /**
     * Get or calculate invoice metrics for multiple date ranges at once
     */
    public static function getMultiPeriodInvoiceStats(int $companyId, array $periods): array
    {
        $results = [];
        
        // Build a single query for all periods
        $caseClauses = [];
        $params = [$companyId];
        
        foreach ($periods as $key => $period) {
            $startDate = $period['start']->toDateString();
            $endDate = $period['end']->toDateString();
            
            $caseClauses[] = "
                SUM(CASE WHEN date BETWEEN '{$startDate}' AND '{$endDate}' AND status = 'Paid' THEN amount ELSE 0 END) as {$key}_paid,
                SUM(CASE WHEN date BETWEEN '{$startDate}' AND '{$endDate}' THEN amount ELSE 0 END) as {$key}_total,
                COUNT(CASE WHEN date BETWEEN '{$startDate}' AND '{$endDate}' THEN 1 END) as {$key}_count,
                AVG(CASE WHEN date BETWEEN '{$startDate}' AND '{$endDate}' THEN amount END) as {$key}_avg
            ";
        }
        
        $query = "
            SELECT " . implode(',', $caseClauses) . "
            FROM invoices
            WHERE company_id = ?
                AND archived_at IS NULL
        ";
        
        $data = DB::selectOne($query, $params);
        
        // Parse results
        foreach ($periods as $key => $period) {
            $results[$key] = [
                'paid_amount' => $data->{$key . '_paid'} ?? 0,
                'total_amount' => $data->{$key . '_total'} ?? 0,
                'count' => $data->{$key . '_count'} ?? 0,
                'average' => $data->{$key . '_avg'} ?? 0,
            ];
        }
        
        return $results;
    }
}