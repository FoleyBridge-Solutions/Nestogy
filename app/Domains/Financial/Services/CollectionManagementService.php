<?php

namespace App\Domains\Financial\Services;

use App\Models\Client;
use App\Models\CollectionNote;
use App\Models\DunningAction;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Collection Management Service
 *
 * Handles sophisticated client risk assessment, payment behavior analysis,
 * and intelligent collection strategy optimization with ML-driven insights.
 */
class CollectionManagementService
{
    protected string $cachePrefix = 'collection_mgmt:';

    protected int $cacheTtl = 3600; // 1 hour

    /**
     * Assess comprehensive risk profile for a client.
     */
    public function assessClientRisk(Client $client): array
    {
        $cacheKey = $this->cachePrefix."risk_assessment:{$client->id}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($client) {
            $assessment = [
                'client_id' => $client->id,
                'risk_level' => 'medium',
                'risk_score' => 0,
                'confidence_level' => 0,
                'assessment_date' => Carbon::now()->toISOString(),
                'factors' => [],
                'recommendations' => [],
                'predicted_outcomes' => [],
            ];

            // Calculate risk factors
            $factors = $this->calculateRiskFactors($client);
            $assessment['factors'] = $factors;

            // Determine overall risk score
            $riskScore = $this->calculateOverallRiskScore($factors);
            $assessment['risk_score'] = $riskScore;

            // Determine risk level
            $assessment['risk_level'] = $this->determineRiskLevel($riskScore);

            // Calculate confidence level
            $assessment['confidence_level'] = $this->calculateConfidenceLevel($client, $factors);

            // Generate recommendations
            $assessment['recommendations'] = $this->generateRiskRecommendations($client, $assessment);

            // Predict outcomes using behavioral analysis
            $assessment['predicted_outcomes'] = $this->predictCollectionOutcomes($client, $assessment);

            Log::info('Client risk assessment completed', [
                'client_id' => $client->id,
                'risk_level' => $assessment['risk_level'],
                'risk_score' => $assessment['risk_score'],
            ]);

            return $assessment;
        });
    }

    /**
     * Calculate comprehensive risk factors for a client.
     */
    protected function calculateRiskFactors(Client $client): array
    {
        return [
            'payment_history' => $this->analyzePaymentHistory($client),
            'account_aging' => $this->analyzeAccountAging($client),
            'behavioral_patterns' => $this->analyzeBehavioralPatterns($client),
            'financial_stability' => $this->analyzeFinancialStability($client),
            'communication_history' => $this->analyzeCommunicationHistory($client),
        ];
    }

    /**
     * Analyze client payment history and behavior patterns.
     */
    protected function analyzePaymentHistory(Client $client): array
    {
        $payments = $client->payments()
            ->where('created_at', '>=', Carbon::now()->subYear())
            ->orderBy('created_at')
            ->get();

        $invoices = $client->invoices()
            ->where('created_at', '>=', Carbon::now()->subYear())
            ->get();

        $analysis = [
            'total_payments' => $payments->count(),
            'total_invoices' => $invoices->count(),
            'payment_ratio' => 0,
            'average_days_to_pay' => 0,
            'payment_consistency' => 0,
            'missed_payments' => 0,
            'partial_payments' => 0,
            'late_payments' => 0,
            'payment_trend' => 'stable',
            'risk_score' => 0,
        ];

        if ($analysis['total_invoices'] > 0) {
            $analysis['payment_ratio'] = ($analysis['total_payments'] / $analysis['total_invoices']) * 100;
        }

        // Calculate payment timing patterns
        $paymentDelays = [];
        foreach ($invoices as $invoice) {
            $payment = $payments->where('invoice_id', $invoice->id)->first();
            if ($payment) {
                $delay = Carbon::parse($payment->created_at)->diffInDays($invoice->due_date, false);
                $paymentDelays[] = $delay;

                if ($delay > 0) {
                    $analysis['late_payments']++;
                }
                if ($payment->amount < $invoice->amount) {
                    $analysis['partial_payments']++;
                }
            } else {
                $analysis['missed_payments']++;
                $paymentDelays[] = Carbon::now()->diffInDays($invoice->due_date);
            }
        }

        if (! empty($paymentDelays)) {
            $analysis['average_days_to_pay'] = array_sum($paymentDelays) / count($paymentDelays);
        }

        // Calculate payment consistency
        if (count($paymentDelays) > 1) {
            $mean = $analysis['average_days_to_pay'];
            $variance = array_sum(array_map(function ($x) use ($mean) {
                return pow($x - $mean, 2);
            }, $paymentDelays)) / count($paymentDelays);
            $analysis['payment_consistency'] = max(0, 100 - sqrt($variance));
        }

        // Calculate risk score
        $riskScore = 0;
        if ($analysis['payment_ratio'] < 80) {
            $riskScore += 25;
        }
        if ($analysis['average_days_to_pay'] > 30) {
            $riskScore += 20;
        }
        if ($analysis['payment_consistency'] < 50) {
            $riskScore += 15;
        }
        if ($analysis['missed_payments'] > 2) {
            $riskScore += 20;
        }
        if ($analysis['partial_payments'] > 1) {
            $riskScore += 10;
        }

        $analysis['risk_score'] = min(100, $riskScore);

        return $analysis;
    }

    /**
     * Analyze account aging and overdue amounts.
     */
    protected function analyzeAccountAging(Client $client): array
    {
        $overdueInvoices = $client->invoices()->overdue()->get();
        $totalBalance = $client->getBalance();
        $pastDueAmount = $client->getPastDueAmount();

        $aging = [
            'total_balance' => $totalBalance,
            'past_due_amount' => $pastDueAmount,
            'past_due_ratio' => $totalBalance > 0 ? ($pastDueAmount / $totalBalance) * 100 : 0,
            'oldest_overdue_days' => 0,
            'average_overdue_days' => 0,
            'overdue_invoices_count' => $overdueInvoices->count(),
            'aging_buckets' => [
                '1_30' => 0,
                '31_60' => 0,
                '61_90' => 0,
                '91_120' => 0,
                'over_120' => 0,
            ],
            'risk_score' => 0,
        ];

        if ($overdueInvoices->isNotEmpty()) {
            $overdueDays = [];
            foreach ($overdueInvoices as $invoice) {
                $days = Carbon::now()->diffInDays($invoice->due_date);
                $overdueDays[] = $days;

                // Categorize into aging buckets
                if ($days <= 30) {
                    $aging['aging_buckets']['1_30'] += $invoice->getBalance();
                } elseif ($days <= 60) {
                    $aging['aging_buckets']['31_60'] += $invoice->getBalance();
                } elseif ($days <= 90) {
                    $aging['aging_buckets']['61_90'] += $invoice->getBalance();
                } elseif ($days <= 120) {
                    $aging['aging_buckets']['91_120'] += $invoice->getBalance();
                } else {
                    $aging['aging_buckets']['over_120'] += $invoice->getBalance();
                }
            }

            $aging['oldest_overdue_days'] = max($overdueDays);
            $aging['average_overdue_days'] = array_sum($overdueDays) / count($overdueDays);
        }

        // Calculate aging risk score
        $riskScore = 0;
        if ($aging['past_due_ratio'] > 50) {
            $riskScore += 30;
        }
        if ($aging['oldest_overdue_days'] > 90) {
            $riskScore += 25;
        }
        if ($aging['aging_buckets']['over_120'] > 0) {
            $riskScore += 25;
        }
        if ($aging['overdue_invoices_count'] > 3) {
            $riskScore += 20;
        }

        $aging['risk_score'] = min(100, $riskScore);

        return $aging;
    }

    /**
     * Analyze behavioral patterns and communication responsiveness.
     */
    protected function analyzeBehavioralPatterns(Client $client): array
    {
        $collectionNotes = $client->collectionNotes()
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->get();

        $patterns = [
            'communication_responsiveness' => 0,
            'promise_to_pay_reliability' => 0,
            'cooperation_level' => 'neutral',
            'successful_contacts' => 0,
            'promises_made' => 0,
            'promises_kept' => 0,
            'disputes_raised' => 0,
            'hostile_interactions' => 0,
            'risk_score' => 0,
        ];

        // Analyze collection notes
        foreach ($collectionNotes as $note) {
            if ($note->outcome === CollectionNote::OUTCOME_SPOKE_WITH_CLIENT) {
                $patterns['successful_contacts']++;
            }

            if ($note->contains_promise_to_pay) {
                $patterns['promises_made']++;
                if ($note->promise_kept === true) {
                    $patterns['promises_kept']++;
                }
            }

            if ($note->contains_dispute) {
                $patterns['disputes_raised']++;
            }

            if ($note->client_mood === CollectionNote::MOOD_HOSTILE) {
                $patterns['hostile_interactions']++;
            }
        }

        // Calculate metrics
        $totalAttempts = DunningAction::where('client_id', $client->id)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->count();

        if ($totalAttempts > 0) {
            $patterns['communication_responsiveness'] =
                ($patterns['successful_contacts'] / $totalAttempts) * 100;
        }

        if ($patterns['promises_made'] > 0) {
            $patterns['promise_to_pay_reliability'] =
                ($patterns['promises_kept'] / $patterns['promises_made']) * 100;
        }

        // Determine cooperation level
        if ($patterns['hostile_interactions'] > 2) {
            $patterns['cooperation_level'] = 'hostile';
        } elseif ($patterns['communication_responsiveness'] > 70) {
            $patterns['cooperation_level'] = 'cooperative';
        }

        // Calculate risk score
        $riskScore = 0;
        if ($patterns['communication_responsiveness'] < 30) {
            $riskScore += 25;
        }
        if ($patterns['promise_to_pay_reliability'] < 50) {
            $riskScore += 20;
        }
        if ($patterns['disputes_raised'] > 2) {
            $riskScore += 15;
        }
        if ($patterns['hostile_interactions'] > 0) {
            $riskScore += 20;
        }

        $patterns['risk_score'] = min(100, $riskScore);

        return $patterns;
    }

    /**
     * Analyze financial stability indicators.
     */
    protected function analyzeFinancialStability(Client $client): array
    {
        $monthlyRevenue = $client->getMonthlyRecurring();
        $hasActiveContract = $client->hasActiveContract();

        $stability = [
            'monthly_recurring_revenue' => $monthlyRevenue,
            'contract_status' => $hasActiveContract ? 'active' : 'expired',
            'revenue_trend' => 'stable',
            'account_growth' => 0,
            'risk_score' => 0,
        ];

        // Calculate financial stability risk score
        $riskScore = 0;
        if (! $hasActiveContract) {
            $riskScore += 15;
        }
        if ($monthlyRevenue < 100) {
            $riskScore += 15;
        }

        $stability['risk_score'] = min(100, $riskScore);

        return $stability;
    }

    /**
     * Analyze communication history and responsiveness.
     */
    protected function analyzeCommunicationHistory(Client $client): array
    {
        $actions = DunningAction::where('client_id', $client->id)
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->get();

        $communication = [
            'total_communications' => $actions->count(),
            'email_communications' => $actions->where('action_type', DunningAction::ACTION_EMAIL)->count(),
            'sms_communications' => $actions->where('action_type', DunningAction::ACTION_SMS)->count(),
            'phone_communications' => $actions->where('action_type', DunningAction::ACTION_PHONE_CALL)->count(),
            'response_rate' => 0,
            'preferred_channel' => 'email',
            'risk_score' => 0,
        ];

        $responseCount = $actions->where('responded_at', '!=', null)->count();
        if ($communication['total_communications'] > 0) {
            $communication['response_rate'] = ($responseCount / $communication['total_communications']) * 100;
        }

        // Calculate communication risk score
        $riskScore = 0;
        if ($communication['response_rate'] < 25) {
            $riskScore += 30;
        }
        if ($communication['total_communications'] > 20) {
            $riskScore += 15;
        }

        $communication['risk_score'] = min(100, $riskScore);

        return $communication;
    }

    /**
     * Calculate overall risk score from individual factors.
     */
    protected function calculateOverallRiskScore(array $factors): int
    {
        $weights = [
            'payment_history' => 0.35,
            'account_aging' => 0.25,
            'behavioral_patterns' => 0.20,
            'financial_stability' => 0.15,
            'communication_history' => 0.05,
        ];

        $weightedScore = 0;
        foreach ($weights as $factor => $weight) {
            if (isset($factors[$factor]['risk_score'])) {
                $weightedScore += $factors[$factor]['risk_score'] * $weight;
            }
        }

        return min(100, max(0, round($weightedScore)));
    }

    /**
     * Determine risk level from numerical score.
     */
    protected function determineRiskLevel(int $score): string
    {
        if ($score >= 80) {
            return 'critical';
        }
        if ($score >= 60) {
            return 'high';
        }
        if ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Calculate confidence level in the assessment.
     */
    protected function calculateConfidenceLevel(Client $client, array $factors): int
    {
        $dataPoints = 0;
        $maxDataPoints = 100;

        // Count available data points
        $dataPoints += min(20, $client->invoices()->count());
        $dataPoints += min(20, $client->payments()->count());
        $dataPoints += min(15, $client->collectionNotes()->count());

        // Account age contributes to confidence
        $accountAge = $client->created_at->diffInDays(Carbon::now());
        $dataPoints += min(25, $accountAge / 10);

        // Communication history
        $communications = $factors['communication_history']['total_communications'] ?? 0;
        $dataPoints += min(20, $communications);

        return min(100, round(($dataPoints / $maxDataPoints) * 100));
    }

    /**
     * Generate risk-based recommendations.
     */
    protected function generateRiskRecommendations(Client $client, array $assessment): array
    {
        $recommendations = [];
        $riskLevel = $assessment['risk_level'];

        switch ($riskLevel) {
            case 'critical':
                $recommendations[] = [
                    'type' => 'immediate_action',
                    'priority' => 'urgent',
                    'action' => 'service_suspension',
                    'reason' => 'Critical risk level requires immediate service protection',
                ];
                break;

            case 'high':
                $recommendations[] = [
                    'type' => 'collection_strategy',
                    'priority' => 'high',
                    'action' => 'aggressive_dunning',
                    'reason' => 'High risk requires intensive collection efforts',
                ];
                break;

            case 'low':
                $recommendations[] = [
                    'type' => 'collection_strategy',
                    'priority' => 'low',
                    'action' => 'gentle_reminders',
                    'reason' => 'Low risk allows for courteous collection approach',
                ];
                break;

            default:
                $recommendations[] = [
                    'type' => 'collection_strategy',
                    'priority' => 'medium',
                    'action' => 'standard_dunning',
                    'reason' => 'Medium risk requires standard collection process',
                ];
                break;
        }

        return $recommendations;
    }

    /**
     * Predict collection outcomes using behavioral analysis.
     */
    protected function predictCollectionOutcomes(Client $client, array $assessment): array
    {
        $riskScore = $assessment['risk_score'];

        $predictions = [
            'payment_probability' => 0,
            'full_recovery_probability' => 0,
            'settlement_probability' => 0,
            'legal_action_probability' => 0,
            'estimated_recovery_rate' => 0,
            'predicted_collection_time' => 0,
        ];

        // Base probabilities on risk score
        if ($riskScore < 20) {
            $predictions = [
                'payment_probability' => 95,
                'full_recovery_probability' => 90,
                'settlement_probability' => 5,
                'legal_action_probability' => 0,
                'estimated_recovery_rate' => 98,
                'predicted_collection_time' => 15,
            ];
        } elseif ($riskScore < 40) {
            $predictions = [
                'payment_probability' => 80,
                'full_recovery_probability' => 70,
                'settlement_probability' => 15,
                'legal_action_probability' => 5,
                'estimated_recovery_rate' => 85,
                'predicted_collection_time' => 30,
            ];
        } elseif ($riskScore < 60) {
            $predictions = [
                'payment_probability' => 60,
                'full_recovery_probability' => 45,
                'settlement_probability' => 30,
                'legal_action_probability' => 15,
                'estimated_recovery_rate' => 70,
                'predicted_collection_time' => 60,
            ];
        } elseif ($riskScore < 80) {
            $predictions = [
                'payment_probability' => 35,
                'full_recovery_probability' => 20,
                'settlement_probability' => 40,
                'legal_action_probability' => 30,
                'estimated_recovery_rate' => 50,
                'predicted_collection_time' => 120,
            ];
        } else {
            $predictions = [
                'payment_probability' => 15,
                'full_recovery_probability' => 5,
                'settlement_probability' => 25,
                'legal_action_probability' => 60,
                'estimated_recovery_rate' => 25,
                'predicted_collection_time' => 180,
            ];
        }

        return $predictions;
    }

    /**
     * Get optimal collection strategy for a client.
     */
    public function getOptimalCollectionStrategy(Client $client): array
    {
        $assessment = $this->assessClientRisk($client);

        $strategy = [
            'strategy_type' => 'standard',
            'contact_frequency' => 'weekly',
            'preferred_channels' => ['email'],
            'escalation_timeline' => 30,
            'payment_plan_threshold' => 500,
            'settlement_threshold' => 1000,
            'expected_outcome' => $assessment['predicted_outcomes'],
        ];

        // Customize strategy based on risk level
        switch ($assessment['risk_level']) {
            case 'low':
                $strategy['strategy_type'] = 'gentle';
                $strategy['contact_frequency'] = 'monthly';
                $strategy['escalation_timeline'] = 60;
                break;

            case 'medium':
                $strategy['strategy_type'] = 'standard';
                $strategy['contact_frequency'] = 'weekly';
                $strategy['escalation_timeline'] = 30;
                break;

            case 'high':
                $strategy['strategy_type'] = 'aggressive';
                $strategy['contact_frequency'] = 'daily';
                $strategy['escalation_timeline'] = 14;
                break;

            case 'critical':
                $strategy['strategy_type'] = 'immediate_action';
                $strategy['contact_frequency'] = 'daily';
                $strategy['escalation_timeline'] = 7;
                break;
        }

        return $strategy;
    }

    /**
     * Clear risk assessment cache for a client.
     */
    public function clearClientRiskCache(Client $client): void
    {
        $cacheKey = $this->cachePrefix."risk_assessment:{$client->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Batch assess risk for multiple clients.
     */
    public function batchAssessRisk(Collection $clients): array
    {
        $assessments = [];

        foreach ($clients as $client) {
            try {
                $assessments[$client->id] = $this->assessClientRisk($client);
            } catch (\Exception $e) {
                Log::error('Failed to assess risk for client', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);

                $assessments[$client->id] = [
                    'error' => $e->getMessage(),
                    'risk_level' => 'medium',
                    'risk_score' => 50,
                ];
            }
        }

        return $assessments;
    }
}
