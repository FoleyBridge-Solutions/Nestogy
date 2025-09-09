<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\CollectionNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Payment Plan Service
 * 
 * Handles sophisticated payment plan creation, modification, processing,
 * and optimization with intelligent risk-based calculations and automated monitoring.
 */
class PaymentPlanService
{
    protected CollectionManagementService $collectionService;
    protected string $cachePrefix = 'payment_plan:';
    protected int $cacheTtl = 1800; // 30 minutes

    public function __construct(CollectionManagementService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * Create an optimal payment plan for a client based on their financial situation.
     */
    public function createOptimalPaymentPlan(
        Client $client, 
        float $totalAmount, 
        array $options = []
    ): array {
        // Get client risk assessment
        $riskAssessment = $this->collectionService->assessClientRisk($client);
        
        // Calculate optimal payment plan parameters
        $planOptions = $this->calculateOptimalPlanOptions($client, $totalAmount, $riskAssessment, $options);
        
        // Select the best plan option
        $selectedPlan = $this->selectBestPlanOption($planOptions, $riskAssessment);
        
        Log::info('Optimal payment plan created', [
            'client_id' => $client->id,
            'total_amount' => $totalAmount,
            'selected_plan' => $selectedPlan['plan_type'],
            'monthly_payment' => $selectedPlan['monthly_payment'],
            'duration_months' => $selectedPlan['duration_months']
        ]);

        return $selectedPlan;
    }

    /**
     * Calculate multiple payment plan options based on client profile.
     */
    protected function calculateOptimalPlanOptions(
        Client $client, 
        float $totalAmount, 
        array $riskAssessment, 
        array $options = []
    ): array {
        $monthlyRevenue = $client->getMonthlyRecurring();
        $riskLevel = $riskAssessment['risk_level'];
        $paymentHistory = $riskAssessment['factors']['payment_history'] ?? [];
        
        $planOptions = [];

        // Conservative Plan (Low Risk)
        if ($riskLevel === 'low') {
            $planOptions[] = $this->createPlanOption([
                'plan_type' => 'conservative',
                'total_amount' => $totalAmount,
                'duration_months' => min(12, max(3, ceil($totalAmount / ($monthlyRevenue * 0.15)))),
                'down_payment_percent' => 10,
                'interest_rate' => 0,
                'setup_fee' => 0,
                'priority' => 1
            ]);
        }

        // Standard Plan (Medium Risk)
        $planOptions[] = $this->createPlanOption([
            'plan_type' => 'standard',
            'total_amount' => $totalAmount,
            'duration_months' => min(6, max(2, ceil($totalAmount / ($monthlyRevenue * 0.20)))),
            'down_payment_percent' => 15,
            'interest_rate' => 0,
            'setup_fee' => 25,
            'priority' => 2
        ]);

        // Aggressive Plan (High Risk)
        if ($riskLevel === 'high' || $riskLevel === 'critical') {
            $planOptions[] = $this->createPlanOption([
                'plan_type' => 'aggressive',
                'total_amount' => $totalAmount,
                'duration_months' => min(3, max(1, ceil($totalAmount / ($monthlyRevenue * 0.30)))),
                'down_payment_percent' => 25,
                'interest_rate' => 1.5,
                'setup_fee' => 50,
                'priority' => 3
            ]);
        }

        // Custom options from input
        if (!empty($options['custom_duration'])) {
            $planOptions[] = $this->createPlanOption([
                'plan_type' => 'custom',
                'total_amount' => $totalAmount,
                'duration_months' => $options['custom_duration'],
                'down_payment_percent' => $options['down_payment_percent'] ?? 20,
                'interest_rate' => $options['interest_rate'] ?? 0,
                'setup_fee' => $options['setup_fee'] ?? 0,
                'priority' => 4
            ]);
        }

        return $planOptions;
    }

    /**
     * Create a payment plan option with calculated parameters.
     */
    protected function createPlanOption(array $params): array
    {
        $totalAmount = $params['total_amount'];
        $durationMonths = $params['duration_months'];
        $downPaymentPercent = $params['down_payment_percent'];
        $interestRate = $params['interest_rate'] / 100; // Convert to decimal
        $setupFee = $params['setup_fee'];

        $downPayment = $totalAmount * ($downPaymentPercent / 100);
        $principalAmount = $totalAmount - $downPayment;
        
        // Calculate monthly payment with interest
        if ($interestRate > 0) {
            $monthlyRate = $interestRate / 12;
            $monthlyPayment = $principalAmount * ($monthlyRate * pow(1 + $monthlyRate, $durationMonths)) / 
                             (pow(1 + $monthlyRate, $durationMonths) - 1);
        } else {
            $monthlyPayment = $principalAmount / $durationMonths;
        }

        $totalWithInterest = $downPayment + ($monthlyPayment * $durationMonths) + $setupFee;

        return [
            'plan_type' => $params['plan_type'],
            'total_amount' => $totalAmount,
            'down_payment' => round($downPayment, 2),
            'monthly_payment' => round($monthlyPayment, 2),
            'duration_months' => $durationMonths,
            'interest_rate' => $params['interest_rate'],
            'setup_fee' => $setupFee,
            'total_with_interest' => round($totalWithInterest, 2),
            'total_interest' => round($totalWithInterest - $totalAmount, 2),
            'priority' => $params['priority'],
            'affordability_ratio' => 0, // Will be calculated
            'success_probability' => 0  // Will be calculated
        ];
    }

    /**
     * Select the best payment plan option based on risk and affordability.
     */
    protected function selectBestPlanOption(array $planOptions, array $riskAssessment): array
    {
        $client = $riskAssessment['client_id'];
        $monthlyRevenue = 1000; // Default fallback, should get from client

        // Calculate affordability and success probability for each option
        foreach ($planOptions as &$option) {
            $option['affordability_ratio'] = $option['monthly_payment'] / $monthlyRevenue;
            $option['success_probability'] = $this->calculateSuccessProbability($option, $riskAssessment);
        }

        // Sort by success probability (descending) and affordability (ascending)
        usort($planOptions, function ($a, $b) {
            if ($a['success_probability'] === $b['success_probability']) {
                return $a['affordability_ratio'] <=> $b['affordability_ratio'];
            }
            return $b['success_probability'] <=> $a['success_probability'];
        });

        return $planOptions[0];
    }

    /**
     * Calculate success probability for a payment plan option.
     */
    protected function calculateSuccessProbability(array $planOption, array $riskAssessment): float
    {
        $baseSuccessRate = 70; // Base 70% success rate
        
        // Adjust based on risk level
        switch ($riskAssessment['risk_level']) {
            case 'low':
                $baseSuccessRate = 85;
                break;
            case 'medium':
                $baseSuccessRate = 70;
                break;
            case 'high':
                $baseSuccessRate = 50;
                break;
            case 'critical':
                $baseSuccessRate = 25;
                break;
        }

        // Adjust based on affordability
        if ($planOption['affordability_ratio'] > 0.3) {
            $baseSuccessRate -= 20;
        } elseif ($planOption['affordability_ratio'] > 0.2) {
            $baseSuccessRate -= 10;
        }

        // Adjust based on plan duration
        if ($planOption['duration_months'] > 6) {
            $baseSuccessRate -= 10;
        } elseif ($planOption['duration_months'] > 12) {
            $baseSuccessRate -= 20;
        }

        // Adjust based on down payment
        if ($planOption['down_payment'] > 0) {
            $baseSuccessRate += 15;
        }

        return max(10, min(95, $baseSuccessRate));
    }

    /**
     * Create a payment plan record in the database.
     */
    public function createPaymentPlan(
        Client $client,
        array $invoiceIds,
        array $planDetails,
        array $options = []
    ): PaymentPlan {
        return DB::transaction(function () use ($client, $invoiceIds, $planDetails, $options) {
            // Create the payment plan
            $paymentPlan = PaymentPlan::create([
                'client_id' => $client->id,
                'total_amount' => $planDetails['total_amount'],
                'down_payment' => $planDetails['down_payment'],
                'monthly_payment' => $planDetails['monthly_payment'],
                'duration_months' => $planDetails['duration_months'],
                'interest_rate' => $planDetails['interest_rate'],
                'setup_fee' => $planDetails['setup_fee'],
                'status' => PaymentPlan::STATUS_PENDING,
                'start_date' => $options['start_date'] ?? Carbon::now()->addDays(3),
                'created_by' => auth()->id() ?? 1,
                'notes' => $options['notes'] ?? '',
                'terms_and_conditions' => $this->generateTermsAndConditions($planDetails)
            ]);

            // Link invoices to the payment plan
            $paymentPlan->invoices()->attach($invoiceIds);

            // Generate payment schedule
            $this->generatePaymentSchedule($paymentPlan);

            // Create collection note
            CollectionNote::create([
                'client_id' => $client->id,
                'payment_plan_id' => $paymentPlan->id,
                'note_type' => CollectionNote::TYPE_PAYMENT_PLAN,
                'content' => "Payment plan created: {$planDetails['duration_months']} months, $" . 
                           number_format($planDetails['monthly_payment'], 2) . " per month",
                'outcome' => CollectionNote::OUTCOME_PAYMENT_PLAN_CREATED,
                'follow_up_date' => $paymentPlan->start_date,
                'created_by' => auth()->id() ?? 1
            ]);

            Log::info('Payment plan created', [
                'payment_plan_id' => $paymentPlan->id,
                'client_id' => $client->id,
                'total_amount' => $planDetails['total_amount'],
                'duration_months' => $planDetails['duration_months']
            ]);

            return $paymentPlan;
        });
    }

    /**
     * Generate payment schedule for a payment plan.
     */
    protected function generatePaymentSchedule(PaymentPlan $paymentPlan): void
    {
        $schedule = [];
        $currentDate = Carbon::parse($paymentPlan->start_date);

        // Down payment (if applicable)
        if ($paymentPlan->down_payment > 0) {
            $schedule[] = [
                'due_date' => $currentDate->copy(),
                'amount' => $paymentPlan->down_payment,
                'type' => 'down_payment',
                'status' => 'pending'
            ];
        }

        // Monthly payments
        for ($month = 1; $month <= $paymentPlan->duration_months; $month++) {
            $dueDate = $currentDate->copy()->addMonths($month);
            $schedule[] = [
                'due_date' => $dueDate,
                'amount' => $paymentPlan->monthly_payment,
                'type' => 'monthly_payment',
                'status' => 'pending'
            ];
        }

        $paymentPlan->update(['payment_schedule' => $schedule]);
    }

    /**
     * Generate terms and conditions for a payment plan.
     */
    protected function generateTermsAndConditions(array $planDetails): string
    {
        $terms = [
            "Payment Plan Agreement",
            "Total Amount: $" . number_format($planDetails['total_amount'], 2),
            "Monthly Payment: $" . number_format($planDetails['monthly_payment'], 2),
            "Duration: " . $planDetails['duration_months'] . " months"
        ];

        if ($planDetails['down_payment'] > 0) {
            $terms[] = "Down Payment: $" . number_format($planDetails['down_payment'], 2);
        }

        if ($planDetails['interest_rate'] > 0) {
            $terms[] = "Interest Rate: " . $planDetails['interest_rate'] . "% annual";
        }

        if ($planDetails['setup_fee'] > 0) {
            $terms[] = "Setup Fee: $" . number_format($planDetails['setup_fee'], 2);
        }

        $terms = array_merge($terms, [
            "",
            "Terms:",
            "- Payments are due on the same day each month",
            "- Late payments may incur additional fees",
            "- Failure to maintain payment schedule may result in plan termination",
            "- Services may be suspended for non-payment",
            "- Early payment is accepted without penalty"
        ]);

        return implode("\n", $terms);
    }

    /**
     * Process due payments for all active payment plans.
     */
    public function processDuePayments(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $duePaymentPlans = PaymentPlan::where('status', PaymentPlan::STATUS_ACTIVE)
            ->where('next_payment_date', '<=', Carbon::now())
            ->get();

        foreach ($duePaymentPlans as $plan) {
            try {
                $processed = $this->processPaymentPlanPayment($plan);
                if ($processed) {
                    $results['processed']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'plan_id' => $plan->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to process payment plan payment', [
                    'payment_plan_id' => $plan->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Process a single payment plan payment.
     */
    protected function processPaymentPlanPayment(PaymentPlan $plan): bool
    {
        // This would integrate with the payment processing system
        // For now, we'll mark it as a manual process requiring attention
        
        $plan->update([
            'last_payment_attempt' => Carbon::now(),
            'payment_attempts' => $plan->payment_attempts + 1
        ]);

        // Check for defaults
        $this->checkForDefault($plan);

        return false; // Return true when actual payment processing is implemented
    }

    /**
     * Check if a payment plan is in default.
     */
    protected function checkForDefault(PaymentPlan $plan): void
    {
        $daysPastDue = Carbon::now()->diffInDays($plan->next_payment_date);
        
        if ($daysPastDue > 15 && $plan->status !== PaymentPlan::STATUS_DEFAULTED) {
            $plan->update([
                'status' => PaymentPlan::STATUS_DEFAULTED,
                'default_date' => Carbon::now()
            ]);

            // Create collection note
            CollectionNote::create([
                'client_id' => $plan->client_id,
                'payment_plan_id' => $plan->id,
                'note_type' => CollectionNote::TYPE_PAYMENT_PLAN,
                'content' => "Payment plan defaulted - {$daysPastDue} days past due",
                'outcome' => CollectionNote::OUTCOME_PAYMENT_PLAN_DEFAULTED,
                'is_important' => true,
                'requires_attention' => true,
                'created_by' => 1
            ]);

            Log::warning('Payment plan defaulted', [
                'payment_plan_id' => $plan->id,
                'client_id' => $plan->client_id,
                'days_past_due' => $daysPastDue
            ]);
        }
    }

    /**
     * Modify an existing payment plan.
     */
    public function modifyPaymentPlan(
        PaymentPlan $plan, 
        array $modifications, 
        string $reason = ''
    ): PaymentPlan {
        return DB::transaction(function () use ($plan, $modifications, $reason) {
            // Store original plan details for audit
            $originalDetails = [
                'monthly_payment' => $plan->monthly_payment,
                'duration_months' => $plan->duration_months,
                'interest_rate' => $plan->interest_rate
            ];

            // Apply modifications
            $plan->update($modifications);

            // Regenerate payment schedule if payment amount or duration changed
            if (isset($modifications['monthly_payment']) || isset($modifications['duration_months'])) {
                $this->generatePaymentSchedule($plan);
            }

            // Record modification
            $plan->recordModification($originalDetails, $modifications, $reason);

            // Create collection note
            CollectionNote::create([
                'client_id' => $plan->client_id,
                'payment_plan_id' => $plan->id,
                'note_type' => CollectionNote::TYPE_PAYMENT_PLAN,
                'content' => "Payment plan modified: {$reason}",
                'outcome' => CollectionNote::OUTCOME_PAYMENT_PLAN_MODIFIED,
                'created_by' => auth()->id() ?? 1
            ]);

            Log::info('Payment plan modified', [
                'payment_plan_id' => $plan->id,
                'modifications' => $modifications,
                'reason' => $reason
            ]);

            return $plan->fresh();
        });
    }

    /**
     * Calculate payment plan performance metrics.
     */
    public function calculatePerformanceMetrics(PaymentPlan $plan): array
    {
        $payments = $plan->payments()->get();
        $schedule = $plan->payment_schedule ?? [];

        $metrics = [
            'completion_percentage' => 0,
            'payments_made' => $payments->count(),
            'payments_scheduled' => count($schedule),
            'amount_paid' => $payments->sum('amount'),
            'amount_remaining' => 0,
            'on_time_payments' => 0,
            'late_payments' => 0,
            'missed_payments' => 0,
            'average_days_late' => 0,
            'success_probability' => 0
        ];

        $totalScheduled = collect($schedule)->sum('amount');
        $metrics['amount_remaining'] = max(0, $totalScheduled - $metrics['amount_paid']);
        $metrics['completion_percentage'] = $totalScheduled > 0 ? 
            ($metrics['amount_paid'] / $totalScheduled) * 100 : 0;

        // Analyze payment timing
        $lateDays = [];
        foreach ($schedule as $scheduledPayment) {
            $payment = $payments->where('scheduled_payment_id', $scheduledPayment['id'] ?? null)->first();
            
            if ($payment) {
                $daysDiff = Carbon::parse($payment->created_at)
                    ->diffInDays(Carbon::parse($scheduledPayment['due_date']), false);
                
                if ($daysDiff <= 0) {
                    $metrics['on_time_payments']++;
                } else {
                    $metrics['late_payments']++;
                    $lateDays[] = $daysDiff;
                }
            } else {
                if (Carbon::parse($scheduledPayment['due_date']) < Carbon::now()) {
                    $metrics['missed_payments']++;
                }
            }
        }

        if (!empty($lateDays)) {
            $metrics['average_days_late'] = array_sum($lateDays) / count($lateDays);
        }

        // Calculate success probability
        $metrics['success_probability'] = $this->calculatePlanSuccessProbability($metrics, $plan);

        return $metrics;
    }

    /**
     * Calculate success probability for a payment plan based on performance.
     */
    protected function calculatePlanSuccessProbability(array $metrics, PaymentPlan $plan): float
    {
        $baseRate = 70;

        // Adjust based on completion percentage
        if ($metrics['completion_percentage'] > 80) {
            $baseRate += 20;
        } elseif ($metrics['completion_percentage'] > 50) {
            $baseRate += 10;
        } elseif ($metrics['completion_percentage'] < 25) {
            $baseRate -= 20;
        }

        // Adjust based on payment history
        $totalPayments = $metrics['on_time_payments'] + $metrics['late_payments'] + $metrics['missed_payments'];
        if ($totalPayments > 0) {
            $onTimeRate = $metrics['on_time_payments'] / $totalPayments;
            if ($onTimeRate > 0.8) {
                $baseRate += 15;
            } elseif ($onTimeRate < 0.5) {
                $baseRate -= 25;
            }
        }

        // Adjust based on missed payments
        if ($metrics['missed_payments'] > 2) {
            $baseRate -= 30;
        } elseif ($metrics['missed_payments'] > 0) {
            $baseRate -= 15;
        }

        return max(5, min(95, $baseRate));
    }

    /**
     * Get payment plan recommendations for a client.
     */
    public function getPaymentPlanRecommendations(Client $client): array
    {
        $cacheKey = $this->cachePrefix . "recommendations:{$client->id}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($client) {
            $totalBalance = $client->getPastDueAmount();
            
            if ($totalBalance <= 0) {
                return ['message' => 'No outstanding balance'];
            }

            $riskAssessment = $this->collectionService->assessClientRisk($client);
            $recommendations = [];

            // Generate optimal payment plan
            $optimalPlan = $this->createOptimalPaymentPlan($client, $totalBalance);
            $recommendations[] = [
                'type' => 'optimal_plan',
                'plan' => $optimalPlan,
                'recommendation' => 'Recommended based on client risk profile and financial capacity'
            ];

            // Quick payment discount
            if ($riskAssessment['risk_level'] === 'low') {
                $recommendations[] = [
                    'type' => 'quick_payment_discount',
                    'discount_percent' => 10,
                    'full_payment_required' => true,
                    'deadline' => Carbon::now()->addDays(10)->toDateString(),
                    'recommendation' => 'Offer discount for full payment within 10 days'
                ];
            }

            // Settlement option
            if ($riskAssessment['risk_level'] === 'high' || $riskAssessment['risk_level'] === 'critical') {
                $settlementAmount = $totalBalance * 0.6; // 60% settlement
                $recommendations[] = [
                    'type' => 'settlement_offer',
                    'settlement_amount' => $settlementAmount,
                    'original_amount' => $totalBalance,
                    'savings' => $totalBalance - $settlementAmount,
                    'recommendation' => 'Consider settlement due to high collection risk'
                ];
            }

            return $recommendations;
        });
    }

    /**
     * Clear payment plan cache for a client.
     */
    public function clearClientCache(Client $client): void
    {
        $cacheKey = $this->cachePrefix . "recommendations:{$client->id}";
        Cache::forget($cacheKey);
        
        // Clear collection service cache as well
        $this->collectionService->clearClientRiskCache($client);
    }
}