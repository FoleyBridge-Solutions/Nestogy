<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Financial\Models\BankTransaction;
use App\Domains\Financial\Models\Expense;
use App\Domains\Financial\Models\Payment;
use App\Domains\Financial\Services\BankReconciliationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BankTransactionController extends Controller
{
    public function __construct(
        protected BankReconciliationService $reconciliationService
    ) {}

    /**
     * Display list of bank transactions.
     */
    public function index(Request $request): View
    {
        $query = BankTransaction::where('company_id', Auth::user()->company_id)
            ->with(['account', 'reconciledPayment', 'reconciledExpense']);

        // Apply filters
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'reconciled') {
                $query->where('is_reconciled', true);
            } elseif ($request->status === 'unreconciled') {
                $query->unreconciled();
            }
        }

        if ($request->filled('type')) {
            if ($request->type === 'income') {
                $query->income();
            } elseif ($request->type === 'expense') {
                $query->expense();
            }
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('date', 'desc')
            ->paginate(50);

        $accounts = Auth::user()->company->accounts()
            ->whereNotNull('plaid_account_id')
            ->get();

        return view('financial.bank-transactions.index', compact('transactions', 'accounts'));
    }

    /**
     * Display transaction details.
     */
    public function show(BankTransaction $transaction): View
    {
        $this->authorize('view', $transaction);

        $transaction->load(['account', 'reconciledPayment', 'reconciledExpense', 'reconciledByUser']);

        $suggestedMatches = $this->reconciliationService->getSuggestedMatches($transaction);

        return view('financial.bank-transactions.show', compact('transaction', 'suggestedMatches'));
    }

    /**
     * Reconcile a transaction.
     */
    public function reconcile(Request $request, BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $request->validate([
            'type' => 'required|in:payment,expense',
            'id' => 'required_if:type,payment,expense|integer',
        ]);

        try {
            $success = match($request->type) {
                'payment' => $this->reconcileWithPayment($transaction, $request->id),
                'expense' => $this->reconcileWithExpense($transaction, $request->id),
            };

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction reconciled successfully',
                ]);
            }

            return response()->json([
                'error' => 'Failed to reconcile transaction',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Transaction reconciliation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to reconcile transaction',
            ], 500);
        }
    }

    /**
     * Bulk reconcile transactions.
     */
    public function bulkReconcile(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:bank_transactions,id',
        ]);

        $reconciled = 0;
        $failed = 0;

        foreach ($request->transaction_ids as $transactionId) {
            $transaction = BankTransaction::find($transactionId);
            
            if ($transaction && $transaction->company_id === Auth::user()->company_id) {
                if ($this->reconciliationService->autoReconcileTransaction($transaction)) {
                    $reconciled++;
                } else {
                    $failed++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'reconciled' => $reconciled,
            'failed' => $failed,
            'message' => "Reconciled {$reconciled} transactions, {$failed} failed",
        ]);
    }

    /**
     * Create payment from transaction.
     */
    public function createPayment(Request $request, BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        try {
            $payment = $this->reconciliationService->createPaymentFromTransaction(
                $transaction,
                $request->only(['client_id', 'invoice_id'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment from transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create payment',
            ], 500);
        }
    }

    /**
     * Create expense from transaction.
     */
    public function createExpense(Request $request, BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        try {
            $expense = $this->reconciliationService->createExpenseFromTransaction(
                $transaction,
                $request->only(['category_id', 'client_id', 'project_id'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'expense_id' => $expense->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create expense from transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create expense',
            ], 500);
        }
    }

    /**
     * Ignore a transaction.
     */
    public function ignore(BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $transaction->ignore();

        Log::info('Transaction ignored', [
            'transaction_id' => $transaction->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction ignored',
        ]);
    }

    /**
     * Unignore a transaction.
     */
    public function unignore(BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $transaction->unignore();

        return response()->json([
            'success' => true,
            'message' => 'Transaction unignored',
        ]);
    }

    /**
     * Categorize a transaction.
     */
    public function categorize(Request $request, BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $transaction->update([
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction categorized',
        ]);
    }

    /**
     * Unreconcile a transaction.
     */
    public function unreconcile(BankTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        try {
            $this->reconciliationService->unreconcile($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction unreconciled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to unreconcile transaction',
            ], 500);
        }
    }

    /**
     * Helper: Reconcile with payment.
     */
    protected function reconcileWithPayment(BankTransaction $transaction, int $paymentId): bool
    {
        $payment = Payment::find($paymentId);

        if (!$payment || $payment->company_id !== Auth::user()->company_id) {
            return false;
        }

        return $this->reconciliationService->reconcileWithPayment($transaction, $payment);
    }

    /**
     * Helper: Reconcile with expense.
     */
    protected function reconcileWithExpense(BankTransaction $transaction, int $expenseId): bool
    {
        $expense = Expense::find($expenseId);

        if (!$expense || $expense->company_id !== Auth::user()->company_id) {
            return false;
        }

        return $this->reconciliationService->reconcileWithExpense($transaction, $expense);
    }
}
