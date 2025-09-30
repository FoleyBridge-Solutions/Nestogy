<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Trait to optimize dashboard queries by batching similar queries
 */
trait DashboardQueryOptimizer
{
    protected static array $queryBatch = [];
    protected static bool $batchMode = false;
    
    /**
     * Start batching queries
     */
    public static function startBatch(): void
    {
        self::$batchMode = true;
        self::$queryBatch = [];
    }
    
    /**
     * Execute batched queries
     */
    public static function executeBatch(): array
    {
        self::$batchMode = false;
        $results = [];
        
        // Group queries by type
        $invoiceQueries = [];
        $paymentQueries = [];
        
        foreach (self::$queryBatch as $query) {
            if ($query['type'] === 'invoice') {
                $invoiceQueries[] = $query;
            } elseif ($query['type'] === 'payment') {
                $paymentQueries[] = $query;
            }
        }
        
        // Execute invoice queries in bulk
        if (!empty($invoiceQueries)) {
            $results = array_merge($results, self::executeBulkInvoiceQueries($invoiceQueries));
        }
        
        // Execute payment queries in bulk
        if (!empty($paymentQueries)) {
            $results = array_merge($results, self::executeBulkPaymentQueries($paymentQueries));
        }
        
        // Clear batch
        self::$queryBatch = [];
        
        return $results;
    }
    
    /**
     * Add query to batch or execute immediately
     */
    protected function batchOrExecute(string $type, array $params)
    {
        if (self::$batchMode) {
            $key = md5(json_encode($params));
            self::$queryBatch[$key] = array_merge(['type' => $type, 'key' => $key], $params);
            return $key; // Return key for later retrieval
        }
        
        // Execute immediately if not in batch mode
        return $this->executeQuery($type, $params);
    }
    
    /**
     * Execute bulk invoice queries using CASE statements
     */
    protected static function executeBulkInvoiceQueries(array $queries): array
    {
        if (empty($queries)) {
            return [];
        }
        
        // Build a single query with multiple CASE statements
        $companyId = $queries[0]['company_id'] ?? null;
        if (!$companyId) {
            return [];
        }
        
        $selectClauses = [];
        $results = [];
        
        foreach ($queries as $query) {
            $key = $query['key'];
            $startDate = $query['start_date'] ?? null;
            $endDate = $query['end_date'] ?? null;
            $status = $query['status'] ?? null;
            
            if ($startDate && $endDate) {
                $dateCondition = "date BETWEEN '{$startDate}' AND '{$endDate}'";
                
                if ($status) {
                    $selectClauses[] = "SUM(CASE WHEN {$dateCondition} AND status = '{$status}' THEN amount ELSE 0 END) as sum_{$key}";
                    $selectClauses[] = "COUNT(CASE WHEN {$dateCondition} AND status = '{$status}' THEN 1 END) as count_{$key}";
                } else {
                    $selectClauses[] = "SUM(CASE WHEN {$dateCondition} THEN amount ELSE 0 END) as sum_{$key}";
                    $selectClauses[] = "COUNT(CASE WHEN {$dateCondition} THEN 1 END) as count_{$key}";
                    $selectClauses[] = "AVG(CASE WHEN {$dateCondition} THEN amount END) as avg_{$key}";
                }
            }
        }
        
        if (empty($selectClauses)) {
            return [];
        }
        
        $sql = "SELECT " . implode(', ', $selectClauses) . " 
                FROM invoices 
                WHERE company_id = ? AND archived_at IS NULL";
        
        $data = DB::selectOne($sql, [$companyId]);
        
        // Parse results
        foreach ($queries as $query) {
            $key = $query['key'];
            $results[$key] = [
                'sum' => $data->{"sum_{$key}"} ?? 0,
                'count' => $data->{"count_{$key}"} ?? 0,
                'avg' => $data->{"avg_{$key}"} ?? 0,
            ];
        }
        
        return $results;
    }
    
    /**
     * Execute bulk payment queries using CASE statements
     */
    protected static function executeBulkPaymentQueries(array $queries): array
    {
        if (empty($queries)) {
            return [];
        }
        
        // Similar implementation for payments
        $companyId = $queries[0]['company_id'] ?? null;
        if (!$companyId) {
            return [];
        }
        
        $selectClauses = [];
        $results = [];
        
        foreach ($queries as $query) {
            $key = $query['key'];
            $startDate = $query['start_date'] ?? null;
            $endDate = $query['end_date'] ?? null;
            $status = $query['status'] ?? 'completed';
            
            if ($startDate && $endDate) {
                $dateCondition = "payment_date BETWEEN '{$startDate}' AND '{$endDate}'";
                $selectClauses[] = "SUM(CASE WHEN {$dateCondition} AND status = '{$status}' THEN amount ELSE 0 END) as sum_{$key}";
                $selectClauses[] = "COUNT(CASE WHEN {$dateCondition} AND status = '{$status}' THEN 1 END) as count_{$key}";
            }
        }
        
        if (empty($selectClauses)) {
            return [];
        }
        
        $sql = "SELECT " . implode(', ', $selectClauses) . " 
                FROM payments 
                WHERE company_id = ? AND deleted_at IS NULL";
        
        $data = DB::selectOne($sql, [$companyId]);
        
        // Parse results
        foreach ($queries as $query) {
            $key = $query['key'];
            $results[$key] = [
                'sum' => $data->{"sum_{$key}"} ?? 0,
                'count' => $data->{"count_{$key}"} ?? 0,
            ];
        }
        
        return $results;
    }
}