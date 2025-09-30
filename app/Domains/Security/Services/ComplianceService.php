<?php

namespace App\Domains\Security\Services;

use App\Models\AccountHold;
use App\Models\Client;
use App\Models\CollectionNote;
use App\Models\DunningAction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Compliance Service
 *
 * Handles FDCPA, TCPA, and state law compliance monitoring,
 * legal documentation generation, dispute handling, and
 * compliance reporting for dunning management operations.
 */
class ComplianceService
{
    protected array $fdcpaRequirements = [
        'validation_notice_sent' => false,
        'dispute_period_respected' => false,
        'cease_communication_honored' => false,
        'legal_notices_proper' => false,
        'harassment_avoided' => false,
    ];

    protected array $tcpaRequirements = [
        'consent_obtained' => false,
        'opt_out_honored' => false,
        'time_restrictions_followed' => false,
        'frequency_limits_respected' => false,
        'do_not_call_honored' => false,
    ];

    protected array $stateSpecificRules = [
        'CA' => ['max_interest_rate' => 10, 'wage_garnishment_limit' => 25],
        'NY' => ['max_interest_rate' => 16, 'wage_garnishment_limit' => 10],
        'TX' => ['max_interest_rate' => 18, 'wage_garnishment_limit' => 25],
        'FL' => ['max_interest_rate' => 18, 'wage_garnishment_limit' => 25],
    ];

    protected int $disputeResponseDays = 30;

    protected int $validationNoticeDays = 5;

    protected array $documentRetentionYears = [
        'collection_notes' => 7,
        'payment_records' => 7,
        'dispute_records' => 7,
        'legal_notices' => 10,
        'compliance_reports' => 5,
    ];

    /**
     * Perform comprehensive compliance check for a client.
     */
    public function performComplianceCheck(Client $client): array
    {
        $compliance = [
            'client_id' => $client->id,
            'check_date' => Carbon::now(),
            'overall_status' => 'compliant',
            'violations' => [],
            'warnings' => [],
            'required_actions' => [],
            'fdcpa_compliance' => $this->checkFdcpaCompliance($client),
            'tcpa_compliance' => $this->checkTcpaCompliance($client),
            'state_compliance' => $this->checkStateCompliance($client),
            'documentation_compliance' => $this->checkDocumentationCompliance($client),
        ];

        // Determine overall compliance status
        $hasViolations = ! empty($compliance['fdcpa_compliance']['violations']) ||
                        ! empty($compliance['tcpa_compliance']['violations']) ||
                        ! empty($compliance['state_compliance']['violations']);

        if ($hasViolations) {
            $compliance['overall_status'] = 'non_compliant';
        } elseif (! empty($compliance['fdcpa_compliance']['warnings']) ||
                 ! empty($compliance['tcpa_compliance']['warnings'])) {
            $compliance['overall_status'] = 'at_risk';
        }

        // Generate required actions
        $compliance['required_actions'] = $this->generateRequiredActions($compliance);

        // Log compliance check
        Log::info('Compliance check completed', [
            'client_id' => $client->id,
            'status' => $compliance['overall_status'],
            'violations_count' => count($compliance['violations']),
        ]);

        return $compliance;
    }

    /**
     * Check FDCPA compliance for a client.
     */
    protected function checkFdcpaCompliance(Client $client): array
    {
        $compliance = [
            'status' => 'compliant',
            'violations' => [],
            'warnings' => [],
            'requirements_met' => [],
        ];

        // Check validation notice requirement
        $validationNotice = $this->checkValidationNotice($client);
        if (! $validationNotice['sent']) {
            $compliance['violations'][] = [
                'type' => 'missing_validation_notice',
                'description' => 'Initial validation notice not sent within 5 days',
                'severity' => 'high',
                'action_required' => 'Send validation notice immediately',
            ];
        } else {
            $compliance['requirements_met'][] = 'validation_notice_sent';
        }

        // Check dispute handling
        $disputeHandling = $this->checkDisputeHandling($client);
        foreach ($disputeHandling['violations'] as $violation) {
            $compliance['violations'][] = $violation;
        }

        // Check communication frequency
        $communicationCheck = $this->checkCommunicationFrequency($client);
        if ($communicationCheck['excessive']) {
            $compliance['violations'][] = [
                'type' => 'excessive_communication',
                'description' => 'Communication frequency exceeds FDCPA limits',
                'severity' => 'medium',
                'details' => $communicationCheck['details'],
            ];
        }

        // Check cease communication requests
        $ceaseComm = $this->checkCeaseCommunicationRequests($client);
        if ($ceaseComm['violations']) {
            $compliance['violations'][] = [
                'type' => 'cease_communication_violated',
                'description' => 'Communication continued after cease request',
                'severity' => 'high',
                'action_required' => 'Stop all communications immediately',
            ];
        }

        // Check prohibited practices
        $prohibitedPractices = $this->checkProhibitedPractices($client);
        foreach ($prohibitedPractices as $violation) {
            $compliance['violations'][] = $violation;
        }

        $compliance['status'] = empty($compliance['violations']) ? 'compliant' : 'non_compliant';

        return $compliance;
    }

    /**
     * Check TCPA compliance for a client.
     */
    protected function checkTcpaCompliance(Client $client): array
    {
        $compliance = [
            'status' => 'compliant',
            'violations' => [],
            'warnings' => [],
            'requirements_met' => [],
        ];

        // Check SMS consent
        $smsConsent = $this->checkSmsConsent($client);
        if (! $smsConsent['valid']) {
            $compliance['violations'][] = [
                'type' => 'missing_sms_consent',
                'description' => 'SMS communications sent without proper consent',
                'severity' => 'high',
                'action_required' => 'Stop SMS communications until consent obtained',
            ];
        }

        // Check call time restrictions
        $timeRestrictions = $this->checkCallTimeRestrictions($client);
        if ($timeRestrictions['violations']) {
            $compliance['violations'][] = [
                'type' => 'call_time_violation',
                'description' => 'Calls made outside permitted hours',
                'severity' => 'medium',
                'details' => $timeRestrictions['details'],
            ];
        }

        // Check Do Not Call registry
        $dncCheck = $this->checkDoNotCallRegistry($client);
        if ($dncCheck['violation']) {
            $compliance['violations'][] = [
                'type' => 'do_not_call_violation',
                'description' => 'Calls made to number on Do Not Call registry',
                'severity' => 'high',
                'action_required' => 'Remove from calling list immediately',
            ];
        }

        // Check opt-out handling
        $optOutCheck = $this->checkOptOutHandling($client);
        if ($optOutCheck['violations']) {
            $compliance['violations'][] = [
                'type' => 'opt_out_violation',
                'description' => 'Communications continued after opt-out request',
                'severity' => 'high',
            ];
        }

        $compliance['status'] = empty($compliance['violations']) ? 'compliant' : 'non_compliant';

        return $compliance;
    }

    /**
     * Check state-specific compliance requirements.
     */
    protected function checkStateCompliance(Client $client): array
    {
        $state = $client->state ?? 'CA';
        $stateRules = $this->stateSpecificRules[$state] ?? [];

        $compliance = [
            'status' => 'compliant',
            'state' => $state,
            'violations' => [],
            'warnings' => [],
            'applicable_rules' => $stateRules,
        ];

        if (empty($stateRules)) {
            $compliance['warnings'][] = [
                'type' => 'unknown_state_rules',
                'description' => "State-specific rules for {$state} not configured",
                'severity' => 'low',
            ];

            return $compliance;
        }

        // Check interest rate limits
        if (isset($stateRules['max_interest_rate'])) {
            $interestCheck = $this->checkInterestRateCompliance($client, $stateRules['max_interest_rate']);
            if ($interestCheck['violation']) {
                $compliance['violations'][] = $interestCheck['details'];
            }
        }

        // Check wage garnishment limits
        if (isset($stateRules['wage_garnishment_limit'])) {
            $garnishmentCheck = $this->checkGarnishmentCompliance($client, $stateRules['wage_garnishment_limit']);
            if ($garnishmentCheck['violation']) {
                $compliance['violations'][] = $garnishmentCheck['details'];
            }
        }

        // Check state-specific licensing requirements
        $licensingCheck = $this->checkCollectionLicensing($state);
        if (! $licensingCheck['compliant']) {
            $compliance['violations'][] = [
                'type' => 'licensing_violation',
                'description' => "Collection activities require license in {$state}",
                'severity' => 'critical',
            ];
        }

        $compliance['status'] = empty($compliance['violations']) ? 'compliant' : 'non_compliant';

        return $compliance;
    }

    /**
     * Check documentation compliance and retention.
     */
    protected function checkDocumentationCompliance(Client $client): array
    {
        $compliance = [
            'status' => 'compliant',
            'violations' => [],
            'warnings' => [],
            'retention_compliance' => [],
        ];

        // Check required documentation exists
        $requiredDocs = [
            'collection_notes' => CollectionNote::where('client_id', $client->id)->exists(),
            'dunning_actions' => DunningAction::where('client_id', $client->id)->exists(),
            'validation_notice' => $this->hasValidationNotice($client),
            'dispute_records' => $this->hasDisputeRecords($client),
        ];

        foreach ($requiredDocs as $docType => $exists) {
            if (! $exists) {
                $compliance['violations'][] = [
                    'type' => "missing_{$docType}",
                    'description' => ucfirst(str_replace('_', ' ', $docType)).' documentation missing',
                    'severity' => 'medium',
                ];
            }
        }

        // Check retention compliance
        foreach ($this->documentRetentionYears as $docType => $years) {
            $retentionCheck = $this->checkRetentionCompliance($client, $docType, $years);
            $compliance['retention_compliance'][$docType] = $retentionCheck;

            if (! $retentionCheck['compliant']) {
                $compliance['warnings'][] = [
                    'type' => "retention_warning_{$docType}",
                    'description' => "Some {$docType} may exceed retention period",
                    'severity' => 'low',
                ];
            }
        }

        $compliance['status'] = empty($compliance['violations']) ? 'compliant' : 'non_compliant';

        return $compliance;
    }

    /**
     * Check validation notice requirement.
     */
    protected function checkValidationNotice(Client $client): array
    {
        $firstAction = DunningAction::where('client_id', $client->id)
            ->whereIn('action_type', [
                DunningAction::ACTION_EMAIL,
                DunningAction::ACTION_LETTER,
                DunningAction::ACTION_PHONE_CALL,
            ])
            ->orderBy('created_at')
            ->first();

        if (! $firstAction) {
            return ['sent' => false, 'required' => false];
        }

        $validationNotice = DunningAction::where('client_id', $client->id)
            ->where('action_type', DunningAction::ACTION_VALIDATION_NOTICE)
            ->where('created_at', '<=', $firstAction->created_at->addDays($this->validationNoticeDays))
            ->first();

        return [
            'sent' => $validationNotice !== null,
            'required' => true,
            'first_action_date' => $firstAction->created_at,
            'validation_notice_date' => $validationNotice?->created_at,
        ];
    }

    /**
     * Check dispute handling compliance.
     */
    protected function checkDisputeHandling(Client $client): array
    {
        $disputes = CollectionNote::where('client_id', $client->id)
            ->where('contains_dispute', true)
            ->get();

        $violations = [];

        foreach ($disputes as $dispute) {
            // Check if collection was paused during dispute
            $collectionPaused = DunningAction::where('client_id', $client->id)
                ->where('created_at', '>', $dispute->created_at)
                ->where('created_at', '<=', $dispute->created_at->addDays($this->disputeResponseDays))
                ->whereIn('action_type', [
                    DunningAction::ACTION_EMAIL,
                    DunningAction::ACTION_PHONE_CALL,
                    DunningAction::ACTION_SMS,
                ])
                ->exists();

            if ($collectionPaused) {
                $violations[] = [
                    'type' => 'collection_continued_during_dispute',
                    'description' => 'Collection activities continued during dispute period',
                    'severity' => 'high',
                    'dispute_date' => $dispute->created_at,
                    'action_required' => 'Pause all collection activities during disputes',
                ];
            }

            // Check if dispute was responded to within 30 days
            $disputeResponse = CollectionNote::where('client_id', $client->id)
                ->where('created_at', '>', $dispute->created_at)
                ->where('created_at', '<=', $dispute->created_at->addDays($this->disputeResponseDays))
                ->where('note_type', CollectionNote::TYPE_DISPUTE_RESPONSE)
                ->exists();

            if (! $disputeResponse) {
                $violations[] = [
                    'type' => 'dispute_not_responded',
                    'description' => 'Dispute not responded to within 30 days',
                    'severity' => 'medium',
                    'dispute_date' => $dispute->created_at,
                ];
            }
        }

        return ['violations' => $violations];
    }

    /**
     * Generate legal documentation for compliance.
     */
    public function generateLegalDocumentation(Client $client, string $documentType, array $options = []): array
    {
        $documents = [];

        switch ($documentType) {
            case 'validation_notice':
                $documents[] = $this->generateValidationNotice($client, $options);
                break;

            case 'final_demand':
                $documents[] = $this->generateFinalDemandLetter($client, $options);
                break;

            case 'dispute_response':
                $documents[] = $this->generateDisputeResponse($client, $options);
                break;

            case 'legal_referral':
                $documents[] = $this->generateLegalReferralPackage($client, $options);
                break;

            case 'compliance_report':
                $documents[] = $this->generateComplianceReport($client, $options);
                break;

            default:
                throw new \InvalidArgumentException("Unknown document type: {$documentType}");
        }

        // Store documents with proper retention
        foreach ($documents as $document) {
            $this->storeDocument($client, $document);
        }

        return $documents;
    }

    /**
     * Generate validation notice document.
     */
    protected function generateValidationNotice(Client $client, array $options = []): array
    {
        $totalBalance = $client->getBalance();

        $content = "
DEBT VALIDATION NOTICE

This is an attempt to collect a debt and any information obtained will be used for that purpose.

Debtor: {$client->name}
Account: #{$client->account_number}
Original Creditor: Nestogy Communications
Amount: $".number_format($totalBalance, 2).'

Unless you dispute the validity of this debt within 30 days of receiving this notice, the debt will be assumed to be valid. If you dispute the debt in writing within 30 days, we will obtain verification and mail it to you.

You have the right to request the name and address of the original creditor if different from the current creditor.

If you have any questions, please contact us at:
Nestogy Collections Department
Phone: 1-800-NESTOGY
Email: collections@nestogy.com

Date: '.Carbon::now()->format('M j, Y').'
        ';

        return [
            'type' => 'validation_notice',
            'client_id' => $client->id,
            'content' => $content,
            'generated_date' => Carbon::now(),
            'file_name' => "validation_notice_{$client->id}_".time().'.pdf',
        ];
    }

    /**
     * Generate final demand letter.
     */
    protected function generateFinalDemandLetter(Client $client, array $options = []): array
    {
        $totalBalance = $client->getBalance();

        $content = "
FINAL DEMAND FOR PAYMENT

{$client->name}
{$client->mailing_address}

Account: #{$client->account_number}
Amount Due: $".number_format($totalBalance, 2).'

DEMAND IS HEREBY MADE for payment of the above amount, which represents charges for services provided to you.

If this amount is not paid within 10 days from the date of this letter, we will have no alternative but to:

1. Refer this account to our legal department for collection
2. Report this delinquency to credit reporting agencies
3. Suspend all services immediately
4. Pursue all available legal remedies

This is a final notice. Act now to avoid additional costs and legal action.

TAKE NOTICE that this is an attempt to collect a debt and any information obtained will be used for that purpose.

Date: '.Carbon::now()->format('M j, Y').'

NESTOGY COLLECTIONS DEPARTMENT
        ';

        return [
            'type' => 'final_demand',
            'client_id' => $client->id,
            'content' => $content,
            'generated_date' => Carbon::now(),
            'file_name' => "final_demand_{$client->id}_".time().'.pdf',
            'legal_significance' => true,
        ];
    }

    /**
     * Handle dispute filing and processing.
     */
    public function processDispute(Client $client, array $disputeData): array
    {
        return DB::transaction(function () use ($client, $disputeData) {
            // Create dispute record
            $dispute = CollectionNote::create([
                'client_id' => $client->id,
                'note_type' => CollectionNote::TYPE_DISPUTE,
                'content' => $disputeData['description'] ?? 'Client dispute filed',
                'contains_dispute' => true,
                'dispute_amount' => $disputeData['amount'] ?? $client->getBalance(),
                'dispute_reason' => $disputeData['reason'] ?? 'Amount disputed',
                'outcome' => CollectionNote::OUTCOME_DISPUTE_RECEIVED,
                'requires_legal_review' => true,
                'is_important' => true,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Pause collection activities
            $this->pauseCollectionActivities($client, 'Dispute filed - FDCPA compliance');

            // Schedule dispute response
            $responseDate = Carbon::now()->addDays($this->disputeResponseDays);
            DunningAction::create([
                'client_id' => $client->id,
                'action_type' => DunningAction::ACTION_DISPUTE_RESPONSE,
                'status' => DunningAction::STATUS_SCHEDULED,
                'scheduled_date' => $responseDate,
                'notes' => 'Respond to client dispute within FDCPA timeframe',
                'requires_legal_review' => true,
                'created_by' => auth()->id() ?? 1,
            ]);

            Log::info('Dispute processed', [
                'client_id' => $client->id,
                'dispute_id' => $dispute->id,
                'amount' => $disputeData['amount'] ?? $client->getBalance(),
            ]);

            return [
                'dispute_id' => $dispute->id,
                'status' => 'received',
                'response_due_date' => $responseDate,
                'collection_paused' => true,
            ];
        });
    }

    /**
     * Pause collection activities for compliance.
     */
    protected function pauseCollectionActivities(Client $client, string $reason): void
    {
        // Cancel scheduled dunning actions
        DunningAction::where('client_id', $client->id)
            ->where('status', DunningAction::STATUS_SCHEDULED)
            ->update([
                'status' => DunningAction::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => Carbon::now(),
            ]);

        // Create compliance hold
        AccountHold::create([
            'client_id' => $client->id,
            'hold_type' => AccountHold::TYPE_COMPLIANCE_HOLD,
            'reason' => $reason,
            'status' => AccountHold::STATUS_ACTIVE,
            'severity' => AccountHold::SEVERITY_COMPLIANCE_HOLD,
            'notes' => 'Automatic compliance hold due to dispute or legal requirement',
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Generate compliance report.
     */
    public function generateComplianceReport(array $options = []): array
    {
        $dateRange = [
            $options['start_date'] ?? Carbon::now()->subMonth(),
            $options['end_date'] ?? Carbon::now(),
        ];

        $report = [
            'report_date' => Carbon::now(),
            'period' => $dateRange,
            'summary' => [
                'total_clients_reviewed' => 0,
                'compliant_clients' => 0,
                'non_compliant_clients' => 0,
                'at_risk_clients' => 0,
            ],
            'fdcpa_compliance' => [
                'validation_notices_sent' => 0,
                'disputes_received' => 0,
                'disputes_responded' => 0,
                'violations' => [],
            ],
            'tcpa_compliance' => [
                'sms_consent_violations' => 0,
                'call_time_violations' => 0,
                'opt_out_violations' => 0,
            ],
            'state_compliance' => [],
            'required_actions' => [],
        ];

        // Get all clients with collection activity in period
        $clients = Client::whereHas('dunningActions', function ($query) use ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        })->get();

        $report['summary']['total_clients_reviewed'] = $clients->count();

        foreach ($clients as $client) {
            $compliance = $this->performComplianceCheck($client);

            switch ($compliance['overall_status']) {
                case 'compliant':
                    $report['summary']['compliant_clients']++;
                    break;
                case 'non_compliant':
                    $report['summary']['non_compliant_clients']++;
                    break;
                case 'at_risk':
                    $report['summary']['at_risk_clients']++;
                    break;
            }
        }

        // Store report
        $fileName = 'compliance_report_'.Carbon::now()->format('Y_m_d').'.json';
        Storage::put("compliance/reports/{$fileName}", json_encode($report, JSON_PRETTY_PRINT));

        return $report;
    }

    /**
     * Store legal document with proper retention.
     */
    protected function storeDocument(Client $client, array $document): void
    {
        $directory = "legal/documents/{$client->id}";
        $fileName = $document['file_name'];
        $filePath = "{$directory}/{$fileName}";

        // Store document content
        Storage::put($filePath, $document['content']);

        // Create document record
        DB::table('legal_documents')->insert([
            'client_id' => $client->id,
            'document_type' => $document['type'],
            'file_path' => $filePath,
            'file_name' => $fileName,
            'generated_date' => $document['generated_date'],
            'legal_significance' => $document['legal_significance'] ?? false,
            'retention_years' => $this->documentRetentionYears[$document['type']] ?? 7,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Check various compliance requirements with mock implementations.
     */
    protected function checkCommunicationFrequency(Client $client): array
    {
        return ['excessive' => false, 'details' => []];
    }

    protected function checkCeaseCommunicationRequests(Client $client): array
    {
        return ['violations' => false];
    }

    protected function checkProhibitedPractices(Client $client): array
    {
        return [];
    }

    protected function checkSmsConsent(Client $client): array
    {
        return ['valid' => $client->sms_consent ?? false];
    }

    protected function checkCallTimeRestrictions(Client $client): array
    {
        return ['violations' => false, 'details' => []];
    }

    protected function checkDoNotCallRegistry(Client $client): array
    {
        return ['violation' => $client->do_not_call ?? false];
    }

    protected function checkOptOutHandling(Client $client): array
    {
        return ['violations' => false];
    }

    protected function checkInterestRateCompliance(Client $client, float $maxRate): array
    {
        return ['violation' => false];
    }

    protected function checkGarnishmentCompliance(Client $client, float $maxPercent): array
    {
        return ['violation' => false];
    }

    protected function checkCollectionLicensing(string $state): array
    {
        return ['compliant' => true];
    }

    protected function hasValidationNotice(Client $client): bool
    {
        return true;
    }

    protected function hasDisputeRecords(Client $client): bool
    {
        return CollectionNote::where('client_id', $client->id)->where('contains_dispute', true)->exists();
    }

    protected function checkRetentionCompliance(Client $client, string $docType, int $years): array
    {
        return ['compliant' => true];
    }

    /**
     * Generate required actions based on compliance check.
     */
    protected function generateRequiredActions(array $compliance): array
    {
        $actions = [];

        // Collect all violations from all compliance areas
        $allViolations = array_merge(
            $compliance['fdcpa_compliance']['violations'] ?? [],
            $compliance['tcpa_compliance']['violations'] ?? [],
            $compliance['state_compliance']['violations'] ?? [],
            $compliance['documentation_compliance']['violations'] ?? []
        );

        foreach ($allViolations as $violation) {
            if (isset($violation['action_required'])) {
                $actions[] = [
                    'action' => $violation['action_required'],
                    'priority' => $violation['severity'] === 'high' ? 'urgent' : 'normal',
                    'deadline' => Carbon::now()->addDays($violation['severity'] === 'high' ? 1 : 7),
                    'violation_type' => $violation['type'],
                ];
            }
        }

        return $actions;
    }
}
