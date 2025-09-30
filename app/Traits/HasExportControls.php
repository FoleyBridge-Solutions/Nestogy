<?php

namespace App\Traits;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * HasExportControls Trait
 *
 * Provides comprehensive export permission controls and security features.
 * Handles different types of exports with appropriate authorization checks.
 */
trait HasExportControls
{
    /**
     * Authorize general export operation.
     */
    protected function authorizeExport(string $domain, array $options = []): void
    {
        $exportType = $options['type'] ?? 'basic';
        $format = $options['format'] ?? 'csv';

        // Basic export permission check
        if (! auth()->user()->hasPermission("{$domain}.export")) {
            $this->logExportAttempt($domain, 'denied', 'Missing basic export permission');
            abort(403, "Insufficient permissions to export {$domain} data");
        }

        // Check format-specific permissions
        $this->checkFormatPermissions($format);

        // Apply export type specific checks
        $this->applyExportTypeChecks($exportType, $domain);

        // Rate limiting for exports
        $this->applyExportRateLimit($domain);

        // Log successful authorization
        $this->logExportAttempt($domain, 'authorized', 'Export authorized successfully');
    }

    /**
     * Check permissions for specific export formats.
     */
    protected function checkFormatPermissions(string $format): void
    {
        $restrictedFormats = ['pdf', 'excel', 'xml'];

        if (in_array($format, $restrictedFormats)) {
            if (! auth()->user()->hasAnyPermission(['reports.export', 'system.settings.manage'])) {
                abort(403, "Insufficient permissions to export {$format} format");
            }
        }
    }

    /**
     * Apply export type specific authorization checks.
     */
    protected function applyExportTypeChecks(string $exportType, string $domain): void
    {
        switch ($exportType) {
            case 'sensitive':
                $this->authorizeSensitiveExport($domain);
                break;

            case 'financial':
                $this->authorizeFinancialExport();
                break;

            case 'personal':
                $this->authorizePersonalDataExport();
                break;

            case 'bulk':
                $this->authorizeBulkExport($domain);
                break;

            case 'scheduled':
                $this->authorizeScheduledExport();
                break;

            case 'audit':
                $this->authorizeAuditExport();
                break;
        }
    }

    /**
     * Authorize sensitive data export.
     */
    protected function authorizeSensitiveExport(string $domain): void
    {
        if (! Gate::allows('export-sensitive-data')) {
            abort(403, 'Insufficient permissions to export sensitive data');
        }

        // Additional checks for specific domains
        $sensitiveDomains = ['users', 'financial', 'clients'];

        if (in_array($domain, $sensitiveDomains)) {
            if (! auth()->user()->hasPermission("{$domain}.manage") && ! auth()->user()->isAdmin()) {
                abort(403, "Elevated permissions required for sensitive {$domain} export");
            }
        }
    }

    /**
     * Authorize financial data export.
     */
    protected function authorizeFinancialExport(): void
    {
        if (! auth()->user()->hasAnyPermission(['financial.export', 'financial.manage'])) {
            abort(403, 'Insufficient permissions to export financial data');
        }

        if (! Gate::allows('export-financial-data')) {
            abort(403, 'Financial export permissions denied');
        }
    }

    /**
     * Authorize personal data export (GDPR compliance).
     */
    protected function authorizePersonalDataExport(): void
    {
        if (! auth()->user()->hasAnyPermission(['users.export', 'users.manage'])) {
            abort(403, 'Insufficient permissions to export personal data');
        }

        // Special logging for GDPR compliance
        $this->logPersonalDataAccess('export');
    }

    /**
     * Authorize bulk export operations.
     */
    protected function authorizeBulkExport(string $domain): void
    {
        if (! Gate::allows('bulk-export')) {
            abort(403, 'Insufficient permissions for bulk export operations');
        }

        // Additional check for large dataset exports
        if (! auth()->user()->hasPermission('reports.export')) {
            abort(403, 'Reports export permission required for bulk operations');
        }
    }

    /**
     * Authorize scheduled export operations.
     */
    protected function authorizeScheduledExport(): void
    {
        if (! Gate::allows('scheduled-exports')) {
            abort(403, 'Insufficient permissions to schedule exports');
        }

        if (! auth()->user()->hasPermission('reports.export')) {
            abort(403, 'Reports export permission required for scheduling');
        }
    }

    /**
     * Authorize audit log exports.
     */
    protected function authorizeAuditExport(): void
    {
        if (! auth()->user()->hasPermission('system.logs.view')) {
            abort(403, 'Insufficient permissions to export audit data');
        }

        if (! auth()->user()->isAdmin()) {
            abort(403, 'Administrator access required for audit exports');
        }
    }

    /**
     * Apply rate limiting for export operations.
     */
    protected function applyExportRateLimit(string $domain): void
    {
        $key = "export_rate_limit:{$domain}:".auth()->id();
        $maxExports = config('app.max_exports_per_hour', 10);

        if (cache()->has($key)) {
            $count = cache()->get($key, 0);

            if ($count >= $maxExports) {
                $this->logExportAttempt($domain, 'rate_limited', 'Export rate limit exceeded');
                abort(429, "Export rate limit exceeded. Maximum {$maxExports} exports per hour allowed.");
            }

            cache()->put($key, $count + 1, now()->addHour());
        } else {
            cache()->put($key, 1, now()->addHour());
        }
    }

    /**
     * Filter exportable data based on permissions.
     */
    protected function filterExportableData($query, string $domain, array $options = []): mixed
    {
        $user = auth()->user();

        // Apply company scoping
        if (method_exists($query->getModel(), 'getAttribute')) {
            $query = $query->where('company_id', $user->company_id);
        }

        // Apply role-based filtering
        switch ($domain) {
            case 'users':
                return $this->filterUserExportData($query, $options);

            case 'financial':
                return $this->filterFinancialExportData($query, $options);

            case 'clients':
                return $this->filterClientExportData($query, $options);

            case 'projects':
                return $this->filterProjectExportData($query, $options);

            default:
                return $this->filterGeneralExportData($query, $options);
        }
    }

    /**
     * Filter user export data based on permissions.
     */
    protected function filterUserExportData($query, array $options)
    {
        $user = auth()->user();

        // Non-admins can only export basic user info
        if (! $user->hasPermission('users.manage')) {
            $query = $query->select(['id', 'name', 'email', 'created_at']);
        }

        // Exclude sensitive fields unless specifically authorized
        if (! $user->hasPermission('system.permissions.manage')) {
            // This would exclude fields like password hashes, tokens, etc.
            $hiddenFields = ['password', 'remember_token', 'api_token'];
            foreach ($hiddenFields as $field) {
                $query = $query->makeHidden($field);
            }
        }

        return $query;
    }

    /**
     * Filter financial export data based on permissions.
     */
    protected function filterFinancialExportData($query, array $options)
    {
        $user = auth()->user();

        // Only users with financial permissions can export detailed data
        if (! $user->hasPermission('financial.manage')) {
            // Exclude sensitive financial details
            $query = $query->select(['id', 'amount', 'date', 'description', 'status']);
        }

        return $query;
    }

    /**
     * Filter client export data based on permissions.
     */
    protected function filterClientExportData($query, array $options)
    {
        $user = auth()->user();

        // Apply visibility filters based on user role
        if (! $user->hasPermission('clients.manage')) {
            // Hide internal notes and sensitive client information
            $query = $query->makeHidden(['internal_notes', 'credit_limit']);
        }

        return $query;
    }

    /**
     * Filter project export data based on permissions.
     */
    protected function filterProjectExportData($query, array $options)
    {
        $user = auth()->user();

        // Only show projects user has access to
        if (! $user->hasPermission('projects.manage')) {
            $query = $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('user_id', $user->id);
                    });
            });
        }

        return $query;
    }

    /**
     * Filter general export data.
     */
    protected function filterGeneralExportData($query, array $options)
    {
        // Apply standard company scoping
        if (method_exists($query->getModel(), 'getAttribute')) {
            $query = $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }

    /**
     * Log export attempts for audit trail.
     */
    protected function logExportAttempt(string $domain, string $status, string $message): void
    {
        Log::info('Export Attempt', [
            'user_id' => auth()->id(),
            'domain' => $domain,
            'status' => $status,
            'message' => $message,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log personal data access for GDPR compliance.
     */
    protected function logPersonalDataAccess(string $action): void
    {
        Log::info('Personal Data Access', [
            'user_id' => auth()->id(),
            'action' => $action,
            'purpose' => 'data_export',
            'legal_basis' => 'legitimate_interest',
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Generate export filename with security considerations.
     */
    protected function generateSecureFilename(string $domain, string $format, array $options = []): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;

        $filename = "{$domain}_export_{$companyId}_{$userId}_{$timestamp}";

        if (isset($options['suffix'])) {
            $filename .= "_{$options['suffix']}";
        }

        return "{$filename}.{$format}";
    }

    /**
     * Apply export size limitations.
     */
    protected function checkExportSizeLimits($query, string $domain): void
    {
        $maxRecords = config("export.limits.{$domain}", 10000);
        $count = $query->count();

        if ($count > $maxRecords) {
            abort(413, "Export size limit exceeded. Maximum {$maxRecords} records allowed for {$domain} exports.");
        }
    }

    /**
     * Check export time window restrictions.
     */
    protected function checkExportTimeWindow(string $domain): void
    {
        $allowedHours = config('export.allowed_hours', [9, 17]); // 9 AM to 5 PM
        $currentHour = now()->hour;

        if ($currentHour < $allowedHours[0] || $currentHour > $allowedHours[1]) {
            abort(403, 'Exports are only allowed during business hours (9 AM - 5 PM)');
        }
    }
}
