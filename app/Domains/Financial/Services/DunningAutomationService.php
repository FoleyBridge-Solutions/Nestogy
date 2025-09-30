<?php

namespace App\Domains\Financial\Services;

use App\Models\AccountHold;
use App\Models\Client;
use App\Models\CollectionNote;
use App\Models\DunningAction;
use App\Models\DunningCampaign;
use App\Models\DunningSequence;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Dunning Automation Service
 *
 * Handles sophisticated collection workflows with automated communication,
 * escalation logic, payment plan management, and VoIP-specific features.
 */
class DunningAutomationService
{
    protected VoIPTaxReversalService $voipTaxService;

    public function __construct(VoIPTaxReversalService $voipTaxService)
    {
        $this->voipTaxService = $voipTaxService;
    }

    /**
     * Process all overdue invoices and trigger appropriate dunning campaigns.
     */
    public function processOverdueInvoices(): array
    {
        Log::info('Starting dunning automation process');

        $results = [
            'invoices_processed' => 0,
            'campaigns_triggered' => 0,
            'actions_scheduled' => 0,
            'errors' => [],
        ];

        try {
            // Get all active campaigns
            $campaigns = DunningCampaign::active()->with('sequences')->get();

            foreach ($campaigns as $campaign) {
                $campaignResults = $this->processCampaign($campaign);

                $results['invoices_processed'] += $campaignResults['invoices_processed'];
                $results['campaigns_triggered'] += $campaignResults['campaigns_triggered'];
                $results['actions_scheduled'] += $campaignResults['actions_scheduled'];
                $results['errors'] = array_merge($results['errors'], $campaignResults['errors']);
            }

            Log::info('Dunning automation completed', $results);

        } catch (\Exception $e) {
            Log::error('Dunning automation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Process a specific dunning campaign.
     */
    public function processCampaign(DunningCampaign $campaign): array
    {
        $results = [
            'invoices_processed' => 0,
            'campaigns_triggered' => 0,
            'actions_scheduled' => 0,
            'errors' => [],
        ];

        try {
            // Get eligible invoices for this campaign
            $eligibleInvoices = $campaign->getEligibleInvoices();

            Log::info("Processing campaign: {$campaign->name}", [
                'eligible_invoices' => $eligibleInvoices->count(),
            ]);

            foreach ($eligibleInvoices as $invoice) {
                try {
                    $invoiceResults = $this->processInvoiceForCampaign($invoice, $campaign);

                    $results['invoices_processed']++;
                    $results['actions_scheduled'] += $invoiceResults['actions_scheduled'];

                    if ($invoiceResults['campaign_triggered']) {
                        $results['campaigns_triggered']++;
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to process invoice for campaign', [
                        'invoice_id' => $invoice->id,
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);
                    $results['errors'][] = "Invoice {$invoice->id}: {$e->getMessage()}";
                }
            }

            // Update campaign performance metrics
            $campaign->updatePerformanceMetrics();

        } catch (\Exception $e) {
            Log::error('Campaign processing failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = "Campaign {$campaign->id}: {$e->getMessage()}";
        }

        return $results;
    }

    /**
     * Process an invoice for a specific campaign.
     */
    protected function processInvoiceForCampaign(Invoice $invoice, DunningCampaign $campaign): array
    {
        $results = [
            'campaign_triggered' => false,
            'actions_scheduled' => 0,
        ];

        // Check if campaign is already running for this invoice
        $existingActions = DunningAction::where('invoice_id', $invoice->id)
            ->where('campaign_id', $campaign->id)
            ->where('status', '!=', DunningAction::STATUS_COMPLETED)
            ->where('status', '!=', DunningAction::STATUS_CANCELLED)
            ->exists();

        if ($existingActions && ! $campaign->auto_escalate) {
            return $results; // Campaign already running
        }

        // Check risk assessment
        $riskAssessment = $this->assessClientRisk($invoice->client);

        if (! $this->shouldTriggerCampaign($invoice, $campaign, $riskAssessment)) {
            return $results;
        }

        // Calculate accurate collection amount including VoIP taxes
        $collectionAmount = $this->calculateAccurateCollectionAmount($invoice);

        // Create dunning sequence for this invoice
        $this->createDunningSequence($invoice, $campaign, $collectionAmount, $riskAssessment);

        $results['campaign_triggered'] = true;
        $results['actions_scheduled'] = $campaign->sequences->count();

        return $results;
    }

    /**
     * Determine if campaign should be triggered for an invoice.
     */
    protected function shouldTriggerCampaign(
        Invoice $invoice,
        DunningCampaign $campaign,
        array $riskAssessment
    ): bool {
        // Check basic campaign eligibility
        if (! $campaign->canTriggerForInvoice($invoice)) {
            return false;
        }

        // Check risk level alignment
        if ($campaign->risk_level !== $riskAssessment['risk_level']) {
            return false;
        }

        // Check if within contact hours
        if (! $campaign->isWithinContactHours()) {
            return false;
        }

        // Check for blackout dates
        if ($campaign->isBlackoutDate()) {
            return false;
        }

        // Check for existing payment plans
        $hasActivePlan = PaymentPlan::where('client_id', $invoice->client_id)
            ->where('status', PaymentPlan::STATUS_ACTIVE)
            ->exists();

        if ($hasActivePlan && $campaign->campaign_type !== DunningCampaign::TYPE_GENTLE) {
            return false;
        }

        // Check for recent disputes
        $hasRecentDispute = CollectionNote::where('client_id', $invoice->client_id)
            ->where('contains_dispute', true)
            ->where('dispute_status', 'pending')
            ->exists();

        if ($hasRecentDispute && $campaign->campaign_type === DunningCampaign::TYPE_AGGRESSIVE) {
            return false;
        }

        return true;
    }

    /**
     * Calculate accurate collection amount including VoIP taxes and fees.
     */
    protected function calculateAccurateCollectionAmount(Invoice $invoice): float
    {
        $baseAmount = $invoice->getBalance();

        // Add VoIP tax recalculations if needed
        if ($invoice->hasVoIPServices()) {
            $taxCalculations = $invoice->calculateVoIPTaxes();
            // Tax adjustments for collection might be different
        }

        // Add late fees based on age
        $daysOverdue = Carbon::now()->diffInDays($invoice->due_date);
        $lateFees = $this->calculateLateFees($invoice, $daysOverdue);

        // Add collection fees if applicable
        $collectionFees = $this->calculateCollectionFees($invoice);

        return $baseAmount + $lateFees + $collectionFees;
    }

    /**
     * Create dunning sequence actions for an invoice.
     */
    protected function createDunningSequence(
        Invoice $invoice,
        DunningCampaign $campaign,
        float $collectionAmount,
        array $riskAssessment
    ): void {
        $sequences = $campaign->sequences()->active()->orderByStep()->get();
        $triggerDate = Carbon::now();
        $previousStepDate = null;

        foreach ($sequences as $sequence) {
            // Calculate when this sequence should execute
            $executionTime = $sequence->calculateExecutionTime($triggerDate, $previousStepDate);

            // Create dunning action
            $action = DunningAction::create([
                'company_id' => $campaign->company_id,
                'campaign_id' => $campaign->id,
                'sequence_id' => $sequence->id,
                'client_id' => $invoice->client_id,
                'invoice_id' => $invoice->id,
                'action_type' => $sequence->action_type,
                'status' => DunningAction::STATUS_SCHEDULED,
                'scheduled_at' => $executionTime,
                'recipient_email' => $invoice->client->email,
                'recipient_phone' => $invoice->client->phone,
                'recipient_name' => $invoice->client->getDisplayName(),
                'invoice_amount' => $invoice->amount,
                'amount_due' => $collectionAmount,
                'days_overdue' => Carbon::now()->diffInDays($invoice->due_date),
                'final_notice' => $sequence->final_notice,
                'legal_action_threatened' => $sequence->legal_threat,
                'settlement_offer_amount' => $sequence->settlement_percentage ?
                    $collectionAmount * ($sequence->settlement_percentage / 100) : null,
                'created_by' => 1, // System user
            ]);

            // Prepare personalized message
            if ($sequence->isCommunicationAction()) {
                $personalizedMessage = $sequence->getPersonalizedMessage($invoice, $invoice->client);
                $action->update([
                    'message_subject' => $this->generateSubjectLine($sequence, $invoice),
                    'message_content' => $personalizedMessage,
                    'template_used' => $sequence->email_template_id ?: $sequence->sms_template_id,
                ]);
            }

            // Handle service suspension actions
            if ($sequence->action_type === DunningSequence::ACTION_SERVICE_SUSPENSION) {
                $this->prepareServiceSuspension($action, $sequence, $invoice);
            }

            // Handle payment plan offers
            if ($sequence->action_type === DunningSequence::ACTION_PAYMENT_PLAN_OFFER) {
                $this->preparePaymentPlanOffer($action, $sequence, $invoice, $riskAssessment);
            }

            $previousStepDate = $executionTime;

            Log::info('Dunning action scheduled', [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'scheduled_at' => $executionTime->toDateTimeString(),
            ]);
        }
    }

    /**
     * Execute scheduled dunning actions.
     */
    public function executeScheduledActions(): array
    {
        Log::info('Executing scheduled dunning actions');

        $results = [
            'actions_executed' => 0,
            'actions_failed' => 0,
            'errors' => [],
        ];

        // Get actions ready to execute
        $readyActions = DunningAction::readyToExecute()
            ->with(['sequence', 'campaign', 'client', 'invoice'])
            ->get();

        foreach ($readyActions as $action) {
            try {
                $this->executeAction($action);
                $results['actions_executed']++;

            } catch (\Exception $e) {
                Log::error('Action execution failed', [
                    'action_id' => $action->id,
                    'error' => $e->getMessage(),
                ]);

                $action->markAsFailed($e->getMessage());
                $results['actions_failed']++;
                $results['errors'][] = "Action {$action->id}: {$e->getMessage()}";
            }
        }

        // Process failed actions that can be retried
        $retryActions = DunningAction::where('status', DunningAction::STATUS_FAILED)
            ->where('next_retry_at', '<=', Carbon::now())
            ->with(['sequence', 'campaign', 'client', 'invoice'])
            ->get();

        foreach ($retryActions as $action) {
            try {
                $this->executeAction($action);
                $results['actions_executed']++;

            } catch (\Exception $e) {
                $action->markAsFailed($e->getMessage());
                $results['actions_failed']++;
            }
        }

        Log::info('Scheduled actions execution completed', $results);

        return $results;
    }

    /**
     * Execute a specific dunning action.
     */
    public function executeAction(DunningAction $action): void
    {
        Log::info('Executing dunning action', [
            'action_id' => $action->id,
            'action_type' => $action->action_type,
        ]);

        // Update action status
        $action->update(['status' => DunningAction::STATUS_PROCESSING]);

        try {
            switch ($action->action_type) {
                case DunningAction::ACTION_EMAIL:
                    $this->executeEmailAction($action);
                    break;

                case DunningAction::ACTION_SMS:
                    $this->executeSmsAction($action);
                    break;

                case DunningAction::ACTION_PHONE_CALL:
                    $this->executePhoneCallAction($action);
                    break;

                case DunningAction::ACTION_LETTER:
                    $this->executeLetterAction($action);
                    break;

                case DunningAction::ACTION_SERVICE_SUSPENSION:
                    $this->executeServiceSuspension($action);
                    break;

                case DunningAction::ACTION_SERVICE_RESTORATION:
                    $this->executeServiceRestoration($action);
                    break;

                case DunningAction::ACTION_PAYMENT_PLAN_OFFER:
                    $this->executePaymentPlanOffer($action);
                    break;

                case DunningAction::ACTION_LEGAL_HANDOFF:
                    $this->executeLegalHandoff($action);
                    break;

                case DunningAction::ACTION_COLLECTION_AGENCY:
                    $this->executeCollectionAgencyHandoff($action);
                    break;

                default:
                    throw new \InvalidArgumentException("Unknown action type: {$action->action_type}");
            }

            // Check if sequence should pause
            if ($action->sequence->shouldPauseSequence($action)) {
                $this->pauseSequence($action);
            }

        } catch (\Exception $e) {
            $action->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute email action.
     */
    protected function executeEmailAction(DunningAction $action): void
    {
        if (! $action->recipient_email) {
            throw new \InvalidArgumentException('No email address available for client');
        }

        // This would integrate with email service
        $emailService = app('email_service'); // Placeholder

        $result = $emailService->send([
            'to' => $action->recipient_email,
            'subject' => $action->message_subject,
            'content' => $action->message_content,
            'template' => $action->template_used,
            'tracking' => true,
        ]);

        $action->markAsSent([
            'email_message_id' => $result['message_id'] ?? null,
            'provider' => $result['provider'] ?? null,
        ]);

        Log::info('Email dunning action sent', [
            'action_id' => $action->id,
            'recipient' => $action->recipient_email,
        ]);
    }

    /**
     * Execute SMS action.
     */
    protected function executeSmsAction(DunningAction $action): void
    {
        if (! $action->recipient_phone) {
            throw new \InvalidArgumentException('No phone number available for client');
        }

        // This would integrate with SMS service (Twilio, etc.)
        $smsService = app('sms_service'); // Placeholder

        $result = $smsService->send([
            'to' => $action->recipient_phone,
            'message' => $action->message_content,
            'tracking' => true,
        ]);

        $action->markAsSent([
            'sms_message_id' => $result['message_id'] ?? null,
            'provider' => $result['provider'] ?? null,
        ]);

        Log::info('SMS dunning action sent', [
            'action_id' => $action->id,
            'recipient' => $action->recipient_phone,
        ]);
    }

    /**
     * Execute phone call action.
     */
    protected function executePhoneCallAction(DunningAction $action): void
    {
        if (! $action->recipient_phone) {
            throw new \InvalidArgumentException('No phone number available for client');
        }

        // This would integrate with voice service
        $voiceService = app('voice_service'); // Placeholder

        $result = $voiceService->call([
            'to' => $action->recipient_phone,
            'message' => $action->message_content,
            'recording' => true,
        ]);

        $action->markAsSent([
            'call_session_id' => $result['session_id'] ?? null,
            'provider' => $result['provider'] ?? null,
        ]);

        Log::info('Voice call dunning action initiated', [
            'action_id' => $action->id,
            'recipient' => $action->recipient_phone,
        ]);
    }

    /**
     * Execute letter action.
     */
    protected function executeLetterAction(DunningAction $action): void
    {
        // This would integrate with postal service
        $postalService = app('postal_service'); // Placeholder

        $result = $postalService->send([
            'to_address' => $action->client->getFullAddress(),
            'content' => $action->message_content,
            'certified' => $action->final_notice,
        ]);

        $action->markAsSent([
            'postal_tracking' => $result['tracking_number'] ?? null,
            'provider' => $result['provider'] ?? null,
        ]);

        Log::info('Letter dunning action sent', [
            'action_id' => $action->id,
            'recipient' => $action->client->name,
        ]);
    }

    /**
     * Execute service suspension.
     */
    protected function executeServiceSuspension(DunningAction $action): void
    {
        // Create account hold for service suspension
        $hold = AccountHold::create([
            'company_id' => $action->company_id,
            'client_id' => $action->client_id,
            'invoice_id' => $action->invoice_id,
            'dunning_action_id' => $action->id,
            'hold_type' => AccountHold::TYPE_SERVICE_SUSPENSION,
            'status' => AccountHold::STATUS_PENDING,
            'severity' => AccountHold::SEVERITY_HIGH,
            'title' => 'Service Suspension - Non-Payment',
            'description' => 'Service suspension due to non-payment of invoice',
            'reason' => "Non-payment of invoice {$action->invoice->getFullNumber()}",
            'amount_threshold' => $action->amount_due,
            'days_overdue' => $action->days_overdue,
            'scheduled_at' => Carbon::now(),
            'services_affected' => $action->suspended_services,
            'essential_services_maintained' => $action->maintained_services,
            'graceful_suspension' => $action->sequence->graceful_suspension,
            'maintain_e911' => true, // Always maintain E911
            'payment_required_amount' => $action->amount_due,
            'created_by' => $action->created_by,
        ]);

        // Activate the hold
        $hold->activate();

        $action->update([
            'suspension_effective_at' => Carbon::now(),
            'status' => DunningAction::STATUS_COMPLETED,
        ]);

        Log::info('Service suspension executed', [
            'action_id' => $action->id,
            'hold_id' => $hold->id,
        ]);
    }

    /**
     * Execute service restoration.
     */
    protected function executeServiceRestoration(DunningAction $action): void
    {
        // Find and lift account holds for this client
        $holds = AccountHold::active()
            ->where('client_id', $action->client_id)
            ->where('hold_type', AccountHold::TYPE_SERVICE_SUSPENSION)
            ->get();

        foreach ($holds as $hold) {
            $hold->lift($action->created_by, 'Payment received - restoring services');
        }

        $action->update([
            'restoration_scheduled_at' => Carbon::now(),
            'status' => DunningAction::STATUS_COMPLETED,
        ]);

        Log::info('Service restoration executed', [
            'action_id' => $action->id,
            'holds_lifted' => $holds->count(),
        ]);
    }

    /**
     * Execute payment plan offer.
     */
    protected function executePaymentPlanOffer(DunningAction $action): void
    {
        // This would integrate with PaymentPlanService when available
        $planTerms = [
            'monthly_payment' => $action->amount_due / 6, // Default 6-month plan
            'number_of_payments' => 6,
            'settlement_percentage' => $action->sequence->settlement_percentage,
        ];

        // This would send communication with payment plan offer
        $this->executeEmailAction($action);

        Log::info('Payment plan offer sent', [
            'action_id' => $action->id,
            'plan_terms' => $planTerms,
        ]);
    }

    /**
     * Execute legal handoff.
     */
    protected function executeLegalHandoff(DunningAction $action): void
    {
        // Create collection note for legal review
        CollectionNote::create([
            'company_id' => $action->company_id,
            'client_id' => $action->client_id,
            'invoice_id' => $action->invoice_id,
            'dunning_action_id' => $action->id,
            'note_type' => CollectionNote::TYPE_LEGAL_ACTION,
            'priority' => CollectionNote::PRIORITY_HIGH,
            'subject' => 'Account Referred for Legal Action',
            'content' => "Account referred for legal action due to non-payment of {$action->invoice->getFullNumber()}",
            'legally_significant' => true,
            'attorney_review_required' => true,
            'created_by' => $action->created_by,
        ]);

        $action->update([
            'status' => DunningAction::STATUS_COMPLETED,
            'escalated' => true,
            'escalation_level' => DunningAction::ESCALATION_LEGAL,
            'escalated_at' => Carbon::now(),
        ]);

        Log::info('Legal handoff executed', [
            'action_id' => $action->id,
        ]);
    }

    /**
     * Execute collection agency handoff.
     */
    protected function executeCollectionAgencyHandoff(DunningAction $action): void
    {
        // This would integrate with collection agency API
        $collectionAgency = app('collection_agency'); // Placeholder

        $result = $collectionAgency->submitAccount([
            'client_info' => $action->client->toArray(),
            'invoice_info' => $action->invoice->toArray(),
            'amount_due' => $action->amount_due,
            'days_overdue' => $action->days_overdue,
        ]);

        $action->update([
            'status' => DunningAction::STATUS_COMPLETED,
            'escalated' => true,
            'escalation_level' => DunningAction::ESCALATION_COLLECTION_AGENCY,
            'escalated_at' => Carbon::now(),
            'delivery_metadata' => $result,
        ]);

        Log::info('Collection agency handoff executed', [
            'action_id' => $action->id,
        ]);
    }

    /**
     * Calculate late fees based on invoice age and terms.
     */
    protected function calculateLateFees(Invoice $invoice, int $daysOverdue): float
    {
        // This would implement late fee calculation logic
        // Based on company policy, contract terms, etc.
        return 0; // Placeholder
    }

    /**
     * Calculate collection fees.
     */
    protected function calculateCollectionFees(Invoice $invoice): float
    {
        // This would implement collection fee calculation
        // Based on company policy, state laws, etc.
        return 0; // Placeholder
    }

    /**
     * Generate subject line for communication.
     */
    protected function generateSubjectLine(DunningSequence $sequence, Invoice $invoice): string
    {
        $subjects = [
            DunningSequence::ACTION_EMAIL => [
                1 => 'Payment Reminder - Invoice '.$invoice->getFullNumber(),
                2 => 'Past Due Notice - Invoice '.$invoice->getFullNumber(),
                3 => 'URGENT: Final Notice - Invoice '.$invoice->getFullNumber(),
            ],
        ];

        return $subjects[$sequence->action_type][$sequence->step_number] ??
               'Collection Notice - Invoice '.$invoice->getFullNumber();
    }

    /**
     * Prepare service suspension details.
     */
    protected function prepareServiceSuspension(
        DunningAction $action,
        DunningSequence $sequence,
        Invoice $invoice
    ): void {
        $action->update([
            'suspended_services' => $sequence->services_to_suspend,
            'maintained_services' => $sequence->essential_services_to_maintain,
            'suspension_reason' => 'Non-payment of invoice '.$invoice->getFullNumber(),
        ]);
    }

    /**
     * Prepare payment plan offer details.
     */
    protected function preparePaymentPlanOffer(
        DunningAction $action,
        DunningSequence $sequence,
        Invoice $invoice,
        array $riskAssessment
    ): void {
        // This would calculate payment plan terms based on risk assessment
        // and sequence configuration
    }

    /**
     * Pause sequence execution.
     */
    protected function pauseSequence(DunningAction $action): void
    {
        DunningAction::where('campaign_id', $action->campaign_id)
            ->where('client_id', $action->client_id)
            ->where('invoice_id', $action->invoice_id)
            ->where('status', DunningAction::STATUS_SCHEDULED)
            ->update([
                'pause_sequence' => true,
                'pause_reason' => 'Sequence paused due to customer interaction',
            ]);

        Log::info('Dunning sequence paused', [
            'campaign_id' => $action->campaign_id,
            'client_id' => $action->client_id,
            'invoice_id' => $action->invoice_id,
        ]);
    }

    /**
     * Basic client risk assessment (temporary until CollectionManagementService is built).
     */
    protected function assessClientRisk(Client $client): array
    {
        // Calculate basic risk factors
        $pastDueAmount = $client->getPastDueAmount();
        $paymentHistory = $client->payments()->where('created_at', '>=', Carbon::now()->subMonths(6))->count();
        $missedPayments = $client->invoices()->where('status', Invoice::STATUS_OVERDUE)->count();

        // Determine risk level
        $riskScore = 0;
        if ($pastDueAmount > 1000) {
            $riskScore += 30;
        }
        if ($missedPayments > 2) {
            $riskScore += 25;
        }
        if ($paymentHistory < 3) {
            $riskScore += 20;
        }
        if ($client->isSuspended()) {
            $riskScore += 25;
        }

        $riskLevel = 'low';
        if ($riskScore >= 70) {
            $riskLevel = 'critical';
        } elseif ($riskScore >= 50) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'medium';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'past_due_amount' => $pastDueAmount,
            'missed_payments' => $missedPayments,
            'payment_history_count' => $paymentHistory,
        ];
    }
}
