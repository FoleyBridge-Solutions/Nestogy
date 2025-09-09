<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $expenses = Expense::where('company_id', $companyId)
            ->when($request->get('category_id'), function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('client_id'), function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('vendor', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->with(['category', 'user', 'client'])
            ->orderBy('expense_date', 'desc')
            ->paginate(20);

        $categories = ExpenseCategory::where('company_id', $companyId)->get();
        
        $clients = Client::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
            
        $statuses = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'paid' => 'Paid'
        ];
        
        // Calculate statistics
        $stats = [
            'total_amount' => Expense::where('company_id', $companyId)->sum('amount'),
            'pending_approval_count' => Expense::where('company_id', $companyId)->where('status', 'pending_approval')->count(),
            'this_month_amount' => Expense::where('company_id', $companyId)
                ->whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('amount'),
            'billable_amount' => Expense::where('company_id', $companyId)->where('is_billable', true)->sum('amount')
        ];

        return view('financial.expenses.index', compact('expenses', 'categories', 'clients', 'statuses', 'stats'));
    }

    public function create()
    {
        $categories = ExpenseCategory::where('company_id', Auth::user()->company_id)->get();
        
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
            
        $paymentMethods = [
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'check' => 'Check',
            'bank_transfer' => 'Bank Transfer',
            'online_payment' => 'Online Payment',
            'other' => 'Other'
        ];

        return view('financial.expenses.create', compact('categories', 'clients', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'vendor' => 'required|string|max:255',
            'description' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string',
            'is_billable' => 'boolean',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string'
        ]);

        $validated['company_id'] = Auth::user()->company_id;
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pending_approval';
        
        $expense = Expense::create($validated);

        return redirect()->route('financial.expenses.show', $expense)
            ->with('success', 'Expense created successfully.');
    }

    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);
        
        $expense->load(['category', 'user', 'client', 'project']);

        return view('financial.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $this->authorize('update', $expense);
        
        $categories = ExpenseCategory::where('company_id', Auth::user()->company_id)->get();
        
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
            
        $paymentMethods = [
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'check' => 'Check',
            'bank_transfer' => 'Bank Transfer',
            'online_payment' => 'Online Payment',
            'other' => 'Other'
        ];

        return view('financial.expenses.edit', compact('expense', 'categories', 'clients', 'paymentMethods'));
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'vendor' => 'required|string|max:255',
            'description' => 'required|string',
            'reference_number' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string',
            'is_billable' => 'boolean',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string'
        ]);

        $expense->update($validated);

        return redirect()->route('financial.expenses.show', $expense)
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return redirect()->route('financial.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }

    public function approve(Expense $expense)
    {
        $this->authorize('approve', $expense);

        $expense->status = 'approved';
        $expense->approved_by = Auth::id();
        $expense->approved_at = now();
        $expense->save();

        return redirect()->back()
            ->with('success', 'Expense approved successfully.');
    }

    public function reject(Expense $expense)
    {
        $this->authorize('approve', $expense);

        $expense->status = 'rejected';
        $expense->approved_by = Auth::id();
        $expense->approved_at = now();
        $expense->save();

        return redirect()->back()
            ->with('success', 'Expense rejected.');
    }
}