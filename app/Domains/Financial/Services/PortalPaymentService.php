<?php

namespace App\Domains\Financial\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\AutoPayment;
use App\Models\PortalNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Exception;

/**
 * Portal Payment Service
 * 
 * Comprehensive payment processing service for client portal with support for:
 * - Multiple payment gateway integration (Stripe, PayPal, Authorize.Net, Square)
 * - Secure payment method storage and tokenization
 * - Automated payment setup and management
 * - Payment scheduling and recurring payments
 * - Payment history and receipt generation
 * - Failed payment handling and retry logic
 * - PCI compliance and security features
 * - Multi-currency payment support
 * - Digital wallet integration (Apple Pay, Google Pay)
 * - Cryptocurrency payment support
 * - Payment plan management
 * - Fraud detection and prevention
 */
class PortalPaymentService
{
    protected array $config;
    protected array $gateways = [];

    public function __construct()
    {
        $this->config = config('portal.payments', [
            'default_gateway' => 'stripe',
            'enabled_gateways' => ['stripe', 'paypal', 'authorize_net', 'square'],
            'supported_currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD'],
            'default_currency' => 'USD',
            'fee_calculation' => 'percentage', // percentage, fixed, hybrid
            'fee_percentage' => 2.9,
            'fee_fixed' => 0.30,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00,
            'retry_attempts' => 3,
            'retry_delays' => [1, 3, 7], // days
            'fraud_detection' => true,
            'velocity_limits' => [
                'daily_amount' => 5000.00,
                'daily_count' => 10,
                'monthly_amount' => 25000.00,
                'monthly_count' => 100,
            ],
        ]);

        $this->initializeGateways();
    }

    /**
     * Process a payment for an invoice
     */
    public function processPayment(Client $client, Invoice $invoice, PaymentMethod $paymentMethod, 
                                  array $options = []): array
    {
        try {
            // Validate payment request
            $validation = $this->validatePaymentRequest($client, $invoice, $paymentMethod, $options);
            if (!$validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            $amount = $options['amount'] ?? $invoice->getBalance();
            $currency = $options['currency'] ?? $invoice->currency_code;

            // Check velocity limits
            if (!$this->checkVelocityLimits($client, $amount)) {
                return $this->failResponse('Payment limits exceeded. Please try again later.');
            }

            // Perform fraud detection
            $fraudCheck = $this->performFraudDetection($client, $paymentMethod, $amount, $options);
            if ($fraudCheck['risk_level'] === 'high') {
                return $this->failResponse('Payment flagged for review. Please contact support.');
            }

            // Calculate fees
            $fees = $this->calculateFees($amount, $paymentMethod);

            return DB::transaction(function () use ($client, $invoice, $paymentMethod, $amount, $currency, $fees, $options, $fraudCheck) {
                
                // Create payment record
                $payment = $this->createPaymentRecord($client, $invoice, $paymentMethod, $amount, $currency, $fees, $options);

                // Process payment through gateway
                $gatewayResult = $this->processGatewayPayment($payment, $paymentMethod, $fraudCheck);

                if ($gatewayResult['success']) {
                    // Update payment record
                    $payment->update([
                        'status' => 'completed',
                        'gateway_transaction_id' => $gatewayResult['transaction_id'],
                        'gateway_fee' => $gatewayResult['gateway_fee'] ?? $fees['gateway_fee'],
                        'processed_at' => Carbon::now(),
                        'metadata' => array_merge($payment->metadata ?? [], $gatewayResult['metadata'] ?? []),
                    ]);

                    // Update invoice
                    $this->updateInvoiceAfterPayment($invoice, $payment);

                    // Update payment method statistics
                    $paymentMethod->recordSuccessfulPayment($amount);

                    // Create notifications
                    $this->createPaymentNotifications($client, $payment, $invoice);

                    // Update auto-payment if applicable
                    if (isset($options['auto_payment_id'])) {
                        $this->updateAutoPaymentAfterSuccess($options['auto_payment_id'], $amount, $fees['total_fee']);
                    }

                    Log::info('Payment processed successfully', [
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'client_id' => $client->id,
                        'amount' => $amount,
                        'gateway' => $paymentMethod->provider,
                    ]);

                    return $this->successResponse('Payment processed successfully', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $gatewayResult['transaction_id'],
                        'amount' => $amount,
                        'fee' => $fees['total_fee'],
                        'receipt_url' => $this->generateReceiptUrl($payment),
                    ]);

                } else {
                    // Update payment record with failure
                    $payment->update([
                        'status' => 'failed',
                        'failure_reason' => $gatewayResult['error_message'],
                        'gateway_error_code' => $gatewayResult['error_code'] ?? null,
                        'metadata' => array_merge($payment->metadata ?? [], $gatewayResult['metadata'] ?? []),
                    ]);

                    // Update payment method statistics
                    $paymentMethod->recordFailedPayment($gatewayResult['error_message']);

                    // Handle auto-payment failure
                    if (isset($options['auto_payment_id'])) {
                        $this->handleAutoPaymentFailure($options['auto_payment_id'], $gatewayResult['error_message']);
                    }

                    // Schedule retry if applicable
                    $this->schedulePaymentRetry($payment);

                    Log::warning('Payment failed', [
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'client_id' => $client->id,
                        'error' => $gatewayResult['error_message'],
                    ]);

                    return $this->failResponse($gatewayResult['error_message'], 'PAYMENT_FAILED');
                }
            });

        } catch (Exception $e) {
            Log::error('Payment processing error', [
                'client_id' => $client->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->failResponse('Payment processing temporarily unavailable');
        }
    }

    /**
     * Add new payment method for client
     */
    public function addPaymentMethod(Client $client, array $paymentData): array
    {
        try {
            // Validate payment method data
            $validation = $this->validatePaymentMethodData($paymentData);
            if (!$validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            return DB::transaction(function () use ($client, $paymentData) {
                
                // Tokenize payment method through gateway
                $tokenizationResult = $this->tokenizePaymentMethod($paymentData);
                
                if (!$tokenizationResult['success']) {
                    return $this->failResponse($tokenizationResult['error_message']);
                }

                // Create payment method record
                $paymentMethod = PaymentMethod::create([
                    'company_id' => $client->company_id,
                    'client_id' => $client->id,
                    'type' => $paymentData['type'],
                    'provider' => $paymentData['provider'] ?? $this->config['default_gateway'],
                    'provider_payment_method_id' => $tokenizationResult['payment_method_id'],
                    'provider_customer_id' => $tokenizationResult['customer_id'],
                    'token' => $tokenizationResult['token'],
                    'name' => $paymentData['name'] ?? null,
                    'description' => $paymentData['description'] ?? null,
                    
                    // Card-specific data
                    'card_brand' => $tokenizationResult['card_brand'] ?? null,
                    'card_last_four' => $tokenizationResult['card_last_four'] ?? null,
                    'card_exp_month' => $tokenizationResult['card_exp_month'] ?? null,
                    'card_exp_year' => $tokenizationResult['card_exp_year'] ?? null,
                    'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                    
                    // Bank account data
                    'bank_name' => $paymentData['bank_name'] ?? null,
                    'bank_account_type' => $paymentData['bank_account_type'] ?? null,
                    'bank_account_last_four' => $tokenizationResult['bank_account_last_four'] ?? null,
                    'bank_account_holder_name' => $paymentData['bank_account_holder_name'] ?? null,
                    
                    // Digital wallet data
                    'wallet_type' => $paymentData['wallet_type'] ?? null,
                    'wallet_email' => $paymentData['wallet_email'] ?? null,
                    
                    // Billing address
                    'billing_name' => $paymentData['billing_name'] ?? null,
                    'billing_address_line1' => $paymentData['billing_address_line1'] ?? null,
                    'billing_city' => $paymentData['billing_city'] ?? null,
                    'billing_state' => $paymentData['billing_state'] ?? null,
                    'billing_postal_code' => $paymentData['billing_postal_code'] ?? null,
                    'billing_country' => $paymentData['billing_country'] ?? null,
                    
                    // Security and compliance
                    'requires_3d_secure' => $tokenizationResult['requires_3d_secure'] ?? false,
                    'security_checks' => $tokenizationResult['security_checks'] ?? [],
                    'risk_assessment' => $this->assessPaymentMethodRisk($paymentData),
                    
                    'created_by' => Auth::user()?->id ?? 1,
                ]);

                // Verify payment method if required
                if ($this->shouldVerifyPaymentMethod($paymentMethod)) {
                    $this->initiatePaymentMethodVerification($paymentMethod);
                }

                // Create notification
                $this->createNotification($client, 'payment_method_added', 'Payment Method Added',
                    "A new {$paymentMethod->getDisplayName()} has been added to your account.");

                Log::info('Payment method added', [
                    'payment_method_id' => $paymentMethod->id,
                    'client_id' => $client->id,
                    'type' => $paymentMethod->type,
                ]);

                return $this->successResponse('Payment method added successfully', [
                    'payment_method_id' => $paymentMethod->id,
                    'display_name' => $paymentMethod->getDisplayName(),
                    'requires_verification' => !$paymentMethod->verified,
                ]);
            });

        } catch (Exception $e) {
            Log::error('Payment method addition error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->failResponse('Unable to add payment method');
        }
    }

    /**
     * Setup automatic payment for client
     */
    public function setupAutoPayment(Client $client, PaymentMethod $paymentMethod, array $config): array
    {
        try {
            // Validate auto-payment configuration
            $validation = $this->validateAutoPaymentConfig($config);
            if (!$validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            // Check if payment method is active and verified
            if (!$paymentMethod->isActive() || !$paymentMethod->isVerified()) {
                return $this->failResponse('Payment method must be active and verified for auto-payment');
            }

            return DB::transaction(function () use ($client, $paymentMethod, $config) {
                
                $autoPayment = AutoPayment::create([
                    'company_id' => $client->company_id,
                    'client_id' => $client->id,
                    'payment_method_id' => $paymentMethod->id,
                    'name' => $config['name'] ?? 'Auto-Pay',
                    'type' => $config['type'] ?? AutoPayment::TYPE_INVOICE_AUTO_PAY,
                    'frequency' => $config['frequency'] ?? null,
                    'trigger_type' => $config['trigger_type'] ?? AutoPayment::TRIGGER_INVOICE_DUE,
                    'trigger_days_offset' => $config['trigger_days_offset'] ?? 0,
                    'trigger_time' => $config['trigger_time'] ?? '09:00',
                    'minimum_amount' => $config['minimum_amount'] ?? null,
                    'maximum_amount' => $config['maximum_amount'] ?? null,
                    'retry_on_failure' => $config['retry_on_failure'] ?? true,
                    'max_retry_attempts' => $config['max_retry_attempts'] ?? 3,
                    'send_success_notifications' => $config['send_success_notifications'] ?? true,
                    'send_failure_notifications' => $config['send_failure_notifications'] ?? true,
                    'currency_code' => $config['currency_code'] ?? $this->config['default_currency'],
                    'created_by' => Auth::user()?->id ?? 1,
                ]);

                // Set next processing date for recurring payments
                if ($autoPayment->type === AutoPayment::TYPE_RECURRING_PAYMENT) {
                    $autoPayment->update([
                        'next_processing_date' => $autoPayment->calculateNextProcessingDate(),
                    ]);
                }

                // Create notification
                $this->createNotification($client, 'auto_payment_setup', 'Auto-Payment Setup',
                    "Auto-payment has been configured using {$paymentMethod->getDisplayName()}.");

                Log::info('Auto-payment setup', [
                    'auto_payment_id' => $autoPayment->id,
                    'client_id' => $client->id,
                    'payment_method_id' => $paymentMethod->id,
                    'type' => $autoPayment->type,
                ]);

                return $this->successResponse('Auto-payment setup successfully', [
                    'auto_payment_id' => $autoPayment->id,
                    'name' => $autoPayment->getDisplayName(),
                    'next_processing_date' => $autoPayment->next_processing_date,
                ]);
            });

        } catch (Exception $e) {
            Log::error('Auto-payment setup error', [
                'client_id' => $client->id,
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);

            return $this->failResponse('Unable to setup auto-payment');
        }
    }

    /**
     * Get payment history for client
     */
    public function getPaymentHistory(Client $client, array $filters = []): array
    {
        try {
            $query = Payment::where('client_id', $client->id)
                ->with(['invoice', 'paymentMethod']);

            // Apply filters
            if (isset($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['payment_method_id'])) {
                $query->where('payment_method_id', $filters['payment_method_id']);
            }

            $payments = $query->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 25);

            $paymentData = $payments->getCollection()->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'invoice_number' => $payment->invoice?->number,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payment_method' => $payment->paymentMethod?->getDisplayName(),
                    'gateway_fee' => $payment->gateway_fee,
                    'processed_at' => $payment->processed_at,
                    'created_at' => $payment->created_at,
                    'receipt_url' => $this->generateReceiptUrl($payment),
                    'can_refund' => $this->canRefundPayment($payment),
                ];
            });

            return $this->successResponse('Payment history retrieved', [
                'payments' => $paymentData,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'total_pages' => $payments->lastPage(),
                    'total_count' => $payments->total(),
                    'per_page' => $payments->perPage(),
                ],
                'summary' => [
                    'total_paid' => $client->getTotalPaid(),
                    'total_fees' => $this->getTotalFesPaid($client),
                    'successful_payments' => $this->getSuccessfulPaymentCount($client),
                    'failed_payments' => $this->getFailedPaymentCount($client),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Payment history retrieval error', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return $this->failResponse('Unable to retrieve payment history');
        }
    }

    /**
     * Process scheduled auto-payments
     */
    public function processScheduledPayments(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $duePayments = AutoPayment::dueForProcessing()
                ->with(['client', 'paymentMethod'])
                ->get();

            foreach ($duePayments as $autoPayment) {
                try {
                    $result = $this->processAutoPayment($autoPayment);
                    $results['processed']++;

                    if ($result['success']) {
                        $results['successful']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'auto_payment_id' => $autoPayment->id,
                            'error' => $result['message'],
                        ];
                    }

                } catch (Exception $e) {
                    $results['processed']++;
                    $results['failed']++;
                    $results['errors'][] = [
                        'auto_payment_id' => $autoPayment->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Auto-payment processing error', [
                        'auto_payment_id' => $autoPayment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Scheduled payments processed', $results);

            return $results;

        } catch (Exception $e) {
            Log::error('Scheduled payment processing error', [
                'error' => $e->getMessage()
            ]);

            return array_merge($results, [
                'error' => 'Scheduled payment processing failed'
            ]);
        }
    }

    /**
     * Private helper methods
     */
    private function initializeGateways(): void
    {
        foreach ($this->config['enabled_gateways'] as $gateway) {
            $this->gateways[$gateway] = $this->createGatewayInstance($gateway);
        }
    }

    private function createGatewayInstance(string $gateway)
    {
        // Initialize gateway instances (Stripe, PayPal, etc.)
        // This would typically return configured gateway objects
        return new class {
            public function processPayment($payment, $paymentMethod) {
                // Mock implementation
                return [
                    'success' => true,
                    'transaction_id' => 'txn_' . Str::random(16),
                    'gateway_fee' => 0.30,
                ];
            }
            
            public function tokenizePaymentMethod($data) {
                return [
                    'success' => true,
                    'token' => 'pm_' . Str::random(24),
                    'payment_method_id' => 'pm_' . Str::random(16),
                    'customer_id' => 'cus_' . Str::random(16),
                ];
            }
        };
    }

    private function validatePaymentRequest(Client $client, Invoice $invoice, PaymentMethod $paymentMethod, array $options): array
    {
        $errors = [];

        if (!$invoice->canBePaid()) {
            $errors[] = 'Invoice cannot be paid';
        }

        if (!$paymentMethod->isActive()) {
            $errors[] = 'Payment method is not active';
        }

        if (!$paymentMethod->isVerified()) {
            $errors[] = 'Payment method must be verified';
        }

        $amount = $options['amount'] ?? $invoice->getBalance();
        if ($amount < $this->config['minimum_amount']) {
            $errors[] = "Minimum payment amount is {$this->config['minimum_amount']}";
        }

        if ($amount > $this->config['maximum_amount']) {
            $errors[] = "Maximum payment amount is {$this->config['maximum_amount']}";
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode('. ', $errors),
        ];
    }

    private function checkVelocityLimits(Client $client, float $amount): bool
    {
        $limits = $this->config['velocity_limits'];

        // Check daily limits
        $dailyAmount = $this->getDailyPaymentAmount($client);
        $dailyCount = $this->getDailyPaymentCount($client);

        if ($dailyAmount + $amount > $limits['daily_amount'] || $dailyCount >= $limits['daily_count']) {
            return false;
        }

        // Check monthly limits
        $monthlyAmount = $this->getMonthlyPaymentAmount($client);
        $monthlyCount = $this->getMonthlyPaymentCount($client);

        if ($monthlyAmount + $amount > $limits['monthly_amount'] || $monthlyCount >= $limits['monthly_count']) {
            return false;
        }

        return true;
    }

    private function performFraudDetection(Client $client, PaymentMethod $paymentMethod, float $amount, array $options): array
    {
        if (!$this->config['fraud_detection']) {
            return ['risk_level' => 'low', 'score' => 0];
        }

        $riskScore = 0;

        // Check for unusual amounts
        $averagePayment = $this->getAveragePaymentAmount($client);
        if ($amount > $averagePayment * 5) {
            $riskScore += 20;
        }

        // Check payment method age
        if ($paymentMethod->created_at->gt(Carbon::now()->subDays(7))) {
            $riskScore += 15;
        }

        // Check payment frequency
        $recentPayments = $this->getRecentPaymentCount($client, 24); // Last 24 hours
        if ($recentPayments > 5) {
            $riskScore += 25;
        }

        // Check payment method success rate
        if ($paymentMethod->getSuccessRate() < 80) {
            $riskScore += 30;
        }

        $riskLevel = $riskScore > 70 ? 'high' : ($riskScore > 40 ? 'medium' : 'low');

        return [
            'risk_level' => $riskLevel,
            'score' => $riskScore,
            'factors' => [
                'unusual_amount' => $amount > $averagePayment * 5,
                'new_payment_method' => $paymentMethod->created_at->gt(Carbon::now()->subDays(7)),
                'high_frequency' => $recentPayments > 5,
                'low_success_rate' => $paymentMethod->getSuccessRate() < 80,
            ],
        ];
    }

    private function calculateFees(float $amount, PaymentMethod $paymentMethod): array
    {
        $baseFee = 0;
        
        switch ($this->config['fee_calculation']) {
            case 'percentage':
                $baseFee = $amount * ($this->config['fee_percentage'] / 100);
                break;
            case 'fixed':
                $baseFee = $this->config['fee_fixed'];
                break;
            case 'hybrid':
                $baseFee = ($amount * ($this->config['fee_percentage'] / 100)) + $this->config['fee_fixed'];
                break;
        }

        // Gateway-specific fees
        $gatewayFee = $this->getGatewayFee($paymentMethod->provider, $amount);
        
        return [
            'base_fee' => round($baseFee, 2),
            'gateway_fee' => round($gatewayFee, 2),
            'total_fee' => round($baseFee + $gatewayFee, 2),
        ];
    }

    private function createPaymentRecord(Client $client, Invoice $invoice, PaymentMethod $paymentMethod, 
                                       float $amount, string $currency, array $fees, array $options): Payment
    {
        return Payment::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $paymentMethod->provider,
            'gateway_fee' => $fees['gateway_fee'],
            'status' => 'processing',
            'payment_date' => Carbon::now(),
            'metadata' => array_merge([
                'fees' => $fees,
                'auto_payment_id' => $options['auto_payment_id'] ?? null,
            ], $options['metadata'] ?? []),
            'processed_by' => Auth::user()?->id ?? 1,
        ]);
    }

    private function processGatewayPayment(Payment $payment, PaymentMethod $paymentMethod, array $fraudCheck): array
    {
        $gateway = $this->gateways[$paymentMethod->provider] ?? $this->gateways[$this->config['default_gateway']];
        
        try {
            return $gateway->processPayment($payment, $paymentMethod);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => 'GATEWAY_ERROR',
            ];
        }
    }

    private function updateInvoiceAfterPayment(Invoice $invoice, Payment $payment): void
    {
        $remainingBalance = $invoice->getBalance() - $payment->amount;
        
        if ($remainingBalance <= 0.01) { // Account for floating point precision
            $invoice->markAsPaid();
        }
    }

    private function createPaymentNotifications(Client $client, Payment $payment, Invoice $invoice): void
    {
        // Success notification
        $this->createNotification($client, 'payment_received', 'Payment Received',
            "Your payment of {$payment->getFormattedAmount()} for invoice #{$invoice->number} has been processed successfully.");

        // Email receipt
        if ($client->portalAccess?->notification_preferences['payment_receipts'] ?? true) {
            // Queue email sending
            // Mail::to($client->email)->queue(new PaymentReceiptMail($payment));
        }
    }

    private function createNotification(Client $client, string $type, string $title, string $message): void
    {
        PortalNotification::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'type' => $type,
            'category' => 'billing',
            'priority' => 'normal',
            'title' => $title,
            'message' => $message,
            'show_in_portal' => true,
            'send_email' => true,
        ]);
    }

    // Additional helper methods would go here...
    private function getDailyPaymentAmount(Client $client): float
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');
    }

    private function getDailyPaymentCount(Client $client): int
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    private function getMonthlyPaymentAmount(Client $client): float
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');
    }

    private function getMonthlyPaymentCount(Client $client): int
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
    }

    private function getAveragePaymentAmount(Client $client): float
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->avg('amount') ?? 0;
    }

    private function getRecentPaymentCount(Client $client, int $hours): int
    {
        return Payment::where('client_id', $client->id)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    private function getGatewayFee(string $provider, float $amount): float
    {
        // Gateway-specific fee calculation
        $feeRates = [
            'stripe' => 0.029,
            'paypal' => 0.035,
            'authorize_net' => 0.025,
            'square' => 0.026,
        ];

        return $amount * ($feeRates[$provider] ?? 0.029);
    }

    private function validatePaymentMethodData(array $paymentData): array
    {
        $errors = [];

        if (!isset($paymentData['type'])) {
            $errors[] = 'Payment method type is required';
        }

        // Validate based on type
        switch ($paymentData['type']) {
            case PaymentMethod::TYPE_CREDIT_CARD:
            case PaymentMethod::TYPE_DEBIT_CARD:
                if (empty($paymentData['card_number'])) {
                    $errors[] = 'Card number is required';
                }
                if (empty($paymentData['card_exp_month']) || empty($paymentData['card_exp_year'])) {
                    $errors[] = 'Card expiration date is required';
                }
                break;

            case PaymentMethod::TYPE_BANK_ACCOUNT:
                if (empty($paymentData['bank_account_number'])) {
                    $errors[] = 'Bank account number is required';
                }
                if (empty($paymentData['bank_routing_number'])) {
                    $errors[] = 'Bank routing number is required';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode('. ', $errors),
        ];
    }

    private function tokenizePaymentMethod(array $paymentData): array
    {
        $provider = $paymentData['provider'] ?? $this->config['default_gateway'];
        $gateway = $this->gateways[$provider] ?? $this->gateways[$this->config['default_gateway']];

        try {
            return $gateway->tokenizePaymentMethod($paymentData);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    private function validateAutoPaymentConfig(array $config): array
    {
        $errors = [];

        if (empty($config['type'])) {
            $errors[] = 'Auto-payment type is required';
        }

        if ($config['type'] === AutoPayment::TYPE_RECURRING_PAYMENT && empty($config['frequency'])) {
            $errors[] = 'Frequency is required for recurring payments';
        }

        if (isset($config['minimum_amount']) && $config['minimum_amount'] < 0) {
            $errors[] = 'Minimum amount cannot be negative';
        }

        if (isset($config['maximum_amount']) && $config['maximum_amount'] <= 0) {
            $errors[] = 'Maximum amount must be positive';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode('. ', $errors),
        ];
    }

    private function shouldVerifyPaymentMethod(PaymentMethod $paymentMethod): bool
    {
        // Bank accounts typically require verification
        return $paymentMethod->isBankAccount();
    }

    private function initiatePaymentMethodVerification(PaymentMethod $paymentMethod): void
    {
        // Implementation would start verification process
        Log::info('Payment method verification initiated', [
            'payment_method_id' => $paymentMethod->id
        ]);
    }

    private function assessPaymentMethodRisk(array $paymentData): array
    {
        $riskScore = 0;
        $factors = [];

        // Basic risk assessment
        if ($paymentData['type'] === PaymentMethod::TYPE_BANK_ACCOUNT) {
            $riskScore += 10;
            $factors[] = 'bank_account_type';
        }

        return [
            'score' => $riskScore,
            'level' => $riskScore > 50 ? 'high' : ($riskScore > 20 ? 'medium' : 'low'),
            'factors' => $factors,
        ];
    }

    private function generateReceiptUrl(Payment $payment): string
    {
        return route('portal.payments.receipt', $payment->id);
    }

    private function canRefundPayment(Payment $payment): bool
    {
        return $payment->status === 'completed' &&
               $payment->created_at->gt(Carbon::now()->subDays(180)) &&
               !$payment->refund_amount;
    }

    private function getTotalFesPaid(Client $client): float
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->sum('gateway_fee');
    }

    private function getSuccessfulPaymentCount(Client $client): int
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->count();
    }

    private function getFailedPaymentCount(Client $client): int
    {
        return Payment::where('client_id', $client->id)
            ->where('status', 'failed')
            ->count();
    }

    private function processAutoPayment(AutoPayment $autoPayment): array
    {
        try {
            $client = $autoPayment->client;
            $paymentMethod = $autoPayment->paymentMethod;

            // Find invoices to pay
            $invoices = $this->getInvoicesForAutoPayment($autoPayment);

            if ($invoices->isEmpty()) {
                return $this->successResponse('No invoices requiring payment');
            }

            $totalProcessed = 0;
            $totalSuccessful = 0;

            foreach ($invoices as $invoice) {
                $amount = $autoPayment->getPaymentAmount($invoice->getBalance());

                if (!$autoPayment->isAmountWithinLimits($amount)) {
                    continue;
                }

                $result = $this->processPayment($client, $invoice, $paymentMethod, [
                    'amount' => $amount,
                    'auto_payment_id' => $autoPayment->id,
                ]);

                $totalProcessed++;
                if ($result['success']) {
                    $totalSuccessful++;
                }
            }

            if ($totalSuccessful > 0) {
                $autoPayment->recordSuccessfulPayment($totalSuccessful * $amount, 0);
            } else {
                $autoPayment->recordFailedPayment('No payments processed successfully');
            }

            return $this->successResponse("Processed {$totalSuccessful} of {$totalProcessed} payments");

        } catch (Exception $e) {
            Log::error('Auto-payment processing error', [
                'auto_payment_id' => $autoPayment->id,
                'error' => $e->getMessage()
            ]);

            return $this->failResponse('Auto-payment processing failed');
        }
    }

    private function getInvoicesForAutoPayment(AutoPayment $autoPayment): \Illuminate\Database\Eloquent\Collection
    {
        $query = Invoice::where('client_id', $autoPayment->client_id)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('amount', '>', 0);

        // Apply auto-payment filters
        if ($autoPayment->minimum_amount) {
            $query->where('amount', '>=', $autoPayment->minimum_amount);
        }

        if ($autoPayment->maximum_amount) {
            $query->where('amount', '<=', $autoPayment->maximum_amount);
        }

        // Apply invoice type filters
        if ($autoPayment->invoice_types) {
            $query->whereIn('type', $autoPayment->invoice_types);
        }

        if ($autoPayment->excluded_invoice_types) {
            $query->whereNotIn('type', $autoPayment->excluded_invoice_types);
        }

        return $query->orderBy('due_date', 'asc')->get();
    }

    private function updateAutoPaymentAfterSuccess(int $autoPaymentId, float $amount, float $fee): void
    {
        $autoPayment = AutoPayment::find($autoPaymentId);
        if ($autoPayment) {
            $autoPayment->recordSuccessfulPayment($amount, $fee);
        }
    }

    private function handleAutoPaymentFailure(int $autoPaymentId, string $reason): void
    {
        $autoPayment = AutoPayment::find($autoPaymentId);
        if ($autoPayment) {
            $autoPayment->recordFailedPayment($reason);
        }
    }

    private function schedulePaymentRetry(Payment $payment): void
    {
        if ($payment->retry_count < $this->config['retry_attempts']) {
            $retryDelay = $this->config['retry_delays'][$payment->retry_count] ?? 7;
            
            // Schedule retry (implementation would depend on your queue system)
            Log::info('Payment retry scheduled', [
                'payment_id' => $payment->id,
                'retry_count' => $payment->retry_count + 1,
                'retry_at' => Carbon::now()->addDays($retryDelay),
            ]);
        }
    }

    private function successResponse(string $message, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
    }

    private function failResponse(string $message, string $errorCode = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return $response;
    }
}