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
        $accounts = collect(); // TODO: Load from chart_of_accounts table
        
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
        $entries = collect(); // TODO: Load from journal_entries table
        
        $filters = [
            'date_from' => $request->get('date_from', Carbon::now()->subMonth()),
            'date_to' => $request->get('date_to', Carbon::now()),
            'account_id' => $request->get('account_id'),
            'entry_type' => $request->get('entry_type')
        ];
        
        $totalDebits = 0; // TODO: Calculate from entries
        $totalCredits = 0; // TODO: Calculate from entries
        
        return view('financial.accounting.journal-entries', compact(
            'entries',
            'filters',
            'totalDebits',
            'totalCredits'
        ));
    }

    public function reconciliation(Request $request): View
    {
        $bankAccounts = collect(); // TODO: Load bank accounts
        $selectedAccount = $request->get('account_id');
        
        $transactions = collect(); // TODO: Load transactions for reconciliation
        $unreconciledItems = collect(); // TODO: Load unreconciled items
        
        $bankBalance = 0; // TODO: Get from bank feed
        $bookBalance = 0; // TODO: Calculate from ledger
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
        
        // TODO: Create journal entry
        
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
        
        // TODO: Mark transactions as reconciled
        
        return redirect()->back()->with('success', 'Transaction reconciled successfully');
    }

    public function exportTrialBalance(Request $request)
    {
        $date = $request->get('date', Carbon::now());
        
        // TODO: Generate trial balance report
        
        return response()->download('trial-balance.pdf');
    }

    public function exportGeneralLedger(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfYear());
        $dateTo = $request->get('date_to', Carbon::now());
        
        // TODO: Generate general ledger report
        
        return response()->download('general-ledger.pdf');
    }

    private function calculateAccountBalances(): array
    {
        // TODO: Calculate current balances for all accounts
        return [];
    }
}