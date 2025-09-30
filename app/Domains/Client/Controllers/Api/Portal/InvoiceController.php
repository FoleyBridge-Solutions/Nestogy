<?php

namespace App\Domains\Client\Controllers\Api\Portal;

use App\Http\Controllers\Controller;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * Portal Invoice Controller
 * 
 * Handles invoice-related functionality including:
 * - Invoice listing and search
 * - Invoice details and line items
 * - Invoice PDF generation and download
 * - Payment status tracking
 * - Invoice history and archives
 */
class InvoiceController extends PortalApiController
{
    /**
     * Get invoices for client
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            
            $this->applyRateLimit('invoices', 120, 60);
            $this->logActivity('invoices_view');

            $filters = $this->getFilterParams($request, [
                'status', 'start_date', 'end_date', 'search'
            ]);

            $query = $client->invoices()->with(['items', 'payments']);

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['start_date'])) {
                $query->whereDate('date', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->whereDate('date', '<=', $filters['end_date']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('number', 'like', "%{$filters['search']}%")
                      ->orWhere('description', 'like', "%{$filters['search']}%");
                });
            }

            $invoices = $query->orderBy('date', 'desc')
                ->paginate($filters['per_page'] ?? 20);

            $invoiceData = $invoices->getCollection()->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date,
                    'due_date' => $invoice->due_date,
                    'status' => $invoice->status,
                    'amount' => $invoice->amount,
                    'balance' => $invoice->getBalance(),
                    'paid_amount' => $invoice->getPaidAmount(),
                    'currency_code' => $invoice->currency_code,
                    'description' => $invoice->description,
                    'is_overdue' => $invoice->isOverdue(),
                    'days_overdue' => $invoice->getDaysOverdue(),
                    'items_count' => $invoice->items->count(),
                    'payments_count' => $invoice->payments->count(),
                    'last_payment_date' => $invoice->getLastPaymentDate(),
                    'can_be_paid' => $invoice->canBePaid(),
                    'pdf_url' => route('portal.invoices.pdf', $invoice->id),
                    'created_at' => $invoice->created_at,
                ];
            });

            return $this->successResponse('Invoices retrieved successfully', [
                'invoices' => $invoiceData,
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'total_pages' => $invoices->lastPage(),
                    'total_count' => $invoices->total(),
                    'per_page' => $invoices->perPage(),
                ],
                'summary' => [
                    'total_outstanding' => $client->getOutstandingBalance(),
                    'overdue_amount' => $client->getOverdueAmount(),
                    'total_paid_this_year' => $client->getTotalPaidThisYear(),
                    'invoice_count' => $invoices->total(),
                ],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'invoices retrieval');
        }
    }

    /**
     * Get specific invoice details
     */
    public function show(Request $request, int $invoiceId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            
            $this->applyRateLimit('invoice_details', 180, 60);
            $this->logActivity('invoice_view', ['invoice_id' => $invoiceId]);

            $invoice = $client->invoices()
                ->with(['items', 'payments.paymentMethod'])
                ->where('id', $invoiceId)
                ->first();

            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }

            $invoiceData = [
                'invoice' => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date,
                    'due_date' => $invoice->due_date,
                    'status' => $invoice->status,
                    'amount' => $invoice->amount,
                    'balance' => $invoice->getBalance(),
                    'paid_amount' => $invoice->getPaidAmount(),
                    'tax_amount' => $invoice->tax_amount,
                    'discount_amount' => $invoice->discount_amount,
                    'currency_code' => $invoice->currency_code,
                    'description' => $invoice->description,
                    'notes' => $invoice->notes,
                    'terms' => $invoice->terms,
                    'is_overdue' => $invoice->isOverdue(),
                    'days_overdue' => $invoice->getDaysOverdue(),
                    'can_be_paid' => $invoice->canBePaid(),
                    'pdf_url' => route('portal.invoices.pdf', $invoice->id),
                    'created_at' => $invoice->created_at,
                    'updated_at' => $invoice->updated_at,
                ],
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                        'tax_rate' => $item->tax_rate,
                        'tax_amount' => $item->tax_amount,
                        'discount_amount' => $item->discount_amount,
                    ];
                }),
                'payments' => $invoice->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'gateway' => $payment->gateway,
                        'payment_method' => $payment->paymentMethod?->getDisplayName(),
                        'gateway_transaction_id' => $payment->gateway_transaction_id,
                        'processed_at' => $payment->processed_at,
                        'receipt_url' => route('portal.payments.receipt', $payment->id),
                    ];
                }),
                'billing_info' => [
                    'company_name' => $client->company_name,
                    'contact_name' => $client->contact_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'billing_address' => $client->getBillingAddress(),
                ],
            ];

            return $this->successResponse('Invoice details retrieved successfully', $invoiceData);

        } catch (Exception $e) {
            return $this->handleException($e, 'invoice details retrieval');
        }
    }

    /**
     * Get invoice summary/dashboard stats
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            
            $this->applyRateLimit('invoice_summary', 60, 60);
            $this->logActivity('invoice_summary_view');

            $now = now();
            $startOfYear = $now->copy()->startOfYear();
            $startOfMonth = $now->copy()->startOfMonth();
            
            $summary = [
                'current_balance' => $client->getBalance(),
                'total_outstanding' => $client->getOutstandingBalance(),
                'overdue_amount' => $client->getOverdueAmount(),
                'overdue_count' => $client->invoices()->overdue()->count(),
                
                'this_year' => [
                    'total_invoiced' => $client->invoices()
                        ->where('date', '>=', $startOfYear)
                        ->sum('amount'),
                    'total_paid' => $client->getTotalPaidInRange($startOfYear, $now),
                    'invoice_count' => $client->invoices()
                        ->where('date', '>=', $startOfYear)
                        ->count(),
                ],
                
                'this_month' => [
                    'total_invoiced' => $client->invoices()
                        ->where('date', '>=', $startOfMonth)
                        ->sum('amount'),
                    'total_paid' => $client->getTotalPaidInRange($startOfMonth, $now),
                    'invoice_count' => $client->invoices()
                        ->where('date', '>=', $startOfMonth)
                        ->count(),
                ],
                
                'recent_invoices' => $client->invoices()
                    ->latest('date')
                    ->take(5)
                    ->get()
                    ->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'number' => $invoice->number,
                            'date' => $invoice->date,
                            'amount' => $invoice->amount,
                            'balance' => $invoice->getBalance(),
                            'status' => $invoice->status,
                        ];
                    }),
                    
                'upcoming_due' => $client->invoices()
                    ->where('status', '!=', 'paid')
                    ->where('due_date', '>', $now)
                    ->where('due_date', '<=', $now->copy()->addDays(30))
                    ->orderBy('due_date')
                    ->take(10)
                    ->get()
                    ->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'number' => $invoice->number,
                            'due_date' => $invoice->due_date,
                            'amount' => $invoice->amount,
                            'balance' => $invoice->getBalance(),
                            'days_until_due' => $invoice->getDaysUntilDue(),
                        ];
                    }),
            ];

            return $this->successResponse('Invoice summary retrieved successfully', $summary);

        } catch (Exception $e) {
            return $this->handleException($e, 'invoice summary retrieval');
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadPdf(Request $request, int $invoiceId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            
            $this->applyRateLimit('invoice_pdf', 30, 60);
            $this->logActivity('invoice_pdf_download', ['invoice_id' => $invoiceId]);

            $invoice = $client->invoices()
                ->where('id', $invoiceId)
                ->first();

            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }

            // In a real implementation, you would generate or retrieve the PDF
            // For now, return the URL where the PDF can be accessed
            $pdfUrl = route('portal.invoices.pdf', $invoice->id);
            
            return $this->successResponse('Invoice PDF available for download', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'pdf_url' => $pdfUrl,
                'download_url' => route('portal.invoices.download', $invoice->id),
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'invoice PDF download');
        }
    }

    /**
     * Get payment options for invoice
     */
    public function paymentOptions(Request $request, int $invoiceId): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            
            $this->applyRateLimit('payment_options', 60, 60);
            $this->logActivity('payment_options_view', ['invoice_id' => $invoiceId]);

            $invoice = $client->invoices()
                ->where('id', $invoiceId)
                ->first();

            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }

            if (!$invoice->canBePaid()) {
                return $this->errorResponse('Invoice cannot be paid', 400);
            }

            $paymentMethods = $client->paymentMethods()
                ->active()
                ->verified()
                ->get()
                ->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'type' => $method->type,
                        'display_name' => $method->getDisplayName(),
                        'is_default' => $method->is_default,
                        'success_rate' => $method->getSuccessRate(),
                    ];
                });

            $balance = $invoice->getBalance();
            
            $paymentOptions = [
                'invoice' => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'balance' => $balance,
                    'currency_code' => $invoice->currency_code,
                    'due_date' => $invoice->due_date,
                    'is_overdue' => $invoice->isOverdue(),
                ],
                'payment_methods' => $paymentMethods,
                'payment_amounts' => [
                    'full_balance' => $balance,
                    'minimum_payment' => max(10, $balance * 0.1), // 10% minimum or $10
                    'suggested_amounts' => [
                        round($balance * 0.25, 2),
                        round($balance * 0.5, 2),
                        round($balance * 0.75, 2),
                        $balance,
                    ],
                ],
                'fees' => [
                    'processing_fee' => $balance * 0.029, // 2.9% estimate
                    'fee_description' => 'Processing fees may apply based on payment method',
                ],
            ];

            return $this->successResponse('Payment options retrieved successfully', $paymentOptions);

        } catch (Exception $e) {
            return $this->handleException($e, 'payment options retrieval');
        }
    }

    /**
     * Get invoice statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $client = $this->requireAuthentication();
            $this->requirePermission('view_analytics');
            
            $this->applyRateLimit('invoice_statistics', 30, 60);
            $this->logActivity('invoice_statistics_view');

            $dateRange = $this->validateDateRange($request);
            $startDate = $dateRange['start_date'] ?? now()->subYear();
            $endDate = $dateRange['end_date'] ?? now();

            $baseQuery = $client->invoices()
                ->whereBetween('date', [$startDate, $endDate]);

            $statistics = [
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'totals' => [
                    'invoice_count' => $baseQuery->count(),
                    'total_amount' => $baseQuery->sum('amount'),
                    'paid_amount' => $client->getTotalPaidInRange($startDate, $endDate),
                    'outstanding_amount' => $baseQuery->where('status', '!=', 'paid')->sum('amount'),
                ],
                'by_status' => $client->invoices()
                    ->selectRaw('status, count(*) as count, sum(amount) as total')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->groupBy('status')
                    ->get()
                    ->pluck('total', 'status')
                    ->toArray(),
                'payment_trends' => $this->getPaymentTrends($client, $startDate, $endDate),
                'average_amounts' => [
                    'average_invoice' => $baseQuery->avg('amount'),
                    'average_payment_time' => $client->getAveragePaymentTime(),
                ],
            ];

            return $this->successResponse('Invoice statistics retrieved successfully', $statistics);

        } catch (Exception $e) {
            return $this->handleException($e, 'invoice statistics retrieval');
        }
    }

    /**
     * Get payment trends for statistics
     */
    private function getPaymentTrends($client, $startDate, $endDate): array
    {
        // This is a simplified version - in a real implementation you'd create more detailed trends
        $months = [];
        $current = $startDate->copy()->startOfMonth();
        
        while ($current->lte($endDate)) {
            $monthStart = $current->copy();
            $monthEnd = $current->copy()->endOfMonth();
            
            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'invoiced' => $client->invoices()
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount'),
                'paid' => $client->getTotalPaidInRange($monthStart, $monthEnd),
            ];
            
            $current->addMonth();
        }
        
        return $months;
    }
}