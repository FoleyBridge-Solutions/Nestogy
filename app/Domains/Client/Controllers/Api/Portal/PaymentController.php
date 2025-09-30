<?php

namespace App\Domains\Client\Controllers\Api\Portal;

use App\Domains\Financial\Services\PortalPaymentService;
use App\Http\Controllers\Controller;
use App\Models\AutoPayment;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Portal Payment Controller
 *
 * Handles payment-related functionality including:
 * - Payment processing and history
 * - Payment method management
 * - Auto-payment setup and management
 * - Payment receipts and documentation
 * - Payment scheduling and planning
 */
class PaymentController extends PortalApiController
{
    protected PortalPaymentService $paymentService;

    public function __construct(PortalPaymentService $paymentService, \App\Services\ClientPortalService $portalService, \App\Domains\Security\Services\PortalAuthService $authService)
    {
        parent::__construct($portalService, $authService);
        $this->paymentService = $paymentService;
    }

    /**
     * Get payment history for client
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('payment_history', 60, 60);
            $this->logActivity('payment_history_view');

            $filters = $this->getFilterParams($request, [
                'start_date', 'end_date', 'status', 'payment_method_id',
            ]);

            $paymentHistory = $this->paymentService->getPaymentHistory($client, $filters);

            return $this->handleServiceResponse($paymentHistory, 'Payment history retrieved successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'payment history retrieval');
        }
    }

    /**
     * Process a payment for an invoice
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('make_payments');

            $this->applyRateLimit('process_payment', 5, 300); // 5 payments per 5 minutes

            // Validate request
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|integer|exists:invoices,id',
                'payment_method_id' => 'required|integer|exists:payment_methods,id',
                'amount' => 'nullable|numeric|min:0.01',
                'currency' => 'nullable|string|size:3',
                'save_payment_method' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // Get invoice and payment method
            $invoice = Invoice::where('id', $validated['invoice_id'])
                ->where('client_id', $client->id)
                ->first();

            if (! $invoice) {
                return $this->errorResponse('Invoice not found or access denied', 404);
            }

            $paymentMethod = PaymentMethod::where('id', $validated['payment_method_id'])
                ->where('client_id', $client->id)
                ->first();

            if (! $paymentMethod) {
                return $this->errorResponse('Payment method not found or access denied', 404);
            }

            $this->logActivity('process_payment', [
                'invoice_id' => $invoice->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $validated['amount'] ?? $invoice->getBalance(),
            ]);

            $paymentResult = $this->paymentService->processPayment($client, $invoice, $paymentMethod, [
                'amount' => $validated['amount'] ?? $invoice->getBalance(),
                'currency' => $validated['currency'] ?? $invoice->currency_code,
                'metadata' => [
                    'source' => 'portal',
                    'save_payment_method' => $validated['save_payment_method'] ?? false,
                ],
            ]);

            return $this->handleServiceResponse($paymentResult, 'Payment processed successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'payment processing');
        }
    }

    /**
     * Get payment methods for client
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('payment_methods', 120, 60);
            $this->logActivity('payment_methods_view');

            $paymentMethods = $client->paymentMethods()
                ->active()
                ->get()
                ->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'type' => $method->type,
                        'display_name' => $method->getDisplayName(),
                        'is_default' => $method->is_default,
                        'is_verified' => $method->isVerified(),
                        'expires_soon' => $method->expiresSoon(),
                        'last_used_at' => $method->last_used_at,
                        'success_rate' => $method->getSuccessRate(),
                        'created_at' => $method->created_at,
                    ];
                });

            return $this->successResponse('Payment methods retrieved successfully', [
                'payment_methods' => $paymentMethods,
                'total_count' => $paymentMethods->count(),
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'payment methods retrieval');
        }
    }

    /**
     * Add new payment method
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('manage_payment_methods');

            $this->applyRateLimit('add_payment_method', 10, 3600); // 10 per hour

            // Validate request based on payment method type
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:credit_card,debit_card,bank_account,digital_wallet',
                'provider' => 'nullable|string|in:stripe,paypal,authorize_net,square',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:500',
                'is_default' => 'boolean',

                // Card fields
                'card_number' => 'required_if:type,credit_card,debit_card|string',
                'card_exp_month' => 'required_if:type,credit_card,debit_card|integer|between:1,12',
                'card_exp_year' => 'required_if:type,credit_card,debit_card|integer|min:'.date('Y'),
                'card_cvc' => 'required_if:type,credit_card,debit_card|string|min:3|max:4',
                'card_holder_name' => 'required_if:type,credit_card,debit_card|string|max:255',

                // Bank account fields
                'bank_account_number' => 'required_if:type,bank_account|string',
                'bank_routing_number' => 'required_if:type,bank_account|string',
                'bank_account_type' => 'required_if:type,bank_account|string|in:checking,savings',
                'bank_account_holder_name' => 'required_if:type,bank_account|string|max:255',
                'bank_name' => 'nullable|string|max:255',

                // Digital wallet fields
                'wallet_type' => 'required_if:type,digital_wallet|string|in:paypal,apple_pay,google_pay',
                'wallet_email' => 'required_if:wallet_type,paypal|email',

                // Billing address
                'billing_name' => 'nullable|string|max:255',
                'billing_address_line1' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_postal_code' => 'nullable|string|max:20',
                'billing_country' => 'nullable|string|size:2',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $this->logActivity('add_payment_method', [
                'type' => $validator->validated()['type'],
            ]);

            $result = $this->paymentService->addPaymentMethod($client, $validator->validated());

            return $this->handleServiceResponse($result, 'Payment method added successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'payment method addition');
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod(Request $request, int $paymentMethodId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('manage_payment_methods');

            $this->applyRateLimit('update_payment_method', 20, 3600);

            $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
                ->where('client_id', $client->id)
                ->first();

            if (! $paymentMethod) {
                return $this->errorResponse('Payment method not found', 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:500',
                'is_default' => 'boolean',
                'is_active' => 'boolean',

                // Limited updates allowed for security
                'card_exp_month' => 'nullable|integer|between:1,12',
                'card_exp_year' => 'nullable|integer|min:'.date('Y'),
                'billing_name' => 'nullable|string|max:255',
                'billing_address_line1' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_postal_code' => 'nullable|string|max:20',
                'billing_country' => 'nullable|string|size:2',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $updateData = array_filter($validator->validated());

            // Handle default payment method logic
            if (isset($updateData['is_default']) && $updateData['is_default']) {
                // Remove default from other methods
                $client->paymentMethods()->update(['is_default' => false]);
            }

            $paymentMethod->update($updateData);

            $this->logActivity('update_payment_method', [
                'payment_method_id' => $paymentMethodId,
                'updated_fields' => array_keys($updateData),
            ]);

            return $this->successResponse('Payment method updated successfully', [
                'payment_method' => [
                    'id' => $paymentMethod->id,
                    'display_name' => $paymentMethod->getDisplayName(),
                    'is_default' => $paymentMethod->is_default,
                    'updated_at' => $paymentMethod->updated_at,
                ],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'payment method update');
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(Request $request, int $paymentMethodId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('manage_payment_methods');

            $this->applyRateLimit('delete_payment_method', 10, 3600);

            $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
                ->where('client_id', $client->id)
                ->first();

            if (! $paymentMethod) {
                return $this->errorResponse('Payment method not found', 404);
            }

            // Check if payment method is used in active auto-payments
            $activeAutoPayments = AutoPayment::where('payment_method_id', $paymentMethodId)
                ->active()
                ->count();

            if ($activeAutoPayments > 0) {
                return $this->errorResponse('Cannot delete payment method with active auto-payments', 400);
            }

            // Soft delete the payment method
            $paymentMethod->update(['is_active' => false, 'deleted_at' => now()]);

            $this->logActivity('delete_payment_method', [
                'payment_method_id' => $paymentMethodId,
            ]);

            return $this->successResponse('Payment method deleted successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'payment method deletion');
        }
    }

    /**
     * Setup auto-payment
     */
    public function setupAutoPayment(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('manage_auto_payments');

            $this->applyRateLimit('setup_auto_payment', 5, 3600);

            // Validate request
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required|integer|exists:payment_methods,id',
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:invoice_auto_pay,recurring_payment',
                'frequency' => 'nullable|string|in:weekly,monthly,quarterly,annually',
                'trigger_type' => 'required|string|in:invoice_due,invoice_sent,scheduled',
                'trigger_days_offset' => 'nullable|integer|between:-30,30',
                'trigger_time' => 'nullable|date_format:H:i',
                'minimum_amount' => 'nullable|numeric|min:0',
                'maximum_amount' => 'nullable|numeric|min:0',
                'retry_on_failure' => 'boolean',
                'max_retry_attempts' => 'nullable|integer|between:1,5',
                'send_success_notifications' => 'boolean',
                'send_failure_notifications' => 'boolean',
                'currency_code' => 'nullable|string|size:3',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $paymentMethod = PaymentMethod::where('id', $validator->validated()['payment_method_id'])
                ->where('client_id', $client->id)
                ->first();

            if (! $paymentMethod) {
                return $this->errorResponse('Payment method not found', 404);
            }

            $this->logActivity('setup_auto_payment', [
                'payment_method_id' => $paymentMethod->id,
                'type' => $validator->validated()['type'],
            ]);

            $result = $this->paymentService->setupAutoPayment($client, $paymentMethod, $validator->validated());

            return $this->handleServiceResponse($result, 'Auto-payment setup successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'auto-payment setup');
        }
    }

    /**
     * Get auto-payments for client
     */
    public function autoPayments(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('auto_payments', 60, 60);
            $this->logActivity('auto_payments_view');

            $autoPayments = $client->autoPayments()
                ->with('paymentMethod')
                ->get()
                ->map(function ($autoPayment) {
                    return [
                        'id' => $autoPayment->id,
                        'name' => $autoPayment->name,
                        'type' => $autoPayment->type,
                        'frequency' => $autoPayment->frequency,
                        'trigger_type' => $autoPayment->trigger_type,
                        'is_active' => $autoPayment->is_active,
                        'payment_method' => $autoPayment->paymentMethod?->getDisplayName(),
                        'next_processing_date' => $autoPayment->next_processing_date,
                        'total_processed_amount' => $autoPayment->total_processed_amount,
                        'successful_payments_count' => $autoPayment->successful_payments_count,
                        'failed_payments_count' => $autoPayment->failed_payments_count,
                        'last_processed_at' => $autoPayment->last_processed_at,
                        'created_at' => $autoPayment->created_at,
                    ];
                });

            return $this->successResponse('Auto-payments retrieved successfully', [
                'auto_payments' => $autoPayments,
                'total_count' => $autoPayments->count(),
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'auto-payments retrieval');
        }
    }

    /**
     * Update auto-payment
     */
    public function updateAutoPayment(Request $request, int $autoPaymentId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('manage_auto_payments');

            $autoPayment = AutoPayment::where('id', $autoPaymentId)
                ->where('client_id', $client->id)
                ->first();

            if (! $autoPayment) {
                return $this->errorResponse('Auto-payment not found', 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'minimum_amount' => 'nullable|numeric|min:0',
                'maximum_amount' => 'nullable|numeric|min:0',
                'send_success_notifications' => 'boolean',
                'send_failure_notifications' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $updateData = array_filter($validator->validated(), function ($value) {
                return $value !== null;
            });

            $autoPayment->update($updateData);

            $this->logActivity('update_auto_payment', [
                'auto_payment_id' => $autoPaymentId,
                'updated_fields' => array_keys($updateData),
            ]);

            return $this->successResponse('Auto-payment updated successfully', [
                'auto_payment' => [
                    'id' => $autoPayment->id,
                    'name' => $autoPayment->name,
                    'is_active' => $autoPayment->is_active,
                    'updated_at' => $autoPayment->updated_at,
                ],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'auto-payment update');
        }
    }

    /**
     * Cancel auto-payment
     */
    public function cancelAutoPayment(Request $request, int $autoPaymentId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('manage_auto_payments');

            $autoPayment = AutoPayment::where('id', $autoPaymentId)
                ->where('client_id', $client->id)
                ->first();

            if (! $autoPayment) {
                return $this->errorResponse('Auto-payment not found', 404);
            }

            $autoPayment->update([
                'is_active' => false,
                'cancelled_at' => now(),
            ]);

            $this->logActivity('cancel_auto_payment', [
                'auto_payment_id' => $autoPaymentId,
            ]);

            return $this->successResponse('Auto-payment cancelled successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'auto-payment cancellation');
        }
    }

    /**
     * Get payment receipt
     */
    public function receipt(Request $request, int $paymentId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();

            $this->applyRateLimit('payment_receipt', 60, 60);

            $payment = $client->payments()
                ->where('id', $paymentId)
                ->with(['invoice', 'paymentMethod'])
                ->first();

            if (! $payment) {
                return $this->errorResponse('Payment not found', 404);
            }

            $this->logActivity('payment_receipt_view', [
                'payment_id' => $paymentId,
            ]);

            $receiptData = [
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'gateway' => $payment->gateway,
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                    'processed_at' => $payment->processed_at,
                    'gateway_fee' => $payment->gateway_fee,
                ],
                'invoice' => [
                    'id' => $payment->invoice->id,
                    'number' => $payment->invoice->number,
                    'date' => $payment->invoice->date,
                    'due_date' => $payment->invoice->due_date,
                ],
                'payment_method' => [
                    'type' => $payment->paymentMethod->type,
                    'display_name' => $payment->paymentMethod->getDisplayName(),
                ],
                'client' => [
                    'company_name' => $client->company_name,
                    'contact_name' => $client->contact_name,
                    'email' => $client->email,
                ],
                'receipt_url' => route('portal.payments.receipt.pdf', $payment->id),
            ];

            return $this->successResponse('Payment receipt retrieved successfully', $receiptData);

        } catch (Exception $e) {
            return $this->handleException($e, 'payment receipt retrieval');
        }
    }
}
