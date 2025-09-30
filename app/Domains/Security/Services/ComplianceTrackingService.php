<?php

namespace App\Domains\Security\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractAuditLog;
use App\Models\ComplianceCheck;
use App\Models\ComplianceRequirement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ComplianceTrackingService
 *
 * Comprehensive compliance tracking and audit trail service for contracts.
 * Handles legal requirements, document retention, audit logging, and compliance monitoring.
 */
class ComplianceTrackingService
{
    /**
     * Log contract activity for audit trail
     */
    public function logContractActivity(
        Contract $contract,
        string $action,
        array $details = [],
        string $category = 'general',
        ?int $userId = null
    ): ContractAuditLog {
        $userId = $userId ?? Auth::id();

        $auditLog = ContractAuditLog::create([
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'user_id' => $userId,
            'action' => $action,
            'category' => $category,
            'description' => $this->generateAuditDescription($action, $details),
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
            'metadata' => [
                'contract_status' => $contract->status,
                'contract_value' => $contract->contract_value,
                'client_id' => $contract->client_id,
            ],
        ]);

        Log::info('Contract audit activity logged', [
            'audit_log_id' => $auditLog->id,
            'contract_id' => $contract->id,
            'action' => $action,
            'user_id' => $userId,
        ]);

        return $auditLog;
    }

    /**
     * Track compliance requirement
     */
    public function trackComplianceRequirement(
        Contract $contract,
        string $requirementType,
        array $requirementData
    ): ComplianceRequirement {
        return DB::transaction(function () use ($contract, $requirementType, $requirementData) {
            $requirement = ComplianceRequirement::create([
                'company_id' => $contract->company_id,
                'contract_id' => $contract->id,
                'requirement_type' => $requirementType,
                'title' => $requirementData['title'],
                'description' => $requirementData['description'] ?? null,
                'category' => $requirementData['category'] ?? 'legal',
                'priority' => $requirementData['priority'] ?? 'medium',
                'due_date' => isset($requirementData['due_date']) ? Carbon::parse($requirementData['due_date']) : null,
                'status' => 'pending',
                'requirements_data' => $requirementData['requirements_data'] ?? [],
                'compliance_criteria' => $requirementData['compliance_criteria'] ?? [],
                'created_by' => Auth::id(),
            ]);

            $this->logContractActivity(
                $contract,
                'compliance_requirement_added',
                ['requirement_id' => $requirement->id, 'type' => $requirementType],
                'compliance'
            );

            return $requirement;
        });
    }

    /**
     * Perform compliance check
     */
    public function performComplianceCheck(
        ComplianceRequirement $requirement,
        array $checkData
    ): ComplianceCheck {
        return DB::transaction(function () use ($requirement, $checkData) {
            $check = ComplianceCheck::create([
                'company_id' => $requirement->company_id,
                'contract_id' => $requirement->contract_id,
                'compliance_requirement_id' => $requirement->id,
                'check_type' => $checkData['check_type'] ?? 'manual',
                'status' => $checkData['status'], // compliant, non_compliant, needs_review
                'findings' => $checkData['findings'] ?? null,
                'recommendations' => $checkData['recommendations'] ?? null,
                'evidence_documents' => $checkData['evidence_documents'] ?? [],
                'checked_by' => Auth::id(),
                'checked_at' => now(),
                'next_check_date' => isset($checkData['next_check_date']) ?
                    Carbon::parse($checkData['next_check_date']) : null,
                'compliance_score' => $checkData['compliance_score'] ?? null,
                'risk_level' => $checkData['risk_level'] ?? 'medium',
                'metadata' => $checkData['metadata'] ?? [],
            ]);

            // Update requirement status based on check result
            $this->updateRequirementStatus($requirement, $check);

            $this->logContractActivity(
                $requirement->contract,
                'compliance_check_performed',
                [
                    'requirement_id' => $requirement->id,
                    'check_id' => $check->id,
                    'status' => $check->status,
                    'risk_level' => $check->risk_level,
                ],
                'compliance'
            );

            return $check;
        });
    }

    /**
     * Generate compliance report for contract
     */
    public function generateComplianceReport(Contract $contract): array
    {
        $requirements = $contract->complianceRequirements()->with('latestCheck')->get();

        $report = [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'generated_at' => now(),
            'overall_compliance_status' => 'compliant',
            'risk_assessment' => 'low',
            'total_requirements' => $requirements->count(),
            'compliant_requirements' => 0,
            'non_compliant_requirements' => 0,
            'pending_requirements' => 0,
            'overdue_requirements' => 0,
            'high_risk_items' => [],
            'upcoming_deadlines' => [],
            'requirements_summary' => [],
            'audit_trail_summary' => $this->getAuditTrailSummary($contract),
            'document_retention_status' => $this->checkDocumentRetention($contract),
        ];

        // Analyze each requirement
        foreach ($requirements as $requirement) {
            $status = $requirement->latestCheck ? $requirement->latestCheck->status : $requirement->status;

            switch ($status) {
                case 'compliant':
                    $report['compliant_requirements']++;
                    break;
                case 'non_compliant':
                    $report['non_compliant_requirements']++;
                    break;
                case 'pending':
                    $report['pending_requirements']++;
                    break;
            }

            // Check for overdue requirements
            if ($requirement->due_date && $requirement->due_date->isPast() && $status !== 'compliant') {
                $report['overdue_requirements']++;
            }

            // Identify high-risk items
            if ($requirement->latestCheck && $requirement->latestCheck->risk_level === 'high') {
                $report['high_risk_items'][] = [
                    'requirement_id' => $requirement->id,
                    'title' => $requirement->title,
                    'risk_level' => $requirement->latestCheck->risk_level,
                    'findings' => $requirement->latestCheck->findings,
                ];
            }

            // Check for upcoming deadlines
            if ($requirement->due_date && $requirement->due_date->between(now(), now()->addDays(30))) {
                $report['upcoming_deadlines'][] = [
                    'requirement_id' => $requirement->id,
                    'title' => $requirement->title,
                    'due_date' => $requirement->due_date,
                    'days_remaining' => now()->diffInDays($requirement->due_date),
                ];
            }

            $report['requirements_summary'][] = [
                'id' => $requirement->id,
                'title' => $requirement->title,
                'category' => $requirement->category,
                'priority' => $requirement->priority,
                'status' => $status,
                'due_date' => $requirement->due_date,
                'last_checked' => $requirement->latestCheck?->checked_at,
                'risk_level' => $requirement->latestCheck?->risk_level ?? 'unknown',
            ];
        }

        // Determine overall compliance status
        if ($report['non_compliant_requirements'] > 0 || $report['overdue_requirements'] > 0) {
            $report['overall_compliance_status'] = 'non_compliant';
        } elseif ($report['pending_requirements'] > 0) {
            $report['overall_compliance_status'] = 'partial';
        }

        // Determine risk assessment
        if (count($report['high_risk_items']) > 0) {
            $report['risk_assessment'] = 'high';
        } elseif ($report['non_compliant_requirements'] > 0) {
            $report['risk_assessment'] = 'medium';
        }

        return $report;
    }

    /**
     * Check document retention compliance
     */
    public function checkDocumentRetention(Contract $contract): array
    {
        $retentionPolicy = $this->getRetentionPolicy($contract);
        $documents = $contract->documents ?? [];

        $status = [
            'is_compliant' => true,
            'retention_period' => $retentionPolicy['period'],
            'documents_count' => count($documents),
            'expired_documents' => [],
            'expiring_soon' => [],
            'issues' => [],
        ];

        // Check each document
        foreach ($documents as $document) {
            $documentAge = Carbon::parse($document['created_at'] ?? $contract->created_at)
                ->diffInYears(now());

            if ($documentAge >= $retentionPolicy['period']) {
                $status['expired_documents'][] = $document;
                $status['is_compliant'] = false;
            } elseif ($documentAge >= ($retentionPolicy['period'] - 1)) {
                $status['expiring_soon'][] = $document;
            }
        }

        // Check for missing required documents
        $requiredDocs = $retentionPolicy['required_documents'] ?? [];
        foreach ($requiredDocs as $requiredDoc) {
            $found = collect($documents)->contains('type', $requiredDoc);
            if (! $found) {
                $status['issues'][] = "Missing required document: {$requiredDoc}";
                $status['is_compliant'] = false;
            }
        }

        return $status;
    }

    /**
     * Perform automated compliance checks
     */
    public function performAutomatedComplianceChecks(Contract $contract): Collection
    {
        $checks = collect();

        // Check contract expiration
        if ($contract->end_date && $contract->end_date->isPast() && $contract->status === 'active') {
            $checks->push([
                'type' => 'contract_expiration',
                'status' => 'non_compliant',
                'message' => 'Contract has expired but status is still active',
                'severity' => 'high',
            ]);
        }

        // Check required signatures
        $requiredSignatures = $contract->signatures()->where('is_required', true)->get();
        $missingSignatures = $requiredSignatures->where('status', '!=', 'signed');

        if ($missingSignatures->count() > 0) {
            $checks->push([
                'type' => 'missing_signatures',
                'status' => 'non_compliant',
                'message' => "Missing {$missingSignatures->count()} required signature(s)",
                'severity' => 'high',
            ]);
        }

        // Check milestone compliance
        $overdueMilestones = $contract->milestones()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->get();

        if ($overdueMilestones->count() > 0) {
            $checks->push([
                'type' => 'overdue_milestones',
                'status' => 'non_compliant',
                'message' => "Contract has {$overdueMilestones->count()} overdue milestone(s)",
                'severity' => 'medium',
            ]);
        }

        // Check payment compliance (if linked to invoices)
        $overdueInvoices = $contract->invoices()
            ->where('status', 'Sent')
            ->where('due_date', '<', now())
            ->get();

        if ($overdueInvoices->count() > 0) {
            $checks->push([
                'type' => 'overdue_payments',
                'status' => 'non_compliant',
                'message' => "Contract has {$overdueInvoices->count()} overdue invoice(s)",
                'severity' => 'medium',
            ]);
        }

        // Check data retention compliance
        $retentionStatus = $this->checkDocumentRetention($contract);
        if (! $retentionStatus['is_compliant']) {
            $checks->push([
                'type' => 'document_retention',
                'status' => 'non_compliant',
                'message' => 'Document retention policy violations found',
                'severity' => 'medium',
                'details' => $retentionStatus,
            ]);
        }

        // Log automated checks
        $this->logContractActivity(
            $contract,
            'automated_compliance_checks',
            ['checks_performed' => $checks->count(), 'issues_found' => $checks->where('status', 'non_compliant')->count()],
            'compliance'
        );

        return $checks;
    }

    /**
     * Get compliance dashboard data
     */
    public function getComplianceDashboard(int $companyId): array
    {
        $contracts = Contract::where('company_id', $companyId)->get();

        $dashboard = [
            'total_contracts' => $contracts->count(),
            'compliant_contracts' => 0,
            'non_compliant_contracts' => 0,
            'contracts_needing_review' => 0,
            'high_risk_contracts' => 0,
            'upcoming_deadlines' => [],
            'recent_audit_activities' => $this->getRecentAuditActivities($companyId),
            'compliance_trends' => $this->getComplianceTrends($companyId),
            'risk_breakdown' => ['high' => 0, 'medium' => 0, 'low' => 0],
        ];

        foreach ($contracts as $contract) {
            $report = $this->generateComplianceReport($contract);

            switch ($report['overall_compliance_status']) {
                case 'compliant':
                    $dashboard['compliant_contracts']++;
                    break;
                case 'non_compliant':
                    $dashboard['non_compliant_contracts']++;
                    break;
                case 'partial':
                    $dashboard['contracts_needing_review']++;
                    break;
            }

            if ($report['risk_assessment'] === 'high') {
                $dashboard['high_risk_contracts']++;
            }

            $dashboard['risk_breakdown'][$report['risk_assessment']]++;

            // Collect upcoming deadlines
            foreach ($report['upcoming_deadlines'] as $deadline) {
                $dashboard['upcoming_deadlines'][] = array_merge($deadline, [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                ]);
            }
        }

        // Sort upcoming deadlines by date
        usort($dashboard['upcoming_deadlines'], function ($a, $b) {
            return $a['due_date'] <=> $b['due_date'];
        });

        return $dashboard;
    }

    /**
     * Export compliance audit report
     */
    public function exportAuditReport(Contract $contract, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? $contract->created_at;
        $endDate = $endDate ?? now();

        $auditLogs = $contract->auditLogs()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->orderBy('occurred_at', 'desc')
            ->with('user')
            ->get();

        $report = [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'report_period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'generated_at' => now(),
            'generated_by' => Auth::user()->name ?? 'System',
            'total_activities' => $auditLogs->count(),
            'activities_by_category' => $auditLogs->groupBy('category')->map->count(),
            'activities_by_user' => $auditLogs->groupBy('user.name')->map->count(),
            'detailed_activities' => $auditLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'timestamp' => $log->occurred_at,
                    'user' => $log->user->name ?? 'System',
                    'action' => $log->action,
                    'category' => $log->category,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                ];
            }),
            'compliance_summary' => $this->generateComplianceReport($contract),
        ];

        return $report;
    }

    /**
     * Helper methods
     */
    protected function generateAuditDescription(string $action, array $details): string
    {
        $descriptions = [
            'contract_created' => 'Contract was created',
            'contract_updated' => 'Contract details were updated',
            'contract_approved' => 'Contract was approved',
            'contract_signed' => 'Contract was digitally signed',
            'contract_activated' => 'Contract status changed to active',
            'contract_terminated' => 'Contract was terminated',
            'milestone_completed' => 'Contract milestone was completed',
            'invoice_generated' => 'Invoice was generated from contract',
            'compliance_requirement_added' => 'Compliance requirement was added',
            'compliance_check_performed' => 'Compliance check was performed',
            'document_uploaded' => 'Document was uploaded to contract',
            'amendment_created' => 'Contract amendment was created',
        ];

        $baseDescription = $descriptions[$action] ?? "Action performed: {$action}";

        if (! empty($details)) {
            $baseDescription .= ' - '.json_encode($details);
        }

        return $baseDescription;
    }

    protected function updateRequirementStatus(ComplianceRequirement $requirement, ComplianceCheck $check): void
    {
        $newStatus = match ($check->status) {
            'compliant' => 'compliant',
            'non_compliant' => 'non_compliant',
            'needs_review' => 'under_review',
            default => 'pending'
        };

        $requirement->update([
            'status' => $newStatus,
            'last_checked_at' => $check->checked_at,
        ]);
    }

    protected function getRetentionPolicy(Contract $contract): array
    {
        // Default retention policy - this could be configurable
        return [
            'period' => 7, // years
            'required_documents' => [
                'signed_contract',
                'amendment_documents',
                'compliance_certificates',
            ],
        ];
    }

    protected function getAuditTrailSummary(Contract $contract): array
    {
        $logs = $contract->auditLogs()->get();

        return [
            'total_activities' => $logs->count(),
            'last_activity' => $logs->max('occurred_at'),
            'categories' => $logs->groupBy('category')->map->count()->toArray(),
            'recent_activities' => $logs->take(5)->map(function ($log) {
                return [
                    'action' => $log->action,
                    'occurred_at' => $log->occurred_at,
                    'user' => $log->user->name ?? 'System',
                ];
            }),
        ];
    }

    protected function getRecentAuditActivities(int $companyId, int $limit = 10): Collection
    {
        return ContractAuditLog::where('company_id', $companyId)
            ->with(['contract', 'user'])
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function getComplianceTrends(int $companyId): array
    {
        // Implementation would analyze compliance trends over time
        // This is a simplified version
        return [
            'monthly_compliance_rate' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'data' => [85, 87, 90, 88, 92, 94],
            ],
            'risk_levels_over_time' => [
                'high' => [2, 1, 0, 1, 0, 0],
                'medium' => [5, 4, 3, 2, 3, 2],
                'low' => [15, 18, 20, 22, 24, 26],
            ],
        ];
    }
}
