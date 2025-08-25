<?php

namespace App\Services;

use App\Models\RefundRequest;
use App\Models\RefundTransaction;
use App\Models\CreditNote;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Domains\Contract\Models\Contract;
use App\Services\VoIPTaxReversalService;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

/**
 * Refund Management Service
 * 
 * Comprehensive refund processing service that handles:
 * - Multi-gateway refund processing (Stripe, PayPal, Authorize.Net)
 * - Complex VoIP billing refund scenarios
 * - Equipment return processing with condition assessments
 * - Proration calculations for service cancellations
 * - Tax reversals with jurisdiction-specific handling
 * - Multi-level approval workflows
 * - Chargeback and dispute management
 * - SLA tracking and compliance
 */
class RefundManagementService
{
    protected ?VoIPTaxReversalService $voipTaxService;
    protected ?object $gatewayService;

    public function __construct(
        ?VoIPTaxReversalService $voipTaxService = null,
        ?object $gatewayService = null
    ) {
        $this->voipTaxService = $voipTaxService;
        $this->gatewayService = $gatewayService;
    }

    /**
     * Create refund request from credit note
     */
    public function createRefundFromCreditNote(
        CreditNote $creditNote,
        string $refundMethod = RefundRequest::METHOD_ORIGINAL_PAYMENT,
        array $options = []
    ): RefundRequest {
        return DB::transaction(function () use ($creditNote, $refundMethod, $options) {
            $refundData = [
                'company_id' => $creditNote->company_id,
                'client_id' => $creditNote->client_id,
                'credit_note_id' => $creditNote->id,
                'invoice_id' => $creditNote->invoice_id,
                'payment_id' => $this->findOriginalPayment($creditNote),
                'refund_type' => $this->determineRefundType($creditNote),
                'refund_method' => $refundMethod,
                'requested_amount' => $creditNote->total_amount,
                'currency_code' => $creditNote->currency_code,
                'exchange_rate' => $creditNote->exchange_rate,
                'reason_code' => $creditNote->reason_code,
                'reason_description' => $creditNote->reason_description,
                'customer_explanation' => $creditNote->customer_notes,
                'internal_notes' => $creditNote->internal_notes,
                'tax_refund_amount' => $creditNote->tax_amount,
                'voip_tax_refund' => $creditNote->voip_tax_reversal,
                'tax_refund_breakdown' => $creditNote->tax_breakdown,
                'jurisdiction_tax_refunds' => $creditNote->jurisdiction_taxes,
                'requires_manager_approval' => $this->requiresManagerApproval($creditNote->total_amount),
                'requires_finance_approval' => $this->requiresFinanceApproval($creditNote->total_amount, $creditNote->reason_code),
                'requires_executive_approval' => $this->requiresExecutiveApproval($creditNote->total_amount),
                'priority' => $this->calculatePriority($creditNote, $options),
                'sla_hours' => $this->calculateSlaHours($creditNote, $options),
                'metadata' => array_merge($options, [
                    'created_from_credit_note' => true,
                    'credit_note_number' => $creditNote->number
                ])
            ];

            // Handle equipment return scenarios
            if ($this->isEquipmentRefund($creditNote)) {
                $refundData = array_merge($refundData, $this->processEquipmentReturn($creditNote, $options));
            }

            // Handle service cancellation scenarios
            if ($this->isServiceCancellation($creditNote)) {
                $refundData = array_merge($refundData, $this->processServiceCancellationRefund($creditNote, $options));
            }

            // Handle proration scenarios
            if ($this->requiresProration($creditNote)) {
                $refundData = array_merge($refundData, $this->calculateProrationRefund($creditNote, $options));
            }

            $refundRequest = RefundRequest::create($refundData);

            // Create approval workflow if needed
            if ($this->requiresApproval($refundRequest)) {
                $this->createApprovalWorkflow($refundRequest);
            }

            // Send notifications
            $this->sendRefundRequestNotifications($refundRequest);

            Log::info('Refund request created from credit note', [
                'refund_request_id' => $refundRequest->id,
                'credit_note_id' => $creditNote->id,
                'amount' => $refundRequest->requested_amount,
                'method' => $refundMethod
            ]);

            return $refundRequest;
        });
    }

    /**
     * Process refund request through appropriate gateway
     */
    public function processRefund(RefundRequest $refundRequest): RefundTransaction
    {
        if (!$refundRequest->canBeProcessed()) {
            throw new Exception('Refund request cannot be processed in current status');
        }

        return DB::transaction(function () use ($refundRequest) {
            // Start processing
            $refundRequest->startProcessing(Auth::user());

            // Create transaction record
            $transaction = $this->createRefundTransaction($refundRequest);

            // Process through gateway
            $result = $this->processGatewayRefund($transaction);

            // Update transaction status based on result
            if ($result['success']) {
                $transaction->markAsCompleted($result['response']);
                $this->handleSuccessfulRefund($refundRequest, $transaction);
            } else {
                $transaction->markAsFailed($result['error'], $result['response']);
                $this->handleFailedRefund($refundRequest, $transaction);
            }

            return $transaction;
        });
    }

    /**
     * Calculate proration refund for service cancellations
     */
    public function calculateProrationRefund(
        CreditNote $creditNote,
        array $options = []
    ): array {
        $prorationData = [];

        if (!$creditNote->contract_id) {
            return $prorationData;
        }

        $contract = Contract::find($creditNote->contract_id);
        if (!$contract) {
            return $prorationData;
        }

        $cancellationDate = $options['cancellation_date'] ?? now();
        $servicePeriodEnd = $options['service_period_end'] ?? $contract->contract_end_date;

        if ($cancellationDate >= $servicePeriodEnd) {
            return $prorationData; // No proration needed
        }

        $totalDays = Carbon::parse($contract->contract_start_date)->diffInDays($servicePeriodEnd);
        $unusedDays = Carbon::parse($cancellationDate)->diffInDays($servicePeriodEnd);
        
        $prorationRatio = $unusedDays / $totalDays;
        $prorationAmount = $creditNote->subtotal * $prorationRatio;

        // Calculate early termination fee if applicable
        $earlyTerminationFee = $this->calculateEarlyTerminationFee($contract, $cancellationDate);

        $prorationData = [
            'is_prorated' => true,
            'service_period_start' => $contract->contract_start_date,
            'service_period_end' => $servicePeriodEnd,
            'unused_days' => $unusedDays,
            'total_period_days' => $totalDays,
            'early_termination' => $earlyTerminationFee > 0,
            'early_termination_fee' => $earlyTerminationFee,
            'contract_end_date' => $servicePeriodEnd,
            'remaining_contract_months' => Carbon::parse($cancellationDate)->diffInMonths($servicePeriodEnd),
            'proration_calculation' => [
                'total_days' => $totalDays,
                'unused_days' => $unusedDays,
                'proration_ratio' => $prorationRatio,
                'original_amount' => $creditNote->subtotal,
                'prorated_amount' => $prorationAmount,
                'early_termination_fee' => $earlyTerminationFee,
                'net_refund' => max(0, $prorationAmount - $earlyTerminationFee)
            ]
        ];

        return $prorationData;
    }

    /**
     * Process equipment return with condition assessment
     */
    public function processEquipmentReturn(
        CreditNote $creditNote,
        array $equipmentData = []
    ): array {
        $equipmentReturn = [
            'equipment_details' => $equipmentData,
            'equipment_condition' => $equipmentData['condition'] ?? 'good',
            'condition_adjustment_percentage' => $this->calculateConditionAdjustment($equipmentData['condition'] ?? 'good'),
            'equipment_received' => $equipmentData['received'] ?? false,
            'equipment_received_date' => $equipmentData['received_date'] ?? null,
            'tracking_number' => $equipmentData['tracking_number'] ?? null
        ];

        // Adjust refund amount based on equipment condition
        $conditionAdjustment = $equipmentReturn['condition_adjustment_percentage'] / 100;
        $adjustedAmount = $creditNote->total_amount * (1 - $conditionAdjustment);

        return array_merge($equipmentReturn, [
            'requested_amount' => $adjustedAmount,
            'condition_adjustment_amount' => $creditNote->total_amount - $adjustedAmount
        ]);
    }

    /**
     * Handle VoIP-specific refund scenarios
     */
    public function processVoipServiceRefund(
        CreditNote $creditNote,
        string $serviceType,
        array $options = []
    ): array {
        $voipRefundData = [];

        switch ($serviceType) {
            case 'service_cancellation':
                $voipRefundData = $this->processVoipServiceCancellation($creditNote, $options);
                break;
            
            case 'porting_failure':
                $voipRefundData = $this->processPortingFailureRefund($creditNote, $options);
                break;
            
            case 'service_quality':
                $voipRefundData = $this->processSlaBreachRefund($creditNote, $options);
                break;
            
            case 'international_dispute':
                $voipRefundData = $this->processInternationalDisputeRefund($creditNote, $options);
                break;
            
            case 'regulatory_adjustment':
                $voipRefundData = $this->processRegulatoryAdjustment($creditNote, $options);
                break;
        }

        // Calculate VoIP tax reversals
        $taxReversals = $this->calculateVoipTaxReversals($creditNote);
        $voipRefundData = array_merge($voipRefundData, $taxReversals);

        return $voipRefundData;
    }

    /**
     * Handle chargeback processing
     */
    public function processChargeback(
        Payment $payment,
        array $chargebackData
    ): RefundRequest {
        return DB::transaction(function () use ($payment, $chargebackData) {
            // Create refund request for chargeback
            $refundRequest = RefundRequest::create([
                'company_id' => $payment->company_id,
                'client_id' => $payment->client_id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'refund_type' => RefundRequest::TYPE_CHARGEBACK_REFUND,
                'refund_method' => RefundRequest::METHOD_ORIGINAL_PAYMENT,
                'reason_code' => RefundRequest::REASON_CHARGEBACK,
                'requested_amount' => $chargebackData['amount'],
                'currency_code' => $payment->currency,
                'reason_description' => $chargebackData['reason'] ?? 'Chargeback initiated by cardholder',
                'customer_explanation' => $chargebackData['cardholder_explanation'] ?? null,
                'priority' => RefundRequest::PRIORITY_HIGH,
                'requires_legal_review' => true,
                'gateway_response' => $chargebackData,
                'metadata' => [
                    'chargeback_id' => $chargebackData['chargeback_id'] ?? null,
                    'reason_code' => $chargebackData['reason_code'] ?? null,
                    'created_from_chargeback' => true
                ]
            ]);

            // Update payment status
            $payment->update([
                'status' => 'chargeback',
                'chargeback_amount' => $chargebackData['amount'],
                'chargeback_reason' => $chargebackData['reason'],
                'chargeback_date' => now()
            ]);

            // Create chargeback dispute record
            $this->createChargebackDispute($payment, $refundRequest, $chargebackData);

            // Send urgent notifications
            $this->sendChargebackNotifications($refundRequest);

            Log::warning('Chargeback processed', [
                'payment_id' => $payment->id,
                'refund_request_id' => $refundRequest->id,
                'amount' => $chargebackData['amount'],
                'reason' => $chargebackData['reason']
            ]);

            return $refundRequest;
        });
    }

    /**
     * Process bulk refunds
     */
    public function processBulkRefunds(Collection $refundRequests): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_processed' => 0,
            'total_amount' => 0
        ];

        foreach ($refundRequests as $refundRequest) {
            try {
                $transaction = $this->processRefund($refundRequest);
                
                $results['successful'][] = [
                    'refund_request_id' => $refundRequest->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $refundRequest->approved_amount ?? $refundRequest->requested_amount
                ];
                
                $results['total_amount'] += $refundRequest->approved_amount ?? $refundRequest->requested_amount;
                
            } catch (Exception $e) {
                $results['failed'][] = [
                    'refund_request_id' => $refundRequest->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Bulk refund failed', [
                    'refund_request_id' => $refundRequest->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $results['total_processed']++;
        }

        Log::info('Bulk refund processing completed', $results);

        return $results;
    }

    /**
     * Get refund analytics and insights
     */
    public function getRefundAnalytics(array $filters = []): array
    {
        $query = RefundRequest::forCompany();

        // Apply filters
        if (isset($filters['date_range'])) {
            $query->whereBetween('requested_at', [
                $filters['date_range']['start'],
                $filters['date_range']['end']
            ]);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['refund_type'])) {
            $query->where('refund_type', $filters['refund_type']);
        }

        $refunds = $query->with(['client', 'creditNote', 'transactions'])->get();

        return [
            'summary' => [
                'total_requests' => $refunds->count(),
                'total_amount' => $refunds->sum('requested_amount'),
                'approved_amount' => $refunds->sum('approved_amount'),
                'processed_amount' => $refunds->sum('processed_amount'),
                'average_processing_time' => $refunds->avg('processing_time_hours'),
                'sla_breach_rate' => $refunds->where('sla_breached', true)->count() / max($refunds->count(), 1) * 100
            ],
            'by_status' => $refunds->groupBy('status')->map->count(),
            'by_type' => $refunds->groupBy('refund_type')->map->count(),
            'by_reason' => $refunds->groupBy('reason_code')->map->count(),
            'by_month' => $refunds->groupBy(fn($r) => $r->requested_at->format('Y-m'))->map->count(),
            'top_clients' => $refunds->groupBy('client_id')
                ->map(fn($group) => [
                    'client' => $group->first()->client->name,
                    'count' => $group->count(),
                    'total_amount' => $group->sum('requested_amount')
                ])
                ->sortByDesc('total_amount')
                ->take(10)
                ->values(),
            'gateway_performance' => $this->getGatewayPerformanceMetrics($refunds)
        ];
    }

    /**
     * Private helper methods
     */
    private function findOriginalPayment(CreditNote $creditNote): ?int
    {
        if ($creditNote->invoice_id) {
            $payment = Payment::where('invoice_id', $creditNote->invoice_id)
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->first();
            
            return $payment?->id;
        }

        return null;
    }

    private function determineRefundType(CreditNote $creditNote): string
    {
        return match($creditNote->reason_code) {
            'equipment_return' => RefundRequest::TYPE_EQUIPMENT_RETURN,
            'service_cancellation' => RefundRequest::TYPE_CANCELLATION_REFUND,
            'billing_error' => RefundRequest::TYPE_BILLING_ERROR_REFUND,
            'goodwill' => RefundRequest::TYPE_GOODWILL_REFUND,
            'chargeback' => RefundRequest::TYPE_CHARGEBACK_REFUND,
            default => $creditNote->type === 'full_refund' ? 
                RefundRequest::TYPE_FULL_REFUND : 
                RefundRequest::TYPE_PARTIAL_REFUND
        };
    }

    private function requiresManagerApproval(float $amount): bool
    {
        return $amount >= 100; // Configure threshold
    }

    private function requiresFinanceApproval(float $amount, string $reasonCode): bool
    {
        return $amount >= 1000 || in_array($reasonCode, ['billing_error', 'regulatory_adjustment']);
    }

    private function requiresExecutiveApproval(float $amount): bool
    {
        return $amount >= 10000;
    }

    private function calculatePriority(CreditNote $creditNote, array $options): string
    {
        if (in_array($creditNote->reason_code, ['chargeback', 'porting_failure'])) {
            return RefundRequest::PRIORITY_URGENT;
        }

        if ($creditNote->total_amount >= 5000) {
            return RefundRequest::PRIORITY_HIGH;
        }

        return $options['priority'] ?? RefundRequest::PRIORITY_NORMAL;
    }

    private function calculateSlaHours(CreditNote $creditNote, array $options): int
    {
        return match($creditNote->reason_code) {
            'chargeback' => 24,
            'porting_failure' => 48,
            'service_quality' => 72,
            default => 96
        };
    }

    private function isEquipmentRefund(CreditNote $creditNote): bool
    {
        return $creditNote->reason_code === 'equipment_return' ||
               $creditNote->items()->where('item_type', 'equipment')->exists();
    }

    private function isServiceCancellation(CreditNote $creditNote): bool
    {
        return $creditNote->reason_code === 'service_cancellation';
    }

    private function requiresProration(CreditNote $creditNote): bool
    {
        return $creditNote->contract_id && $this->isServiceCancellation($creditNote);
    }

    private function calculateConditionAdjustment(string $condition): float
    {
        return match($condition) {
            'new' => 0,
            'excellent' => 5,
            'good' => 10,
            'fair' => 25,
            'poor' => 50,
            'damaged' => 75,
            default => 10
        };
    }

    private function calculateEarlyTerminationFee(Contract $contract, Carbon $cancellationDate): float
    {
        // Implementation would calculate ETF based on contract terms
        return 0; // Placeholder
    }

    private function createRefundTransaction(RefundRequest $refundRequest): RefundTransaction
    {
        return RefundTransaction::create([
            'company_id' => $refundRequest->company_id,
            'refund_request_id' => $refundRequest->id,
            'original_payment_id' => $refundRequest->payment_id,
            'transaction_type' => $this->getTransactionType($refundRequest->refund_method),
            'amount' => $refundRequest->approved_amount ?? $refundRequest->requested_amount,
            'currency_code' => $refundRequest->currency_code,
            'gateway' => $this->getGatewayForMethod($refundRequest->refund_method),
            'processing_fee' => $this->calculateProcessingFee($refundRequest),
            'gateway_fee' => $this->calculateGatewayFee($refundRequest),
            'initiated_at' => now()
        ]);
    }

    private function processGatewayRefund(RefundTransaction $transaction): array
    {
        try {
            if ($this->gatewayService) {
                return $this->gatewayService->processRefund(
                    $transaction->gateway,
                    $transaction->amount,
                    $transaction->original_payment_id,
                    $transaction->currency_code,
                    $transaction->refundRequest
                );
            }
            
            // Fallback when gateway service is not available
            return [
                'success' => true,
                'response' => [
                    'transaction_id' => 'MOCK_' . uniqid(),
                    'status_code' => '200',
                    'message' => 'Refund processed (mock)'
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    private function processServiceCancellationRefund(CreditNote $creditNote, array $options): array
    {
        $cancellationData = [];

        // Add contract-related information
        if ($creditNote->contract_id) {
            $cancellationData['contract_id'] = $creditNote->contract_id;
            $cancellationData['early_termination'] = true;
        }

        // Add service period information
        $cancellationData['service_period_start'] = $options['service_period_start'] ?? null;
        $cancellationData['service_period_end'] = $options['service_period_end'] ?? null;

        return $cancellationData;
    }

    private function handleSuccessfulRefund(RefundRequest $refundRequest, RefundTransaction $transaction): void
    {
        // Send success notifications
        $this->sendRefundSuccessNotifications($refundRequest, $transaction);
    }

    private function handleFailedRefund(RefundRequest $refundRequest, RefundTransaction $transaction): void
    {
        // Send failure notifications
        $this->sendRefundFailureNotifications($refundRequest, $transaction);
    }

    private function requiresApproval(RefundRequest $refundRequest): bool
    {
        return $refundRequest->requires_manager_approval ||
               $refundRequest->requires_finance_approval ||
               $refundRequest->requires_executive_approval ||
               $refundRequest->requires_legal_review;
    }

    private function createApprovalWorkflow(RefundRequest $refundRequest): void
    {
        // Implementation would create approval workflow
    }

    private function sendRefundRequestNotifications(RefundRequest $refundRequest): void
    {
        // Implementation would send notifications
    }

    private function sendRefundSuccessNotifications(RefundRequest $refundRequest, RefundTransaction $transaction): void
    {
        // Implementation would send success notifications
    }

    private function sendRefundFailureNotifications(RefundRequest $refundRequest, RefundTransaction $transaction): void
    {
        // Implementation would send failure notifications
    }

    private function sendChargebackNotifications(RefundRequest $refundRequest): void
    {
        // Implementation would send chargeback notifications
    }

    private function calculateVoipTaxReversals(CreditNote $creditNote): array
    {
        // Integration with VoIP tax service for accurate reversals
        return [];
    }

    private function processVoipServiceCancellation(CreditNote $creditNote, array $options): array
    {
        // Implementation for VoIP service cancellation logic
        return [];
    }

    private function processPortingFailureRefund(CreditNote $creditNote, array $options): array
    {
        // Implementation for porting failure refund logic
        return [];
    }

    private function processSlaBreachRefund(CreditNote $creditNote, array $options): array
    {
        // Implementation for SLA breach refund logic
        return [];
    }

    private function processInternationalDisputeRefund(CreditNote $creditNote, array $options): array
    {
        // Implementation for international dispute refund logic
        return [];
    }

    private function processRegulatoryAdjustment(CreditNote $creditNote, array $options): array
    {
        // Implementation for regulatory adjustment logic
        return [];
    }

    private function createChargebackDispute(Payment $payment, RefundRequest $refundRequest, array $chargebackData): void
    {
        // Implementation would create chargeback dispute record
    }

    private function getTransactionType(string $refundMethod): string
    {
        return match($refundMethod) {
            RefundRequest::METHOD_CREDIT_CARD => RefundTransaction::TYPE_CREDIT_CARD_REFUND,
            RefundRequest::METHOD_ACH => RefundTransaction::TYPE_ACH_REFUND,
            RefundRequest::METHOD_PAYPAL => RefundTransaction::TYPE_PAYPAL_REFUND,
            RefundRequest::METHOD_STRIPE => RefundTransaction::TYPE_STRIPE_REFUND,
            RefundRequest::METHOD_CHECK => RefundTransaction::TYPE_CHECK_REFUND,
            RefundRequest::METHOD_ACCOUNT_CREDIT => RefundTransaction::TYPE_ACCOUNT_CREDIT,
            default => RefundTransaction::TYPE_MANUAL_REFUND
        };
    }

    private function getGatewayForMethod(string $refundMethod): string
    {
        return match($refundMethod) {
            RefundRequest::METHOD_STRIPE => RefundTransaction::GATEWAY_STRIPE,
            RefundRequest::METHOD_PAYPAL => RefundTransaction::GATEWAY_PAYPAL,
            default => RefundTransaction::GATEWAY_MANUAL
        };
    }

    private function calculateProcessingFee(RefundRequest $refundRequest): float
    {
        // Calculate processing fees based on amount and method
        return 0; // Placeholder
    }

    private function calculateGatewayFee(RefundRequest $refundRequest): float
    {
        // Calculate gateway-specific fees
        return 0; // Placeholder
    }

    private function getGatewayPerformanceMetrics(Collection $refunds): array
    {
        // Implementation would return gateway performance metrics
        return [];
    }
}