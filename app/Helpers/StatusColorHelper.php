<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Status Color Helper
 * 
 * Provides centralized access to status and priority colors across the application.
 * All colors are Flux UI compatible color names.
 */
class StatusColorHelper
{
    /**
     * Get color for any status in any domain
     * 
     * @param string $domain Domain name (ticket, invoice, contract, etc.)
     * @param string $status Status value
     * @param string $default Default color if not found
     * @return string Flux UI color name
     */
    public static function get(string $domain, string $status, string $default = 'zinc'): string
    {
        $normalizedStatus = self::normalizeStatus($status);
        return Config::get("status-colors.statuses.{$domain}.{$normalizedStatus}", $default);
    }

    /**
     * Get priority color
     * 
     * @param string $priority Priority value
     * @param string $default Default color if not found
     * @return string Flux UI color name
     */
    public static function priority(string $priority, string $default = 'zinc'): string
    {
        $normalizedPriority = strtolower($priority);
        return Config::get("status-colors.priorities.{$normalizedPriority}", $default);
    }

    /**
     * Get semantic color
     * 
     * @param string $semantic Semantic type (success, error, warning, etc.)
     * @param string $default Default color if not found
     * @return string Flux UI color name
     */
    public static function semantic(string $semantic, string $default = 'zinc'): string
    {
        return Config::get("status-colors.semantic.{$semantic}", $default);
    }

    /**
     * Get conditional state color
     * 
     * @param string $state Conditional state (expired, expiring_soon, overdue)
     * @param string $default Default color if not found
     * @return string Flux UI color name
     */
    public static function conditional(string $state, string $default = 'zinc'): string
    {
        return Config::get("status-colors.conditional.{$state}", $default);
    }

    /**
     * Get ticket status color
     * 
     * @param string $status Ticket status
     * @return string Flux UI color name
     */
    public static function ticket(string $status): string
    {
        return self::get('ticket', $status);
    }

    /**
     * Get invoice status color
     * 
     * @param string $status Invoice status
     * @return string Flux UI color name
     */
    public static function invoice(string $status): string
    {
        return self::get('invoice', $status);
    }

    /**
     * Get contract status color
     * 
     * @param string $status Contract status
     * @return string Flux UI color name
     */
    public static function contract(string $status): string
    {
        return self::get('contract', $status);
    }

    /**
     * Get asset status color
     * 
     * @param string $status Asset status
     * @return string Flux UI color name
     */
    public static function asset(string $status): string
    {
        return self::get('asset', $status);
    }

    /**
     * Get service status color
     * 
     * @param string $status Service status
     * @return string Flux UI color name
     */
    public static function service(string $status): string
    {
        return self::get('service', $status);
    }

    /**
     * Get project status color
     * 
     * @param string $status Project status
     * @return string Flux UI color name
     */
    public static function project(string $status): string
    {
        return self::get('project', $status);
    }

    /**
     * Get lead status color
     * 
     * @param string $status Lead status
     * @return string Flux UI color name
     */
    public static function lead(string $status): string
    {
        return self::get('lead', $status);
    }

    /**
     * Get quote status color
     * 
     * @param string $status Quote status
     * @return string Flux UI color name
     */
    public static function quote(string $status): string
    {
        return self::get('quote', $status);
    }

    /**
     * Get payment status color
     * 
     * @param string $status Payment status
     * @return string Flux UI color name
     */
    public static function payment(string $status): string
    {
        return self::get('payment', $status);
    }

    /**
     * Normalize status string to snake_case for config lookup
     * 
     * Handles both "In Progress" and "in_progress" formats
     * 
     * @param string $status Status string
     * @return string Normalized status in snake_case
     */
    private static function normalizeStatus(string $status): string
    {
        // Handle both "In Progress" and "in_progress" formats
        return Str::snake(strtolower(str_replace([' ', '-'], '_', $status)));
    }

    /**
     * Get all available colors for a domain
     * 
     * @param string $domain Domain name
     * @return array<string, string> Array of status => color mappings
     */
    public static function all(string $domain): array
    {
        return Config::get("status-colors.statuses.{$domain}", []);
    }

    /**
     * Get hex color from Flux color name (for charts/visualizations)
     * 
     * @param string $fluxColor Flux UI color name
     * @return string Hex color code
     */
    public static function toHex(string $fluxColor): string
    {
        return Config::get("status-colors.hex_map.{$fluxColor}", '#71717a');
    }

    /**
     * Get workload status color based on score
     * 
     * @param int|float $score Workload score
     * @return string Flux UI color name
     */
    public static function workloadStatus($score): string
    {
        if ($score >= 50) {
            return 'red';
        }
        if ($score >= 30) {
            return 'orange';
        }
        if ($score >= 15) {
            return 'yellow';
        }
        return 'green';
    }

    /**
     * Get capacity status color based on percentage
     * 
     * @param int|float $percentage Capacity percentage
     * @return string Flux UI color name
     */
    public static function capacityStatus($percentage): string
    {
        if ($percentage >= 90) {
            return 'red';
        }
        if ($percentage >= 75) {
            return 'orange';
        }
        if ($percentage >= 50) {
            return 'yellow';
        }
        if ($percentage >= 25) {
            return 'green';
        }
        return 'blue';
    }

    /**
     * Get sentiment color based on score (-1 to 1)
     * 
     * @param float $score Sentiment score
     * @return string Hex color code
     */
    public static function sentimentColor(float $score): string
    {
        if ($score > 0.5) {
            return '#10b981'; // emerald-500 - Very positive
        }
        if ($score > 0.1) {
            return '#84cc16'; // lime-500 - Positive
        }
        if ($score > -0.1) {
            return '#64748b'; // slate-500 - Neutral
        }
        if ($score > -0.5) {
            return '#f97316'; // orange-500 - Negative
        }
        return '#ef4444'; // red-500 - Very negative
    }

    /**
     * Get performance score color
     * 
     * @param int|float $score Performance score (0-100)
     * @return string Flux UI color name
     */
    public static function performanceScore($score): string
    {
        if ($score >= 70) {
            return 'green';
        }
        if ($score >= 40) {
            return 'yellow';
        }
        return 'red';
    }
}
