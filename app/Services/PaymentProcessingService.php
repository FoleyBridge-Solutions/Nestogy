<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\DunningAction;
use App\Models\CollectionNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Payment Processing Service
 * 
 * Handles payment processing integration for dunning management including
 * multiple payment methods, retry logic, payment plan processing, and
 * automated collection payment handling with PCI compliance.
 */
class PaymentProcessingService
{
    protected CommunicationService $communicationService;
    protected VoipCollectionService $voipService;
    
    protected array $paymentMethods = [
        'credit_card' => 'Credit Card',
        'ach' => 'ACH Bank Transfer',
        'wire_transfer' => 'Wire Transfer',
        'check' => 'Check',
        'cash' => 'Cash',
        'crypto' => 'Cryptocurrency'
    ];

    protected array $processingFees = [
        'credit_card' => 2.9, // 2.9%
        'ach' => 0.8,         // 0.8%
        'wire_transfer' => 25.00, // $25 flat fee
        'check' => 0,         // No fee
        'cash' => 0,          // No fee
        'crypto' => 1.5       // 1.5%
    ];

    protected int $maxRetryAttempts = 3;
    protected array $retryDelays = [1, 3, 7]; // Days between retries

    public function __construct(
        CommunicationService $communicationService,
        VoipCollectionService $voipService
    ) {
        $this->communicationService = $communicationService;
        $this->voipService = $voipService;
    }

    /**
     * Process payment with dunning integration.
     */
    public function processPayment(
        Client $client,
        float $amount,
        string $paymentMethod,
        array $paymentData = [],
        array $options = []
    ): array {
        $result = [
            'success' => false,
            'payment_id' => null,
            'transaction_id' => null,
            'error' => null,
            'fees' => 0,
            'net_amount' => $amount,
            'retry_scheduled' => false
        ];

        try {
            // Validate payment method
            if (!isset($this->paymentMethods[$paymentMethod])) {
                throw new \InvalidArgumentException("Invalid payment method: {$paymentMethod}");
            }

            // Calculate processing fees
            $fees = $this->calculateProcessingFees($amount, $paymentMethod);
            $result['fees'] = $fees;

            // Create payment record
            $payment = $this->createPaymentRecord($client, $amount, $paymentMethod, $paymentData, $options);
            $result['payment_id'] = $payment->id;

            // Process payment through gateway
            $gatewayResult = $this->processPaymentThroughGateway($payment, $paymentData);
            
            if ($gatewayResult['success']) {
                // Payment successful
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'processed_at' => Carbon::now(),
                    'transaction_id' => $gatewayResult['transaction_id'],
                    'gateway_response' => $gatewayResult['response'],
                    'processing_fee' => $fees,
                    'net_amount' => $amount - $fees
                ]);

                $result['success'] = true;
                $result['transaction_id'] = $gatewayResult['transaction_id'];
                $result['net_amount'] = $amount - $fees;

                // Handle successful payment
                $this->handleSuccessfulPayment($payment);

            } else {
                // Payment failed
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                    'failure_reason' => $gatewayResult['error'],
                    'gateway_response' => $gatewayResult['response'],
                    'retry_count' => 0
                ]);

                $result['error'] = $gatewayResult['error'];

                // Schedule retry if appropriate
                if ($this->shouldRetryPayment($payment, $gatewayResult)) {
                    $this->schedulePaymentRetry($payment);
                    $result['retry_scheduled'] = true;
                } else {
                    $this->handleFailedPayment($payment);
                }
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            
            Log::error('Payment processing failed', [
                'client_id' => $client->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);

            if (isset($payment)) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                    'failure_reason' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Calculate processing fees based on payment method and amount.
     */
    protected function calculateProcessingFees(float $amount, string $paymentMethod): float
    {
        $feeRate = $this->processingFees[$paymentMethod] ?? 0;
        
        // Percentage-based fees
        if ($paymentMethod === 'credit_card' || $paymentMethod === 'ach' || $paymentMethod === 'crypto') {
            return round($amount * ($feeRate / 100), 2);
        }
        
        // Flat fees
        return $feeRate;
    }

    /**
     * Create payment record in database.
     */
    protected function createPaymentRecord(
        Client $client,
        float $amount,
        string $paymentMethod,
        array $paymentData,
        array $options
    ): Payment {
        return Payment::create([
            'client_id' => $client->id,
            'invoice_id' => $options['invoice_id'] ?? null,
            'payment_plan_id' => $options['payment_plan_id'] ?? null,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => Payment::STATUS_PENDING,
            'payment_data' => $this->sanitizePaymentData($paymentData),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => $options['source'] ?? 'dunning_system',
            'notes' => $options['notes'] ?? '',
            'created_by' => auth()->id() ?? 1
        ]);
    }

    /**
     * Sanitize payment data for storage (remove sensitive info).
     */
    protected function sanitizePaymentData(array $paymentData): array
    {
        $sanitized = $paymentData;
        
        // Remove sensitive credit card information
        unset($sanitized['card_number']);
        unset($sanitized['cvv']);
        unset($sanitized['card_cvv']);
        
        // Mask account numbers
        if (isset($sanitized['account_number'])) {
            $sanitized['account_number'] = '****' . substr($sanitized['account_number'], -4);
        }

        return $sanitized;
    }

    /**
     * Process payment through payment gateway.
     */
    protected function processPaymentThroughGateway(Payment $payment, array $paymentData): array
    {
        try {
            switch ($payment->payment_method) {
                case 'credit_card':
                    return $this->processCreditCardPayment($payment, $paymentData);
                case 'ach':
                    return $this->processAchPayment($payment, $paymentData);
                case 'wire_transfer':
                    return $this->processWireTransfer($payment, $paymentData);
                case 'crypto':
                    return $this->processCryptoPayment($payment, $paymentData);
                default:
                    return $this->processManualPayment($payment, $paymentData);
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Process credit card payment.
     */
    protected function processCreditCardPayment(Payment $payment, array $paymentData): array
    {
        // Mock credit card processing - integrate with actual gateway
        $response = Http::post('https://api.stripe.com/v1/charges', [
            'amount' => $payment->amount * 100, // Stripe uses cents
            'currency' => 'usd',
            'source' => $paymentData['stripe_token'] ?? 'tok_test',
            'description' => "Payment for account #{$payment->client->account_number}",
            'metadata' => [
                'client_id' => $payment->client_id,
                'payment_id' => $payment->id
            ]
        ], [
            'Authorization' => 'Bearer ' . config('payment.stripe.secret_key')
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'transaction_id' => $data['id'],
                'response' => $data
            ];
        }

        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Credit card processing failed',
            'response' => $response->json()
        ];
    }

    /**
     * Process ACH payment.
     */
    protected function processAchPayment(Payment $payment, array $paymentData): array
    {
        // Mock ACH processing - integrate with actual gateway
        $response = Http::post('https://api.dwolla.com/transfers', [
            'amount' => [
                'currency' => 'USD',
                'value' => number_format($payment->amount, 2, '.', '')
            ],
            'source' => $paymentData['funding_source_url'],
            'destination' => config('payment.dwolla.destination_url'),
            'metadata' => [
                'client_id' => $payment->client_id,
                'payment_id' => $payment->id
            ]
        ], [
            'Authorization' => 'Bearer ' . config('payment.dwolla.access_token'),
            'Accept' => 'application/vnd.dwolla.v1.hal+json'
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'transaction_id' => basename($response->header('Location')),
                'response' => $response->json()
            ];
        }

        return [
            'success' => false,
            'error' => 'ACH processing failed',
            'response' => $response->json()
        ];
    }

    /**
     * Process wire transfer (manual verification required).
     */
    protected function processWireTransfer(Payment $payment, array $paymentData): array
    {
        // Wire transfers require manual verification
        return [
            'success' => false,
            'error' => 'Wire transfer requires manual verification',
            'response' => ['status' => 'pending_verification']
        ];
    }

    /**
     * Process cryptocurrency payment.
     */
    protected function processCryptoPayment(Payment $payment, array $paymentData): array
    {
        // Mock crypto processing
        $response = Http::post('https://api.coinbase.com/v2/charges', [
            'amount' => $payment->amount,
            'currency' => 'USD',
            'name' => 'Account Payment',
            'description' => "Payment for account #{$payment->client->account_number}"
        ], [
            'X-CC-Api-Key' => config('payment.coinbase.api_key')
        ]);

        if ($response->successful()) {
            $data = $response->json()['data'];
            return [
                'success' => $data['confirmed_at'] !== null,
                'transaction_id' => $data['code'],
                'response' => $data
            ];
        }

        return [
            'success' => false,
            'error' => 'Cryptocurrency processing failed',
            'response' => $response->json()
        ];
    }

    /**
     * Process manual payment (check, cash, etc.).
     */
    protected function processManualPayment(Payment $payment, array $paymentData): array
    {
        // Manual payments are marked as successful but require verification
        return [
            'success' => true,
            'transaction_id' => 'MANUAL_' . strtoupper($payment->payment_method) . '_' . time(),
            'response' => ['status' => 'manual_processing']
        ];
    }

    /**
     * Handle successful payment.
     */
    protected function handleSuccessfulPayment(Payment $payment): void
    {
        $client = $payment->client;

        // Apply payment to invoices
        $this->applyPaymentToInvoices($payment);

        // Update payment plan if applicable
        if ($payment->payment_plan_id) {
            $this->updatePaymentPlan($payment);
        }

        // Check if account should be restored
        if ($client->getBalance() <= 0) {
            $this->restoreClientServices($client);
        }

        // Send payment confirmation
        $this->communicationService->sendDunningCommunication(
            $client,
            'payment_received',
            [
                'payment_amount' => $payment->amount,
                'payment_method' => $this->paymentMethods[$payment->payment_method],
                'transaction_id' => $payment->transaction_id
            ],
            ['channels' => ['email', 'portal_notification']]
        );

        // Create collection note
        CollectionNote::create([
            'client_id' => $client->id,
            'payment_id' => $payment->id,
            'note_type' => CollectionNote::TYPE_PAYMENT,
            'content' => "Payment received: $" . number_format($payment->amount, 2) . 
                        " via {$this->paymentMethods[$payment->payment_method]}",
            'outcome' => CollectionNote::OUTCOME_PAYMENT_RECEIVED,
            'created_by' => auth()->id() ?? 1
        ]);

        Log::info('Payment processed successfully', [
            'client_id' => $client->id,
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $payment->payment_method
        ]);
    }

    /**
     * Apply payment to outstanding invoices.
     */
    protected function applyPaymentToInvoices(Payment $payment): void
    {
        $client = $payment->client;
        $remainingAmount = $payment->amount;

        // Get oldest unpaid invoices first
        $unpaidInvoices = $client->invoices()
            ->where('status', '!=', Invoice::STATUS_PAID)
            ->where('amount', '>', 0)
            ->orderBy('due_date')
            ->get();

        foreach ($unpaidInvoices as $invoice) {
            if ($remainingAmount <= 0) break;

            $invoiceBalance = $invoice->getBalance();
            $paymentAmount = min($remainingAmount, $invoiceBalance);

            // Create invoice payment record
            DB::table('invoice_payments')->insert([
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $paymentAmount,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            // Update invoice if fully paid
            if ($paymentAmount >= $invoiceBalance) {
                $invoice->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_date' => Carbon::now()
                ]);
            }

            $remainingAmount -= $paymentAmount;
        }

        // If there's remaining amount, create credit
        if ($remainingAmount > 0) {
            DB::table('client_credits')->insert([
                'client_id' => $client->id,
                'payment_id' => $payment->id,
                'amount' => $remainingAmount,
                'reason' => 'Overpayment credit',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }

    /**
     * Update payment plan after successful payment.
     */
    protected function updatePaymentPlan(Payment $payment): void
    {
        $paymentPlan = PaymentPlan::find($payment->payment_plan_id);
        if (!$paymentPlan) return;

        // Update payment plan status and next payment date
        $schedule = $paymentPlan->payment_schedule ?? [];
        $nextPaymentDate = null;

        // Find next unpaid payment in schedule
        foreach ($schedule as $scheduledPayment) {
            if ($scheduledPayment['status'] === 'pending' && 
                Carbon::parse($scheduledPayment['due_date'])->isFuture()) {
                $nextPaymentDate = Carbon::parse($scheduledPayment['due_date']);
                break;
            }
        }

        $paymentPlan->update([
            'last_payment_date' => Carbon::now(),
            'last_payment_amount' => $payment->amount,
            'next_payment_date' => $nextPaymentDate,
            'payments_made' => $paymentPlan->payments_made + 1,
            'amount_paid' => $paymentPlan->amount_paid + $payment->amount
        ]);

        // Check if payment plan is complete
        if ($paymentPlan->amount_paid >= $paymentPlan->total_amount) {
            $paymentPlan->update([
                'status' => PaymentPlan::STATUS_COMPLETED,
                'completed_date' => Carbon::now()
            ]);
        }
    }

    /**
     * Restore client services after payment.
     */
    protected function restoreClientServices(Client $client): void
    {
        // Find active service holds
        $activeHolds = $client->accountHolds()
            ->where('status', AccountHold::STATUS_ACTIVE)
            ->where('hold_type', AccountHold::TYPE_SERVICE_SUSPENSION)
            ->get();

        foreach ($activeHolds as $hold) {
            $this->voipService->restoreVoipServices($hold, 'Payment received - balance cleared');
        }
    }

    /**
     * Determine if payment should be retried.
     */
    protected function shouldRetryPayment(Payment $payment, array $gatewayResult): bool
    {
        // Don't retry if max attempts reached
        if ($payment->retry_count >= $this->maxRetryAttempts) {
            return false;
        }

        // Don't retry certain error types
        $nonRetryableErrors = [
            'insufficient_funds',
            'card_declined',
            'invalid_card',
            'account_closed',
            'fraud_suspected'
        ];

        $errorType = $gatewayResult['error_type'] ?? '';
        return !in_array($errorType, $nonRetryableErrors);
    }

    /**
     * Schedule payment retry.
     */
    protected function schedulePaymentRetry(Payment $payment): void
    {
        $retryDelay = $this->retryDelays[$payment->retry_count] ?? 7;
        $retryDate = Carbon::now()->addDays($retryDelay);

        $payment->update([
            'status' => Payment::STATUS_RETRY_SCHEDULED,
            'retry_count' => $payment->retry_count + 1,
            'next_retry_date' => $retryDate
        ]);

        // Create scheduled dunning action for retry
        DunningAction::create([
            'client_id' => $payment->client_id,
            'payment_id' => $payment->id,
            'action_type' => DunningAction::ACTION_PAYMENT_RETRY,
            'status' => DunningAction::STATUS_SCHEDULED,
            'scheduled_date' => $retryDate,
            'retry_attempt' => $payment->retry_count,
            'created_by' => auth()->id() ?? 1
        ]);

        Log::info('Payment retry scheduled', [
            'payment_id' => $payment->id,
            'retry_count' => $payment->retry_count,
            'retry_date' => $retryDate
        ]);
    }

    /**
     * Handle failed payment (no more retries).
     */
    protected function handleFailedPayment(Payment $payment): void
    {
        $client = $payment->client;

        // Send payment failure notification
        $this->communicationService->sendDunningCommunication(
            $client,
            'payment_failed',
            [
                'payment_amount' => $payment->amount,
                'payment_method' => $this->paymentMethods[$payment->payment_method],
                'failure_reason' => $payment->failure_reason
            ],
            ['channels' => ['email', 'portal_notification']]
        );

        // Create collection note
        CollectionNote::create([
            'client_id' => $client->id,
            'payment_id' => $payment->id,
            'note_type' => CollectionNote::TYPE_PAYMENT,
            'content' => "Payment failed: $" . number_format($payment->amount, 2) . 
                        " - {$payment->failure_reason}",
            'outcome' => CollectionNote::OUTCOME_PAYMENT_FAILED,
            'requires_attention' => true,
            'created_by' => auth()->id() ?? 1
        ]);

        Log::warning('Payment failed permanently', [
            'payment_id' => $payment->id,
            'client_id' => $client->id,
            'amount' => $payment->amount,
            'reason' => $payment->failure_reason
        ]);
    }

    /**
     * Process scheduled payment retries.
     */
    public function processScheduledRetries(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'rescheduled' => 0,
            'errors' => []
        ];

        $retryPayments = Payment::where('status', Payment::STATUS_RETRY_SCHEDULED)
            ->where('next_retry_date', '<=', Carbon::now())
            ->with('client')
            ->get();

        foreach ($retryPayments as $payment) {
            try {
                $results['processed']++;

                // Get original payment data
                $paymentData = $payment->payment_data ?? [];
                
                // Retry payment
                $result = $this->processPaymentThroughGateway($payment, $paymentData);
                
                if ($result['success']) {
                    $payment->update([
                        'status' => Payment::STATUS_COMPLETED,
                        'processed_at' => Carbon::now(),
                        'transaction_id' => $result['transaction_id'],
                        'gateway_response' => $result['response']
                    ]);

                    $this->handleSuccessfulPayment($payment);
                    $results['successful']++;

                } else {
                    if ($this->shouldRetryPayment($payment, $result)) {
                        $this->schedulePaymentRetry($payment);
                        $results['rescheduled']++;
                    } else {
                        $payment->update([
                            'status' => Payment::STATUS_FAILED,
                            'failed_at' => Carbon::now(),
                            'failure_reason' => $result['error']
                        ]);
                        $this->handleFailedPayment($payment);
                        $results['failed']++;
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Payment retry failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Process payment plan automatic payments.
     */
    public function processPaymentPlanPayments(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $duePaymentPlans = PaymentPlan::where('status', PaymentPlan::STATUS_ACTIVE)
            ->where('auto_pay', true)
            ->where('next_payment_date', '<=', Carbon::now())
            ->with('client')
            ->get();

        foreach ($duePaymentPlans as $plan) {
            try {
                $results['processed']++;

                // Process payment plan payment
                $result = $this->processPayment(
                    $plan->client,
                    $plan->monthly_payment,
                    $plan->payment_method ?? 'credit_card',
                    $plan->payment_data ?? [],
                    [
                        'payment_plan_id' => $plan->id,
                        'source' => 'payment_plan_auto',
                        'notes' => 'Automatic payment plan payment'
                    ]
                );

                if ($result['success']) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'payment_plan_id' => $plan->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Generate secure payment link for client.
     */
    public function generatePaymentLink(Client $client, array $options = []): string
    {
        $tokenData = [
            'client_id' => $client->id,
            'amount' => $options['amount'] ?? $client->getBalance(),
            'payment_plan_id' => $options['payment_plan_id'] ?? null,
            'invoice_id' => $options['invoice_id'] ?? null,
            'expires' => Carbon::now()->addDays(30)->timestamp,
            'one_time_use' => $options['one_time_use'] ?? false
        ];

        $token = encrypt($tokenData);
        return url("/payment/pay/{$token}");
    }

    /**
     * Get payment processing statistics.
     */
    public function getPaymentStatistics(Client $client = null, array $dateRange = []): array
    {
        $query = Payment::query();
        
        if ($client) {
            $query->where('client_id', $client->id);
        }

        if (!empty($dateRange)) {
            $query->whereBetween('created_at', $dateRange);
        }

        $payments = $query->get();

        return [
            'total_payments' => $payments->count(),
            'successful_payments' => $payments->where('status', Payment::STATUS_COMPLETED)->count(),
            'failed_payments' => $payments->where('status', Payment::STATUS_FAILED)->count(),
            'pending_payments' => $payments->where('status', Payment::STATUS_PENDING)->count(),
            'total_amount' => $payments->where('status', Payment::STATUS_COMPLETED)->sum('amount'),
            'total_fees' => $payments->where('status', Payment::STATUS_COMPLETED)->sum('processing_fee'),
            'success_rate' => $payments->count() > 0 ? 
                ($payments->where('status', Payment::STATUS_COMPLETED)->count() / $payments->count()) * 100 : 0,
            'by_method' => $payments->groupBy('payment_method')->map->count(),
            'average_amount' => $payments->where('status', Payment::STATUS_COMPLETED)->avg('amount') ?? 0
        ];
    }
}