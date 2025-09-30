<?php

namespace App\Domains\Financial\Services;

use App\Domains\Financial\Services\VoIPTaxReportingService;
use App\Models\Company;
use App\Models\TaxJurisdiction;
use App\Models\TaxExemption;
use App\Models\VoIPTaxRate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * VoIP Tax Scheduled Report Service
 * 
 * Handles automated report generation, compliance monitoring,
 * and scheduled delivery of tax reports.
 */
class VoIPTaxScheduledReportService
{
    protected VoIPTaxReportingService $reportingService;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'storage_disk' => 'local',
            'reports_path' => 'voip-tax-reports',
            'retention_days' => 90,
            'email_notifications' => true,
            'compression' => false,
        ], $config);
    }

    /**
     * Generate and distribute monthly compliance reports for all companies.
     */
    public function generateMonthlyComplianceReports(?Carbon $month = null): array
    {
        $month = $month ?? Carbon::now()->subMonth();
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        
        $results = [];
        
        $companies = Company::active()->with('users')->get();
        
        foreach ($companies as $company) {
            try {
                $results[$company->id] = $this->generateCompanyMonthlyReport($company, $startDate, $endDate);
            } catch (\Exception $e) {
                Log::error('Failed to generate monthly report for company', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'month' => $month->format('Y-m'),
                    'error' => $e->getMessage(),
                ]);
                
                $results[$company->id] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Generate comprehensive monthly report for a specific company.
     */
    public function generateCompanyMonthlyReport(Company $company, Carbon $startDate, Carbon $endDate): array
    {
        $this->reportingService = new VoIPTaxReportingService($company->id);
        
        $reportData = [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'contact_email' => $company->email,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'month_name' => $startDate->format('F Y'),
            ],
            'generated_at' => now()->toISOString(),
            'reports' => [],
            'compliance_status' => [],
            'action_items' => [],
        ];

        // Generate all standard reports
        $reportData['reports']['tax_summary'] = $this->reportingService->generateTaxSummaryReport($startDate, $endDate);
        $reportData['reports']['service_type_analysis'] = $this->reportingService->generateServiceTypeAnalysis($startDate, $endDate);
        $reportData['reports']['exemption_usage'] = $this->reportingService->generateExemptionReport($startDate, $endDate);
        
        // Generate jurisdiction-specific reports for active jurisdictions
        $jurisdictions = TaxJurisdiction::where('company_id', $company->id)
            ->active()
            ->whereHas('taxRates', function ($query) {
                $query->active();
            })
            ->get();

        foreach ($jurisdictions as $jurisdiction) {
            $reportData['reports']['jurisdictions'][$jurisdiction->id] = 
                $this->reportingService->generateJurisdictionReport($jurisdiction->id, $startDate, $endDate);
        }

        // Compliance status assessment
        $reportData['compliance_status'] = $this->assessComplianceStatus($company);
        
        // Generate action items
        $reportData['action_items'] = $this->generateActionItems($company, $reportData['compliance_status']);
        
        // Save report to storage
        $filename = $this->saveReportToStorage($company, $reportData, $startDate);
        
        // Send notifications if enabled
        if ($this->config['email_notifications']) {
            $this->sendReportNotifications($company, $reportData, $filename);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'report_summary' => [
                'total_tax_collected' => $reportData['reports']['tax_summary']['summary']['total_tax_collected'],
                'invoice_count' => $reportData['reports']['tax_summary']['summary']['invoice_count'],
                'compliance_score' => $reportData['compliance_status']['overall_score'],
                'action_items_count' => count($reportData['action_items']),
            ],
        ];
    }

    /**
     * Generate quarterly filing assistance reports.
     */
    public function generateQuarterlyFilingReports(?Carbon $quarter = null): array
    {
        $quarter = $quarter ?? Carbon::now()->subQuarter();
        $startDate = $quarter->copy()->startOfQuarter();
        $endDate = $quarter->copy()->endOfQuarter();
        
        $results = [];
        $companies = Company::active()->get();
        
        foreach ($companies as $company) {
            try {
                $this->reportingService = new VoIPTaxReportingService($company->id);
                
                // Get jurisdictions with quarterly filing requirements
                $jurisdictions = TaxJurisdiction::where('company_id', $company->id)
                    ->active()
                    ->where('filing_requirements->frequency', 'quarterly')
                    ->get();

                $filingReports = [];
                foreach ($jurisdictions as $jurisdiction) {
                    $jurisdictionReport = $this->reportingService->generateJurisdictionReport(
                        $jurisdiction->id, 
                        $startDate, 
                        $endDate
                    );
                    
                    $filingReports[$jurisdiction->id] = [
                        'jurisdiction' => $jurisdiction->name,
                        'authority' => $jurisdiction->authority_name,
                        'filing_due_date' => $this->calculateFilingDueDate($jurisdiction, $endDate),
                        'tax_collected' => $jurisdictionReport['collections']['total_tax_amount'],
                        'return_data' => $this->prepareFilingData($jurisdiction, $jurisdictionReport),
                        'forms_required' => $jurisdiction->filing_requirements['forms'] ?? [],
                    ];
                }
                
                $results[$company->id] = [
                    'success' => true,
                    'quarter' => $quarter->format('Y-Q'),
                    'filing_reports' => $filingReports,
                    'generated_at' => now()->toISOString(),
                ];
                
            } catch (\Exception $e) {
                Log::error('Failed to generate quarterly filing report', [
                    'company_id' => $company->id,
                    'quarter' => $quarter->format('Y-Q'),
                    'error' => $e->getMessage(),
                ]);
                
                $results[$company->id] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Monitor compliance status and send alerts.
     */
    public function monitorComplianceAlerts(): array
    {
        $alerts = [];
        $companies = Company::active()->get();
        
        foreach ($companies as $company) {
            $companyAlerts = [];
            
            // Check for expired exemptions
            $expiredExemptions = TaxExemption::where('company_id', $company->id)
                ->where('status', TaxExemption::STATUS_EXPIRED)
                ->orWhere(function ($query) {
                    $query->whereNotNull('expiry_date')
                          ->where('expiry_date', '<', now());
                })
                ->with('client')
                ->get();

            if ($expiredExemptions->count() > 0) {
                $companyAlerts[] = [
                    'type' => 'expired_exemptions',
                    'severity' => 'high',
                    'count' => $expiredExemptions->count(),
                    'message' => "You have {$expiredExemptions->count()} expired tax exemption certificates that need renewal",
                    'details' => $expiredExemptions->map(function ($exemption) {
                        return [
                            'client_name' => $exemption->client->name ?? 'Unknown',
                            'exemption_name' => $exemption->exemption_name,
                            'expired_date' => $exemption->expiry_date?->toDateString(),
                        ];
                    })->toArray(),
                ];
            }

            // Check for exemptions expiring soon
            $expiringSoon = TaxExemption::where('company_id', $company->id)
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [now(), now()->addDays(30)])
                ->with('client')
                ->get();

            if ($expiringSoon->count() > 0) {
                $companyAlerts[] = [
                    'type' => 'expiring_exemptions',
                    'severity' => 'medium',
                    'count' => $expiringSoon->count(),
                    'message' => "You have {$expiringSoon->count()} tax exemption certificates expiring within 30 days",
                    'details' => $expiringSoon->map(function ($exemption) {
                        return [
                            'client_name' => $exemption->client->name ?? 'Unknown',
                            'exemption_name' => $exemption->exemption_name,
                            'expires_date' => $exemption->expiry_date->toDateString(),
                            'days_until_expiry' => now()->diffInDays($exemption->expiry_date),
                        ];
                    })->toArray(),
                ];
            }

            // Check for missing tax rates
            $jurisdictions = TaxJurisdiction::where('company_id', $company->id)->active()->count();
            $activeRates = VoIPTaxRate::where('company_id', $company->id)->active()->count();

            if ($jurisdictions > 0 && $activeRates === 0) {
                $companyAlerts[] = [
                    'type' => 'missing_tax_rates',
                    'severity' => 'critical',
                    'message' => 'No active tax rates configured despite having active jurisdictions',
                    'action_required' => 'Configure tax rates for your jurisdictions immediately',
                ];
            }

            // Check for rate updates needed
            $outdatedRates = VoIPTaxRate::where('company_id', $company->id)
                ->active()
                ->where('last_updated', '<', now()->subDays(90))
                ->count();

            if ($outdatedRates > 0) {
                $companyAlerts[] = [
                    'type' => 'outdated_rates',
                    'severity' => 'medium',
                    'count' => $outdatedRates,
                    'message' => "You have {$outdatedRates} tax rates that haven't been updated in over 90 days",
                    'recommendation' => 'Review and update tax rates to ensure compliance with current regulations',
                ];
            }

            if (!empty($companyAlerts)) {
                $alerts[$company->id] = [
                    'company_name' => $company->name,
                    'alert_count' => count($companyAlerts),
                    'alerts' => $companyAlerts,
                ];

                // Send immediate notifications for critical alerts
                $criticalAlerts = array_filter($companyAlerts, fn($alert) => $alert['severity'] === 'critical');
                if (!empty($criticalAlerts) && $this->config['email_notifications']) {
                    $this->sendCriticalAlertNotification($company, $criticalAlerts);
                }
            }
        }
        
        return $alerts;
    }

    /**
     * Clean up old reports based on retention policy.
     */
    public function cleanupOldReports(): array
    {
        $cutoffDate = Carbon::now()->subDays($this->config['retention_days']);
        $disk = Storage::disk($this->config['storage_disk']);
        $reportPath = $this->config['reports_path'];
        
        $deletedFiles = [];
        $totalSize = 0;
        
        try {
            $files = $disk->allFiles($reportPath);
            
            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
                
                if ($lastModified->lt($cutoffDate)) {
                    $size = $disk->size($file);
                    $totalSize += $size;
                    
                    $disk->delete($file);
                    $deletedFiles[] = [
                        'file' => $file,
                        'last_modified' => $lastModified->toDateString(),
                        'size' => $size,
                    ];
                }
            }
            
            Log::info('VoIP tax reports cleanup completed', [
                'files_deleted' => count($deletedFiles),
                'total_size_freed' => $totalSize,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old VoIP tax reports', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);
        }
        
        return [
            'files_deleted' => count($deletedFiles),
            'total_size_freed' => $totalSize,
            'deleted_files' => $deletedFiles,
        ];
    }

    /**
     * Assess compliance status for a company.
     */
    protected function assessComplianceStatus(Company $company): array
    {
        $score = 100;
        $issues = [];
        
        // Check exemption certificate status
        $expiredExemptions = TaxExemption::where('company_id', $company->id)
            ->where(function ($query) {
                $query->where('status', TaxExemption::STATUS_EXPIRED)
                      ->orWhere(function ($q) {
                          $q->whereNotNull('expiry_date')
                            ->where('expiry_date', '<', now());
                      });
            })->count();

        if ($expiredExemptions > 0) {
            $score -= min(20, $expiredExemptions * 5);
            $issues[] = "{$expiredExemptions} expired exemption certificates";
        }

        // Check tax rate currency
        $outdatedRates = VoIPTaxRate::where('company_id', $company->id)
            ->active()
            ->where('last_updated', '<', now()->subDays(90))
            ->count();

        if ($outdatedRates > 0) {
            $score -= min(15, $outdatedRates * 2);
            $issues[] = "{$outdatedRates} outdated tax rates";
        }

        // Check jurisdiction coverage
        $jurisdictionsWithoutRates = TaxJurisdiction::where('company_id', $company->id)
            ->active()
            ->doesntHave('taxRates')
            ->count();

        if ($jurisdictionsWithoutRates > 0) {
            $score -= min(25, $jurisdictionsWithoutRates * 10);
            $issues[] = "{$jurisdictionsWithoutRates} jurisdictions without tax rates";
        }

        return [
            'overall_score' => max(0, $score),
            'grade' => $this->getComplianceGrade($score),
            'issues' => $issues,
            'last_assessed' => now()->toISOString(),
        ];
    }

    /**
     * Generate action items based on compliance status.
     */
    protected function generateActionItems(Company $company, array $complianceStatus): array
    {
        $actionItems = [];
        
        foreach ($complianceStatus['issues'] as $issue) {
            if (str_contains($issue, 'expired exemption')) {
                $actionItems[] = [
                    'priority' => 'high',
                    'category' => 'exemptions',
                    'title' => 'Renew Expired Tax Exemptions',
                    'description' => 'Contact clients to renew expired exemption certificates',
                    'due_date' => now()->addDays(7)->toDateString(),
                ];
            }
            
            if (str_contains($issue, 'outdated tax rates')) {
                $actionItems[] = [
                    'priority' => 'medium',
                    'category' => 'tax_rates',
                    'title' => 'Update Tax Rates',
                    'description' => 'Review and update tax rates to current regulations',
                    'due_date' => now()->addDays(14)->toDateString(),
                ];
            }
            
            if (str_contains($issue, 'jurisdictions without tax rates')) {
                $actionItems[] = [
                    'priority' => 'high',
                    'category' => 'configuration',
                    'title' => 'Configure Missing Tax Rates',
                    'description' => 'Set up tax rates for all active jurisdictions',
                    'due_date' => now()->addDays(3)->toDateString(),
                ];
            }
        }
        
        return $actionItems;
    }

    /**
     * Save report data to storage.
     */
    protected function saveReportToStorage(Company $company, array $reportData, Carbon $month): string
    {
        $filename = sprintf(
            'company_%d_monthly_report_%s.json',
            $company->id,
            $month->format('Y_m')
        );
        
        $filepath = $this->config['reports_path'] . '/' . $filename;
        $disk = Storage::disk($this->config['storage_disk']);
        
        $content = json_encode($reportData, JSON_PRETTY_PRINT);
        
        if ($this->config['compression']) {
            $content = gzcompress($content);
            $filename .= '.gz';
            $filepath .= '.gz';
        }
        
        $disk->put($filepath, $content);
        
        return $filename;
    }

    /**
     * Get compliance grade based on score.
     */
    protected function getComplianceGrade(int $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'A-',
            $score >= 80 => 'B+',
            $score >= 75 => 'B',
            $score >= 70 => 'B-',
            $score >= 65 => 'C+',
            $score >= 60 => 'C',
            $score >= 55 => 'C-',
            default => 'F',
        };
    }

    /**
     * Calculate filing due date based on jurisdiction requirements.
     */
    protected function calculateFilingDueDate(TaxJurisdiction $jurisdiction, Carbon $periodEnd): ?string
    {
        $requirements = $jurisdiction->filing_requirements;
        
        if (!$requirements || !isset($requirements['due_days_after_period'])) {
            return null;
        }
        
        return $periodEnd->copy()
            ->addDays($requirements['due_days_after_period'])
            ->toDateString();
    }

    /**
     * Prepare filing data for tax returns.
     */
    protected function prepareFilingData(TaxJurisdiction $jurisdiction, array $jurisdictionReport): array
    {
        return [
            'gross_receipts' => $jurisdictionReport['collections']['total_base_amount'],
            'taxable_receipts' => $jurisdictionReport['collections']['total_base_amount'],
            'tax_due' => $jurisdictionReport['collections']['total_tax_amount'],
            'exemptions_claimed' => $jurisdictionReport['exemptions']['total_exempted_amount'],
            'net_tax_due' => $jurisdictionReport['collections']['total_tax_amount'],
        ];
    }

    /**
     * Send report notifications (placeholder for actual email implementation).
     */
    protected function sendReportNotifications(Company $company, array $reportData, string $filename): void
    {
        // Implementation would depend on your mail system
        Log::info('Monthly tax report generated', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'report_file' => $filename,
            'period' => $reportData['period']['month_name'],
        ]);
    }

    /**
     * Send critical alert notifications (placeholder).
     */
    protected function sendCriticalAlertNotification(Company $company, array $criticalAlerts): void
    {
        Log::critical('VoIP Tax Compliance Critical Alert', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'alert_count' => count($criticalAlerts),
            'alerts' => $criticalAlerts,
        ]);
    }
}