<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingController extends Controller
{
    public function chartOfAccounts(Request $request): View
    {
        $accounts = \App\Models\Account::orderBy('type')->orderBy('name')->get();
        
        $accountTypes = [
            'assets' => ['Current Assets', 'Fixed Assets', 'Other Assets'],
            'liabilities' => ['Current Liabilities', 'Long-term Liabilities'],
            'equity' => ['Owner\'s Equity', 'Retained Earnings'],
            'revenue' => ['Operating Revenue', 'Other Revenue'],
            'expenses' => ['Operating Expenses', 'Cost of Goods Sold', 'Other Expenses']
        ];
        
        $accountBalances = $this->calculateAccountBalances();
        
        return view('financial.accounting.chart-of-accounts', compact(
            'accounts',
            'accountTypes',
            'accountBalances'
        ));
    }

    public function journalEntries(Request $request): View
    {
        $query = \App\Models\Payment::query();
        
        $filters = [
            'date_from' => $request->get('date_from', Carbon::now()->subMonth()),
            'date_to' => $request->get('date_to', Carbon::now()),
            'account_id' => $request->get('account_id'),
            'entry_type' => $request->get('entry_type')
        ];
        
        // Apply filters
        if ($filters['date_from']) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if ($filters['account_id']) {
            $query->where('account_id', $filters['account_id']);
        }
        
        $entries = $query->orderBy('date', 'desc')->get();
        
        $totalDebits = $entries->where('type', 'debit')->sum('amount');
        $totalCredits = $entries->where('type', 'credit')->sum('amount');
        
        return view('financial.accounting.journal-entries', compact(
            'entries',
            'filters',
            'totalDebits',
            'totalCredits'
        ));
    }

    public function reconciliation(Request $request): View
    {
        $bankAccounts = \App\Models\Account::whereNotNull('plaid_id')->get();
        $selectedAccount = $request->get('account_id');
        
        $transactions = collect();
        $unreconciledItems = collect();
        $bankBalance = 0;
        $bookBalance = 0;
        
        if ($selectedAccount) {
            $account = \App\Models\Account::find($selectedAccount);
            $transactions = \App\Models\Payment::where('account_id', $selectedAccount)
                ->whereNull('reconciled_at')
                ->get();
            $unreconciledItems = $transactions;
            
            $bankBalance = $account->opening_balance ?? 0;
            $bookBalance = \App\Models\Payment::where('account_id', $selectedAccount)
                ->whereNotNull('reconciled_at')
                ->sum('amount') + $account->opening_balance;
        }
        $difference = abs($bankBalance - $bookBalance);
        
        return view('financial.accounting.reconciliation', compact(
            'bankAccounts',
            'selectedAccount',
            'transactions',
            'unreconciledItems',
            'bankBalance',
            'bookBalance',
            'difference'
        ));
    }

    public function createJournalEntry(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:50',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255'
        ]);
        
        // Validate debits equal credits
        $totalDebits = collect($validated['lines'])->sum('debit');
        $totalCredits = collect($validated['lines'])->sum('credit');
        
        if ($totalDebits !== $totalCredits) {
            return redirect()->back()
                ->withErrors(['lines' => 'Total debits must equal total credits'])
                ->withInput();
        }
        
        DB::transaction(function () use ($validated) {
            // Create journal entry record
            $entryData = [
                'date' => $validated['entry_date'],
                'description' => $validated['description'],
                'reference' => $validated['reference_number'],
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Create payment records for each line
            foreach ($validated['lines'] as $line) {
                if ($line['debit'] > 0 || $line['credit'] > 0) {
                    \App\Models\Payment::create([
                        'date' => $validated['entry_date'],
                        'account_id' => $line['account_id'],
                        'amount' => $line['debit'] ?: -$line['credit'],
                        'type' => $line['debit'] ? 'debit' : 'credit',
                        'description' => $line['description'] ?? $validated['description'],
                        'reference_number' => $validated['reference_number']
                    ]);
                }
            }
        });
        
        return redirect()->route('financial.accounting.journal-entries')
            ->with('success', 'Journal entry created successfully');
    }

    public function reconcileTransaction(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'bank_transaction_id' => 'required|exists:bank_transactions,id',
            'notes' => 'nullable|string|max:255'
        ]);
        
        // Mark transaction as reconciled
        $payment = \App\Models\Payment::find($validated['transaction_id']);
        if ($payment) {
            $payment->update([
                'reconciled_at' => now(),
                'reconciliation_notes' => $validated['notes']
            ]);
        }
        
        return redirect()->back()->with('success', 'Transaction reconciled successfully');
    }

    public function exportTrialBalance(Request $request)
    {
        $date = $request->get('date', Carbon::now());
        
        // Generate trial balance data
        $accounts = \App\Models\Account::with(['payments' => function($query) use ($date) {
            $query->whereDate('date', '<=', $date);
        }])->get();
        
        $trialBalance = [];
        foreach ($accounts as $account) {
            $debits = $account->payments->where('type', 'debit')->sum('amount');
            $credits = abs($account->payments->where('type', 'credit')->sum('amount'));
            $balance = $account->opening_balance + $debits - $credits;
            
            $trialBalance[] = [
                'account' => $account->name,
                'debit' => $debits > $credits ? $balance : 0,
                'credit' => $credits > $debits ? abs($balance) : 0
            ];
        }
        
        // For now, return JSON. In production, generate PDF
        return response()->json([
            'date' => $date->format('Y-m-d'),
            'trial_balance' => $trialBalance,
            'total_debits' => collect($trialBalance)->sum('debit'),
            'total_credits' => collect($trialBalance)->sum('credit')
        ]);
    }

    public function exportGeneralLedger(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfYear());
        $dateTo = $request->get('date_to', Carbon::now());
        
        // Generate general ledger data
        $accounts = \App\Models\Account::with(['payments' => function($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('date', [$dateFrom, $dateTo])
                  ->orderBy('date');
        }])->get();
        
        $ledger = [];
        foreach ($accounts as $account) {
            $runningBalance = $account->opening_balance;
            $entries = [];
            
            foreach ($account->payments as $payment) {
                $amount = $payment->type === 'debit' ? $payment->amount : -$payment->amount;
                $runningBalance += $amount;
                
                $entries[] = [
                    'date' => $payment->date,
                    'description' => $payment->description,
                    'debit' => $payment->type === 'debit' ? $payment->amount : null,
                    'credit' => $payment->type === 'credit' ? abs($payment->amount) : null,
                    'balance' => $runningBalance
                ];
            }
            
            if (count($entries) > 0) {
                $ledger[$account->name] = $entries;
            }
        }
        
        // For now, return JSON. In production, generate PDF
        return response()->json([
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d')
            ],
            'ledger' => $ledger
        ]);
    }

    private function calculateAccountBalances(): array
    {
        $accounts = \App\Models\Account::with('payments')->get();
        $balances = [];
        
        foreach ($accounts as $account) {
            $debits = $account->payments->where('type', 'debit')->sum('amount');
            $credits = abs($account->payments->where('type', 'credit')->sum('amount'));
            $balances[$account->id] = $account->opening_balance + $debits - $credits;
        }
        
        return $balances;
    }
}