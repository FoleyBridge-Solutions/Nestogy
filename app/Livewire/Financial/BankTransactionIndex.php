<?php

namespace App\Livewire\Financial;

use App\Domains\Company\Models\Account;
use App\Domains\Financial\Models\BankTransaction;
use App\Domains\Financial\Models\Expense;
use App\Domains\Financial\Models\Payment;
use App\Domains\Financial\Services\BankReconciliationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class BankTransactionIndex extends Component
{
    use WithPagination;

    public $accountId = '';
    public $status = '';
    public $type = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $search = '';
    
    public $selectedTransactions = [];
    public $selectAll = false;
    
    public $showReconcileModal = false;
    public $transactionToReconcile = null;
    public $reconcileType = 'payment';
    public $reconcileId = null;
    public $suggestedMatches = [];
    
    public $showCreateModal = false;
    public $createType = 'payment';
    public $createData = [];

    protected $queryString = ['accountId', 'status', 'type', 'dateFrom', 'dateTo', 'search'];

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTransactions = $this->getTransactions()->pluck('id')->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }

    public function getTransactions()
    {
        $query = BankTransaction::where('company_id', Auth::user()->company_id)
            ->with(['account', 'reconciledPayment', 'reconciledExpense', 'categoryModel']);

        if ($this->accountId) {
            $query->where('account_id', $this->accountId);
        }

        if ($this->status === 'reconciled') {
            $query->where('is_reconciled', true);
        } elseif ($this->status === 'unreconciled') {
            $query->where('is_reconciled', false)->where('is_ignored', false);
        } elseif ($this->status === 'ignored') {
            $query->where('is_ignored', true);
        }

        if ($this->type === 'income') {
            $query->where('amount', '>', 0);
        } elseif ($this->type === 'expense') {
            $query->where('amount', '<', 0);
        }

        if ($this->dateFrom) {
            $query->where('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('date', '<=', $this->dateTo);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('merchant_name', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('date', 'desc');
    }

    public function openReconcileModal($transactionId)
    {
        $this->transactionToReconcile = BankTransaction::with(['account'])->findOrFail($transactionId);
        
        $reconciliationService = app(BankReconciliationService::class);
        $this->suggestedMatches = $reconciliationService->getSuggestedMatches($this->transactionToReconcile);
        
        $this->reconcileType = $this->transactionToReconcile->isIncome() ? 'payment' : 'expense';
        $this->reconcileId = null;
        $this->showReconcileModal = true;
    }

    public function reconcile()
    {
        $this->validate([
            'reconcileType' => 'required|in:payment,expense',
            'reconcileId' => 'required|integer',
        ]);

        try {
            $reconciliationService = app(BankReconciliationService::class);
            
            if ($this->reconcileType === 'payment') {
                $payment = Payment::findOrFail($this->reconcileId);
                $reconciliationService->reconcileWithPayment($this->transactionToReconcile, $payment);
            } else {
                $expense = Expense::findOrFail($this->reconcileId);
                $reconciliationService->reconcileWithExpense($this->transactionToReconcile, $expense);
            }

            session()->flash('success', 'Transaction reconciled successfully!');
            $this->showReconcileModal = false;
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reconcile: ' . $e->getMessage());
        }
    }

    public function bulkAutoReconcile()
    {
        if (empty($this->selectedTransactions)) {
            session()->flash('error', 'No transactions selected');
            return;
        }

        $reconciliationService = app(BankReconciliationService::class);
        $reconciled = 0;
        $failed = 0;

        foreach ($this->selectedTransactions as $transactionId) {
            $transaction = BankTransaction::find($transactionId);
            if ($transaction && $transaction->company_id === Auth::user()->company_id) {
                if ($reconciliationService->autoReconcileTransaction($transaction)) {
                    $reconciled++;
                } else {
                    $failed++;
                }
            }
        }

        session()->flash('success', "Reconciled {$reconciled} transactions. {$failed} could not be auto-matched.");
        $this->selectedTransactions = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function openCreateModal($transactionId, $type)
    {
        $this->transactionToReconcile = BankTransaction::findOrFail($transactionId);
        $this->createType = $type;
        $this->createData = [];
        $this->showCreateModal = true;
    }

    public function create()
    {
        try {
            $reconciliationService = app(BankReconciliationService::class);
            
            if ($this->createType === 'payment') {
                $reconciliationService->createPaymentFromTransaction(
                    $this->transactionToReconcile,
                    $this->createData
                );
                session()->flash('success', 'Payment created and reconciled!');
            } else {
                $reconciliationService->createExpenseFromTransaction(
                    $this->transactionToReconcile,
                    $this->createData
                );
                session()->flash('success', 'Expense created and reconciled!');
            }

            $this->showCreateModal = false;
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    public function ignoreTransaction($transactionId)
    {
        try {
            $transaction = BankTransaction::findOrFail($transactionId);
            
            if ($transaction->company_id !== Auth::user()->company_id) {
                throw new \Exception('Unauthorized');
            }

            $transaction->ignore();
            session()->flash('success', 'Transaction ignored');
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to ignore transaction');
        }
    }

    public function unignoreTransaction($transactionId)
    {
        try {
            $transaction = BankTransaction::findOrFail($transactionId);
            
            if ($transaction->company_id !== Auth::user()->company_id) {
                throw new \Exception('Unauthorized');
            }

            $transaction->unignore();
            session()->flash('success', 'Transaction restored');
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restore transaction');
        }
    }

    public function unreconcile($transactionId)
    {
        try {
            $transaction = BankTransaction::findOrFail($transactionId);
            
            if ($transaction->company_id !== Auth::user()->company_id) {
                throw new \Exception('Unauthorized');
            }

            $reconciliationService = app(BankReconciliationService::class);
            $reconciliationService->unreconcile($transaction);
            
            session()->flash('success', 'Transaction unreconciled');
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to unreconcile transaction');
        }
    }

    public function resetFilters()
    {
        $this->accountId = '';
        $this->status = '';
        $this->type = '';
        $this->search = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $transactions = $this->getTransactions()->paginate(50);
        
        $accounts = Account::where('company_id', Auth::user()->company_id)
            ->whereNotNull('plaid_account_id')
            ->get();

        $summary = [
            'total' => $this->getTransactions()->count(),
            'unreconciled' => $this->getTransactions()->where('is_reconciled', false)->where('is_ignored', false)->count(),
            'reconciled' => $this->getTransactions()->where('is_reconciled', true)->count(),
            'ignored' => $this->getTransactions()->where('is_ignored', true)->count(),
        ];

        return view('livewire.financial.bank-transaction-index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'summary' => $summary,
        ]);
    }
}
