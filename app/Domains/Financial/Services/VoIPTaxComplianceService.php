<?php

namespace App\Domains\Financial\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;
use App\Models\VoIPTaxRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxRateHistory;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * VoIP Tax Compliance Service
 * 
 * Handles compliance reporting, audit trails, and regulatory requirements
 * for VoIP telecommunications taxation.
 */
class VoIPTaxComplianceService
{
    protected int $companyId;
    protected array $config;

    public function __construct(int $companyId, array $config = [])
    {
        $this->companyId = $companyId;
        $this->config = array_merge([
            'retention_years' => 7,
            'export_format' => 'json',
            'storage_disk' => 'local',
        ], $config);
    }

    /**
     * Generate comprehensive audit trail for a period.
     */
    public function generateAuditTrail(Carbon $startDate, Carbon $endDate): array
    {
        $auditTrail = [
            'company_id' => $this->companyId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'tax_calculations' => [],
            'exemptions_used' => [],
            'rate_changes' => [],
            'invoices_processed' => [],
            'quotes_processed' => [],
            'summary' => []
        ];

        // Get all invoices with VoIP services in the period
        $invoices = Invoice::where('company_id', $this->companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('items', function ($query) {
                $query->whereNotNull('service_type');
            })
            ->with(['client', 'items' => function ($query) {
                $query->whereNotNull('service_type');
            }])
            ->get();

        foreach ($invoices as $invoice) {
            $auditTrail['invoices_processed'][] = [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->getFullNumber(),
                'client_name' => $invoice->client->name ?? 'Unknown',
                'date' => $invoice->date->toDateString(),
                'service_address' => $invoice->getServiceAddress(),
                'voip_items' => $invoice->voipItems->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'service_type' => $item->service_type,
                        'base_amount' => $item->subtotal - $item->discount,
                        'tax_amount' => $item->tax,
                        'line_count' => $item->line_count,
                        'minutes' => $item->minutes,
                        'tax_breakdown' => $item->voip_tax_data['tax_breakdown'] ?? [],
                        'exemptions_applied' => $item->voip_tax_data['exemptions_applied'] ?? [],
                    ];
                })->toArray(),
            ];
        }

        // Get quotes with VoIP services in the period
        $quotes = Quote::where('company_id', $this->companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('items', function ($query) {
                $query->whereNotNull('service_type');
            })
            ->with(['client', 'items' => function ($query) {
                $query->whereNotNull('service_type');
            }])
            ->get();

        foreach ($quotes as $quote) {
            $auditTrail['quotes_processed'][] = [
                'quote_id' => $quote->id,
                'quote_number' => $quote->getFullNumber(),
                'client_name' => $quote->client->name ?? 'Unknown',
                'date' => $quote->date->toDateString(),
                'status' => $quote->status,
                'service_address' => $quote->getServiceAddress(),
                'voip_items' => $quote->voipItems->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'service_type' => $item->service_type,
                        'base_amount' => $item->subtotal - $item->discount,
                        'tax_amount' => $item->tax,
                        'line_count' => $item->line_count,
                        'minutes' => $item->minutes,
                        'tax_breakdown' => $item->voip_tax_data['tax_breakdown'] ?? [],
                    ];
                })->toArray(),
            ];
        }

        // Get exemption usage in the period
        $exemptionUsage = TaxExemptionUsage::where('company_id', $this->companyId)
            ->whereBetween('used_at', [$startDate, $endDate])
            ->with(['taxExemption', 'client', 'invoice', 'quote'])
            ->get();

        foreach ($exemptionUsage as $usage) {
            $auditTrail['exemptions_used'][] = [
                'usage_id' => $usage->id,
                'exemption_name' => $usage->taxExemption->exemption_name ?? 'Unknown',
                'exemption_type' => $usage->taxExemption->exemption_type ?? 'Unknown',
                'certificate_number' => $usage->taxExemption->certificate_number ?? null,
                'client_name' => $usage->client->name ?? 'Unknown',
                'invoice_number' => $usage->invoice?->getFullNumber(),
                'quote_number' => $usage->quote?->getFullNumber(),
                'original_tax_amount' => $usage->original_tax_amount,
                'exempted_amount' => $usage->exempted_amount,
                'final_tax_amount' => $usage->final_tax_amount,
                'used_at' => $usage->used_at->toISOString(),
            ];
        }

        // Get tax rate changes in the period
        $rateChanges = TaxRateHistory::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['taxRate', 'changedByUser'])
            ->get();

        foreach ($rateChanges as $change) {
            $auditTrail['rate_changes'][] = [
                'change_id' => $change->id,
                'tax_rate_id' => $change->voip_tax_rate_id,
                'tax_name' => $change->taxRate->tax_name ?? 'Unknown',
                'change_reason' => $change->change_reason,
                'changed_by' => $change->changedByUser->name ?? 'Unknown',
                'changed_at' => $change->created_at->toISOString(),
                'source' => $change->source,
                'changes_summary' => $change->getChangesSummary(),
            ];
        }

        // Generate summary statistics
        $auditTrail['summary'] = [
            'total_invoices' => count($auditTrail['invoices_processed']),
            'total_quotes' => count($auditTrail['quotes_processed']),
            'total_exemptions_used' => count($auditTrail['exemptions_used']),
            'total_rate_changes' => count($auditTrail['rate_changes']),
            'total_tax_collected' => $this->calculateTotalTaxCollected($invoices),
            'total_exemptions_amount' => $exemptionUsage->sum('exempted_amount'),
        ];

        return $auditTrail;
    }

    /**
     * Generate compliance report for regulatory filing.
     */
    public function generateComplianceReport(Carbon $startDate, Carbon $endDate, array $jurisdictions = []): array
    {
        $report = [
            'company_id' => $this->companyId,
            'reporting_period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'jurisdictions' => [],
            'summary_by_service_type' => [],
            'summary_by_tax_type' => [],
            'total_summary' => []
        ];

        // Get target jurisdictions
        $targetJurisdictions = empty($jurisdictions) 
            ? TaxJurisdiction::where('company_id', $this->companyId)->active()->get()
            : TaxJurisdiction::whereIn('id', $jurisdictions)->get();

        foreach ($targetJurisdictions as $jurisdiction) {
            $jurisdictionData = [
                'jurisdiction_id' => $jurisdiction->id,
                'jurisdiction_name' => $jurisdiction->name,
                'jurisdiction_type' => $jurisdiction->jurisdiction_type,
                'authority_name' => $jurisdiction->authority_name,
                'tax_calculations' => [],
                'filing_requirements' => $jurisdiction->filing_requirements,
                'totals' => [
                    'base_amount' => 0,
                    'tax_amount' => 0,
                    'exemptions_amount' => 0,
                    'net_tax_amount' => 0,
                ]
            ];

            // Get all invoices with taxes for this jurisdiction
            $invoices = Invoice::where('company_id', $this->companyId)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereHas('items', function ($query) {
                    $query->whereNotNull('service_type');
                })
                ->with(['client', 'items'])
                ->get();

            foreach ($invoices as $invoice) {
                foreach ($invoice->voipItems as $item) {
                    if ($item->voip_tax_data && !empty($item->voip_tax_data['tax_breakdown'])) {
                        foreach ($item->voip_tax_data['tax_breakdown'] as $tax) {
                            if (isset($tax['jurisdiction']) && $tax['jurisdiction'] === $jurisdiction->name) {
                                $jurisdictionData['tax_calculations'][] = [
                                    'invoice_id' => $invoice->id,
                                    'invoice_number' => $invoice->getFullNumber(),
                                    'client_name' => $invoice->client->name,
                                    'service_type' => $item->service_type,
                                    'tax_name' => $tax['tax_name'],
                                    'tax_type' => $tax['tax_type'],
                                    'base_amount' => $tax['base_amount'],
                                    'tax_amount' => $tax['tax_amount'],
                                    'rate' => $tax['rate'],
                                    'rate_type' => $tax['rate_type'],
                                ];

                                $jurisdictionData['totals']['base_amount'] += $tax['base_amount'];
                                $jurisdictionData['totals']['tax_amount'] += $tax['tax_amount'];
                            }
                        }
                    }
                }
            }

            // Add exemptions for this jurisdiction
            $exemptions = TaxExemptionUsage::where('company_id', $this->companyId)
                ->whereBetween('used_at', [$startDate, $endDate])
                ->whereHas('taxExemption', function ($query) use ($jurisdiction) {
                    $query->where('tax_jurisdiction_id', $jurisdiction->id)
                          ->orWhere('is_blanket_exemption', true);
                })
                ->sum('exempted_amount');

            $jurisdictionData['totals']['exemptions_amount'] = $exemptions;
            $jurisdictionData['totals']['net_tax_amount'] = 
                $jurisdictionData['totals']['tax_amount'] - $exemptions;

            $report['jurisdictions'][] = $jurisdictionData;
        }

        return $report;
    }

    /**
     * Validate tax exemption certificates.
     */
    public function validateExemptionCertificates(): array
    {
        $results = [
            'valid' => [],
            'expiring_soon' => [],
            'expired' => [],
            'missing_documents' => [],
            'needs_verification' => [],
        ];

        $exemptions = TaxExemption::where('company_id', $this->companyId)
            ->with('client')
            ->get();

        foreach ($exemptions as $exemption) {
            if ($exemption->status === TaxExemption::STATUS_EXPIRED || $exemption->isExpired()) {
                $results['expired'][] = [
                    'exemption_id' => $exemption->id,
                    'client_name' => $exemption->client->name ?? 'Unknown',
                    'exemption_name' => $exemption->exemption_name,
                    'certificate_number' => $exemption->certificate_number,
                    'expiry_date' => $exemption->expiry_date?->toDateString(),
                ];
            } elseif ($exemption->isExpiringSoon()) {
                $results['expiring_soon'][] = [
                    'exemption_id' => $exemption->id,
                    'client_name' => $exemption->client->name ?? 'Unknown',
                    'exemption_name' => $exemption->exemption_name,
                    'certificate_number' => $exemption->certificate_number,
                    'expiry_date' => $exemption->expiry_date?->toDateString(),
                    'days_until_expiry' => $exemption->expiry_date ? 
                        now()->diffInDays($exemption->expiry_date) : null,
                ];
            } elseif ($exemption->verification_status === TaxExemption::VERIFICATION_PENDING) {
                $results['needs_verification'][] = [
                    'exemption_id' => $exemption->id,
                    'client_name' => $exemption->client->name ?? 'Unknown',
                    'exemption_name' => $exemption->exemption_name,
                    'certificate_number' => $exemption->certificate_number,
                    'created_at' => $exemption->created_at->toDateString(),
                ];
            } elseif (empty($exemption->certificate_file_path)) {
                $results['missing_documents'][] = [
                    'exemption_id' => $exemption->id,
                    'client_name' => $exemption->client->name ?? 'Unknown',
                    'exemption_name' => $exemption->exemption_name,
                    'certificate_number' => $exemption->certificate_number,
                ];
            } else {
                $results['valid'][] = [
                    'exemption_id' => $exemption->id,
                    'client_name' => $exemption->client->name ?? 'Unknown',
                    'exemption_name' => $exemption->exemption_name,
                    'certificate_number' => $exemption->certificate_number,
                    'expiry_date' => $exemption->expiry_date?->toDateString(),
                ];
            }
        }

        return $results;
    }

    /**
     * Export compliance data for external audit.
     */
    public function exportComplianceData(Carbon $startDate, Carbon $endDate, string $format = 'json'): string
    {
        $auditTrail = $this->generateAuditTrail($startDate, $endDate);
        $complianceReport = $this->generateComplianceReport($startDate, $endDate);
        $exemptionValidation = $this->validateExemptionCertificates();

        $exportData = [
            'export_metadata' => [
                'company_id' => $this->companyId,
                'export_date' => now()->toISOString(),
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'format' => $format,
                'version' => '1.0',
            ],
            'audit_trail' => $auditTrail,
            'compliance_report' => $complianceReport,
            'exemption_validation' => $exemptionValidation,
        ];

        $filename = "voip_tax_compliance_{$this->companyId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}.{$format}";
        $filepath = "compliance-exports/{$filename}";

        switch ($format) {
            case 'json':
                $content = json_encode($exportData, JSON_PRETTY_PRINT);
                break;
            case 'csv':
                $content = $this->convertToCSV($exportData);
                break;
            case 'xml':
                $content = $this->convertToXML($exportData);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        Storage::disk($this->config['storage_disk'])->put($filepath, $content);

        \Log::info('Compliance data exported', [
            'company_id' => $this->companyId,
            'period' => "{$startDate->toDateString()} to {$endDate->toDateString()}",
            'format' => $format,
            'filepath' => $filepath,
            'file_size' => strlen($content),
        ]);

        return $filepath;
    }

    /**
     * Check compliance status for the company.
     */
    public function checkComplianceStatus(): array
    {
        $status = [
            'overall_status' => 'compliant',
            'issues' => [],
            'warnings' => [],
            'recommendations' => [],
            'checks_performed' => [],
        ];

        // Check 1: Tax rate coverage
        $jurisdictions = TaxJurisdiction::where('company_id', $this->companyId)->active()->get();
        $ratesCount = VoIPTaxRate::where('company_id', $this->companyId)->active()->count();
        
        if ($ratesCount === 0) {
            $status['issues'][] = 'No active tax rates configured';
            $status['overall_status'] = 'non_compliant';
        } elseif ($ratesCount < $jurisdictions->count()) {
            $status['warnings'][] = 'Some jurisdictions may not have tax rates configured';
        }

        $status['checks_performed'][] = 'Tax rate coverage';

        // Check 2: Exemption certificate validity
        $exemptionValidation = $this->validateExemptionCertificates();
        
        if (count($exemptionValidation['expired']) > 0) {
            $status['issues'][] = count($exemptionValidation['expired']) . ' expired exemption certificates';
            $status['overall_status'] = 'non_compliant';
        }

        if (count($exemptionValidation['expiring_soon']) > 0) {
            $status['warnings'][] = count($exemptionValidation['expiring_soon']) . ' exemption certificates expiring within 30 days';
        }

        if (count($exemptionValidation['needs_verification']) > 0) {
            $status['warnings'][] = count($exemptionValidation['needs_verification']) . ' exemption certificates need verification';
        }

        $status['checks_performed'][] = 'Exemption certificate validity';

        // Check 3: Data retention compliance
        $oldestRecord = TaxRateHistory::where('company_id', $this->companyId)
            ->orderBy('created_at')
            ->first();

        if ($oldestRecord && $oldestRecord->created_at->lt(now()->subYears($this->config['retention_years']))) {
            $status['recommendations'][] = 'Consider archiving records older than ' . $this->config['retention_years'] . ' years';
        }

        $status['checks_performed'][] = 'Data retention compliance';

        // Check 4: Recent tax calculations
        $recentCalculations = Invoice::where('company_id', $this->companyId)
            ->whereHas('items', function ($query) {
                $query->whereNotNull('service_type');
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($recentCalculations === 0) {
            $status['warnings'][] = 'No VoIP tax calculations in the past 30 days';
        }

        $status['checks_performed'][] = 'Recent tax calculation activity';

        return $status;
    }

    /**
     * Calculate total tax collected from invoices.
     */
    protected function calculateTotalTaxCollected($invoices): float
    {
        $total = 0;
        foreach ($invoices as $invoice) {
            foreach ($invoice->voipItems as $item) {
                $total += $item->tax;
            }
        }
        return round($total, 2);
    }

    /**
     * Convert export data to CSV format.
     */
    protected function convertToCSV(array $data): string
    {
        $csv = "Export Date,Company ID,Period Start,Period End\n";
        $csv .= "{$data['export_metadata']['export_date']},{$data['export_metadata']['company_id']},{$data['export_metadata']['period']['start_date']},{$data['export_metadata']['period']['end_date']}\n\n";
        
        $csv .= "Invoice Number,Client Name,Date,Service Type,Base Amount,Tax Amount\n";
        foreach ($data['audit_trail']['invoices_processed'] as $invoice) {
            foreach ($invoice['voip_items'] as $item) {
                $csv .= "\"{$invoice['invoice_number']}\",\"{$invoice['client_name']}\",\"{$invoice['date']}\",\"{$item['service_type']}\",{$item['base_amount']},{$item['tax_amount']}\n";
            }
        }
        
        return $csv;
    }

    /**
     * Convert export data to XML format.
     */
    protected function convertToXML(array $data): string
    {
        $xml = new \SimpleXMLElement('<VoIPTaxComplianceExport/>');
        $this->arrayToXML($data, $xml);
        return $xml->asXML();
    }

    /**
     * Helper method to convert array to XML.
     */
    protected function arrayToXML(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}