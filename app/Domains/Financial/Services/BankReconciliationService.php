<?php

namespace App\Domains\Financial\Services;

use App\Domains\Company\Models\Account;
use App\Domains\Financial\Models\BankTransaction;
use App\Domains\Financial\Models\Expense;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BankReconciliationService
 *
 * Handles reconciliation of bank transactions with payments and expenses.
 * Includes automatic matching logic and manual reconciliation methods.
 */
class BankReconciliationService
{
    /**
     * Tolerance for amount matching (in dollars)
     */
    const AMOUNT_TOLERANCE = 0.01;

    /**
     * Date range tolerance for matching (in days)
     */
    const DATE_TOLERANCE = 3;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Find matching payment for a bank transaction.
     */
    public function findMatchingPayment(BankTransaction $transaction): ?Payment
    {
        // Only match positive amounts (income)
        if ($transaction->amount <= 0) {
            return null;
        }

        $amount = abs($transaction->amount);
        $dateFrom = Carbon::parse($transaction->date)->subDays(self::DATE_TOLERANCE);
        $dateTo = Carbon::parse($transaction->date)->addDays(self::DATE_TOLERANCE);

        return Payment::where('company_id', $transaction->company_id)
            ->whereNull('bank_transaction_id')
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->whereBetween('amount', [
                $amount - self::AMOUNT_TOLERANCE,
                $amount + self::AMOUNT_TOLERANCE,
            ])
            ->where('status', 'completed')
            ->orderByRaw('ABS(amount - ?) ASC', [$amount])
            ->orderByRaw('ABS(DATEDIFF(payment_date, ?)) ASC', [$transaction->date])
            ->first();
    }

    /**
     * Find matching expense for a bank transaction.
     */
    public function findMatchingExpense(BankTransaction $transaction): ?Expense
    {
        // Only match negative amounts (expenses)
        if ($transaction->amount >= 0) {
            return null;
        }

        $amount = abs($transaction->amount);
        $dateFrom = Carbon::parse($transaction->date)->subDays(self::DATE_TOLERANCE);
        $dateTo = Carbon::parse($transaction->date)->addDays(self::DATE_TOLERANCE);

        return Expense::where('company_id', $transaction->company_id)
            ->whereNull('bank_transaction_id')
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->whereBetween('amount', [
                $amount - self::AMOUNT_TOLERANCE,
                $amount + self::AMOUNT_TOLERANCE,
            ])
            ->whereIn('status', ['approved', 'paid'])
            ->orderByRaw('ABS(amount - ?) ASC', [$amount])
            ->orderByRaw('ABS(DATEDIFF(expense_date, ?)) ASC', [$transaction->date])
            ->first();
    }

    /**
     * Find matching invoice for a bank transaction.
     */
    public function findMatchingInvoice(BankTransaction $transaction): ?Invoice
    {
        // Only match positive amounts (income)
        if ($transaction->amount <= 0) {
            return null;
        }

        $amount = abs($transaction->amount);
        $dateFrom = Carbon::parse($transaction->date)->subDays(self::DATE_TOLERANCE);
        $dateTo = Carbon::parse($transaction->date)->addDays(self::DATE_TOLERANCE);

        return Invoice::where('company_id', $transaction->company_id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereBetween('total', [
                $amount - self::AMOUNT_TOLERANCE,
                $amount + self::AMOUNT_TOLERANCE,
            ])
            ->whereIn('status', ['sent', 'partial'])
            ->orderByRaw('ABS(total - ?) ASC', [$amount])
            ->first();
    }

    /**
     * Auto-reconcile a single transaction.
     */
    public function autoReconcileTransaction(BankTransaction $transaction): bool
    {
        if ($transaction->is_reconciled || $transaction->is_ignored) {
            return false;
        }

        try {
            DB::beginTransaction();

            if ($transaction->isIncome()) {
                // Try to find matching payment
                $payment = $this->findMatchingPayment($transaction);
                
                if ($payment) {
                    $this->reconcileWithPayment($transaction, $payment);
                    DB::commit();
                    
                    Log::info('Auto-reconciled transaction with payment', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $payment->id,
                    ]);
                    
                    return true;
                }

                // If no payment found, try to find matching invoice and create payment
                $invoice = $this->findMatchingInvoice($transaction);
                
                if ($invoice) {
                    $payment = $this->createPaymentFromTransaction($transaction, [
                        'invoice_id' => $invoice->id,
                        'client_id' => $invoice->client_id,
                    ]);
                    
                    DB::commit();
                    
                    Log::info('Auto-reconciled transaction by creating payment', [
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $invoice->id,
                        'payment_id' => $payment->id,
                    ]);
                    
                    return true;
                }
            } else {
                // Try to find matching expense
                $expense = $this->findMatchingExpense($transaction);
                
                if ($expense) {
                    $this->reconcileWithExpense($transaction, $expense);
                    DB::commit();
                    
                    Log::info('Auto-reconciled transaction with expense', [
                        'transaction_id' => $transaction->id,
                        'expense_id' => $expense->id,
                    ]);
                    
                    return true;
                }
            }

            DB::commit();
            return false;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Auto-reconciliation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Bulk auto-reconcile transactions for an account.
     */
    public function bulkAutoReconcile(Account $account): array
    {
        $transactions = BankTransaction::where('account_id', $account->id)
            ->unreconciled()
            ->where('pending', false)
            ->get();

        $results = [
            'total' => $transactions->count(),
            'reconciled' => 0,
            'failed' => 0,
        ];

        foreach ($transactions as $transaction) {
            if ($this->autoReconcileTransaction($transaction)) {
                $results['reconciled']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info('Bulk auto-reconciliation completed', [
            'account_id' => $account->id,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Manually reconcile transaction with payment.
     */
    public function reconcileWithPayment(BankTransaction $transaction, Payment $payment): bool
    {
        try {
            DB::beginTransaction();

            $transaction->reconcileWithPayment($payment);
            
            $payment->update([
                'bank_transaction_id' => $transaction->id,
            ]);

            DB::commit();

            Log::info('Transaction reconciled with payment', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'user_id' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reconcile transaction with payment', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Manually reconcile transaction with expense.
     */
    public function reconcileWithExpense(BankTransaction $transaction, Expense $expense): bool
    {
        try {
            DB::beginTransaction();

            $transaction->reconcileWithExpense($expense);
            
            $expense->update([
                'bank_transaction_id' => $transaction->id,
                'plaid_transaction_id' => $transaction->plaid_transaction_id,
            ]);

            DB::commit();

            Log::info('Transaction reconciled with expense', [
                'transaction_id' => $transaction->id,
                'expense_id' => $expense->id,
                'user_id' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reconcile transaction with expense', [
                'transaction_id' => $transaction->id,
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Create payment from bank transaction.
     */
    public function createPaymentFromTransaction(BankTransaction $transaction, array $additionalData = []): Payment
    {
        try {
            DB::beginTransaction();

            $payment = $this->paymentService->createPayment(array_merge([
                'company_id' => $transaction->company_id,
                'amount' => abs($transaction->amount),
                'payment_date' => $transaction->date,
                'payment_method' => 'Bank Transfer',
                'payment_reference' => $transaction->plaid_transaction_id,
                'notes' => "Auto-created from bank transaction: {$transaction->getDisplayName()}",
                'status' => 'completed',
                'gateway' => 'plaid',
                'currency' => $transaction->account->currency_code ?? 'USD',
                'bank_transaction_id' => $transaction->id,
            ], $additionalData));

            $transaction->reconcileWithPayment($payment);

            DB::commit();

            Log::info('Payment created from bank transaction', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
            ]);

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create payment from transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Create expense from bank transaction.
     */
    public function createExpenseFromTransaction(BankTransaction $transaction, array $additionalData = []): Expense
    {
        try {
            DB::beginTransaction();

            $expense = Expense::create(array_merge([
                'company_id' => $transaction->company_id,
                'amount' => abs($transaction->amount),
                'expense_date' => $transaction->date,
                'title' => $transaction->getDisplayName(),
                'description' => "Auto-created from bank transaction",
                'vendor' => $transaction->merchant_name ?? $transaction->name,
                'payment_method' => 'bank_transfer',
                'reference_number' => $transaction->plaid_transaction_id,
                'status' => 'approved',
                'currency' => $transaction->account->currency_code ?? 'USD',
                'bank_transaction_id' => $transaction->id,
                'plaid_transaction_id' => $transaction->plaid_transaction_id,
                'submitted_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'category_id' => $transaction->category_id,
            ], $additionalData));

            $transaction->reconcileWithExpense($expense);

            DB::commit();

            Log::info('Expense created from bank transaction', [
                'transaction_id' => $transaction->id,
                'expense_id' => $expense->id,
            ]);

            return $expense;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create expense from transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Unreconcile a transaction.
     */
    public function unreconcile(BankTransaction $transaction): bool
    {
        try {
            DB::beginTransaction();

            // Update related payment/expense
            if ($transaction->reconciled_payment_id) {
                Payment::find($transaction->reconciled_payment_id)?->update([
                    'bank_transaction_id' => null,
                ]);
            }

            if ($transaction->reconciled_expense_id) {
                Expense::find($transaction->reconciled_expense_id)?->update([
                    'bank_transaction_id' => null,
                    'plaid_transaction_id' => null,
                ]);
            }

            $transaction->unreconcile();

            DB::commit();

            Log::info('Transaction unreconciled', [
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to unreconcile transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get reconciliation summary for an account.
     */
    public function getReconciliationSummary(Account $account, Carbon $asOfDate): array
    {
        $bankTransactions = BankTransaction::where('account_id', $account->id)
            ->where('date', '<=', $asOfDate)
            ->where('pending', false)
            ->get();

        $reconciled = $bankTransactions->where('is_reconciled', true);
        $unreconciled = $bankTransactions->where('is_reconciled', false)->where('is_ignored', false);

        $bankBalance = $account->current_balance ?? 0;
        $bookBalance = $this->calculateBookBalance($account, $asOfDate);
        $difference = $bankBalance - $bookBalance;

        return [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'bank_balance' => $bankBalance,
            'book_balance' => $bookBalance,
            'difference' => $difference,
            'total_transactions' => $bankTransactions->count(),
            'reconciled_count' => $reconciled->count(),
            'unreconciled_count' => $unreconciled->count(),
            'unreconciled_amount' => $unreconciled->sum('amount'),
            'income_unreconciled' => $unreconciled->where('amount', '>', 0)->sum('amount'),
            'expense_unreconciled' => abs($unreconciled->where('amount', '<', 0)->sum('amount')),
        ];
    }

    /**
     * Get unreconciled transactions for an account.
     */
    public function getUnreconciledTransactions(Account $account): Collection
    {
        return BankTransaction::where('account_id', $account->id)
            ->unreconciled()
            ->where('pending', false)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get reconciliation difference.
     */
    public function getReconciliationDifference(Account $account): float
    {
        $bankBalance = $account->current_balance ?? 0;
        $bookBalance = $this->calculateBookBalance($account);
        
        return $bankBalance - $bookBalance;
    }

    /**
     * Calculate book balance from payments and expenses.
     */
    protected function calculateBookBalance(Account $account, ?Carbon $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?? now();
        
        $openingBalance = $account->opening_balance ?? 0;
        
        $payments = Payment::where('company_id', $account->company_id)
            ->where('status', 'completed')
            ->where('payment_date', '<=', $asOfDate)
            ->sum('amount');

        $expenses = Expense::where('company_id', $account->company_id)
            ->whereIn('status', ['approved', 'paid'])
            ->where('expense_date', '<=', $asOfDate)
            ->sum('amount');

        return $openingBalance + $payments - $expenses;
    }

    /**
     * Get suggested matches for a transaction.
     */
    public function getSuggestedMatches(BankTransaction $transaction, int $limit = 5): array
    {
        $suggestions = [];

        if ($transaction->isIncome()) {
            // Find potential payments
            $payment = $this->findMatchingPayment($transaction);
            if ($payment) {
                $suggestions[] = [
                    'type' => 'payment',
                    'model' => $payment,
                    'confidence' => $this->calculateMatchConfidence($transaction, $payment),
                ];
            }

            // Find potential invoices
            $invoice = $this->findMatchingInvoice($transaction);
            if ($invoice) {
                $suggestions[] = [
                    'type' => 'invoice',
                    'model' => $invoice,
                    'confidence' => $this->calculateMatchConfidence($transaction, $invoice),
                ];
            }
        } else {
            // Find potential expenses
            $expense = $this->findMatchingExpense($transaction);
            if ($expense) {
                $suggestions[] = [
                    'type' => 'expense',
                    'model' => $expense,
                    'confidence' => $this->calculateMatchConfidence($transaction, $expense),
                ];
            }
        }

        // Sort by confidence
        usort($suggestions, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Calculate match confidence score (0-100).
     */
    protected function calculateMatchConfidence(BankTransaction $transaction, $model): int
    {
        $score = 0;

        // Amount matching (50 points max)
        $amountDiff = abs(abs($transaction->amount) - ($model->amount ?? $model->total ?? 0));
        if ($amountDiff < 0.01) {
            $score += 50;
        } elseif ($amountDiff < 1) {
            $score += 40;
        } elseif ($amountDiff < 10) {
            $score += 30;
        } elseif ($amountDiff < 100) {
            $score += 20;
        }

        // Date matching (30 points max)
        $dateField = $model instanceof Payment ? 'payment_date' : ($model instanceof Expense ? 'expense_date' : 'created_at');
        $dateDiff = abs(Carbon::parse($transaction->date)->diffInDays($model->$dateField));
        if ($dateDiff === 0) {
            $score += 30;
        } elseif ($dateDiff === 1) {
            $score += 25;
        } elseif ($dateDiff <= 3) {
            $score += 20;
        } elseif ($dateDiff <= 7) {
            $score += 10;
        }

        // Description matching (20 points max)
        if ($model instanceof Payment || $model instanceof Expense) {
            $transactionName = strtolower($transaction->getDisplayName());
            $modelDesc = strtolower($model->notes ?? $model->description ?? '');
            
            if (str_contains($modelDesc, $transactionName) || str_contains($transactionName, $modelDesc)) {
                $score += 20;
            }
        }

        return min(100, $score);
    }
}
