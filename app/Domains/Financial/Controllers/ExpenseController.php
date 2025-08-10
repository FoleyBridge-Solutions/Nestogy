<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Financial\Models\Expense;
use App\Domains\Financial\Models\ExpenseCategory;
use App\Models\Client;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses
     */
    public function index(Request $request)
    {
        $query = Expense::with(['client', 'project', 'submittedBy', 'approvedBy', 'category'])
            ->where('company_id', auth()->user()->company_id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('submittedBy', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            if ($status === 'pending') {
                $query->whereIn('status', [Expense::STATUS_SUBMITTED, Expense::STATUS_PENDING_APPROVAL]);
            } else {
                $query->where('status', $status);
            }
        }

        // Apply category filter
        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Apply user filter (for managers to see specific user's expenses)
        if ($userId = $request->get('user_id')) {
            $query->where('submitted_by', $userId);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply billable filter
        if ($billable = $request->get('billable')) {
            $query->where('is_billable', $billable === 'yes');
        }

        // Apply date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        // Apply amount range filter
        if ($amountFrom = $request->get('amount_from')) {
            $query->where('amount', '>=', $amountFrom);
        }
        if ($amountTo = $request->get('amount_to')) {
            $query->where('amount', '<=', $amountTo);
        }

        // Show only user's own expenses unless they're a manager
        if (!auth()->user()->hasRole('manager') && !auth()->user()->hasRole('admin')) {
            $query->where('submitted_by', Auth::id());
        }

        $expenses = $query->orderBy('expense_date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(20)
                         ->appends($request->query());

        // Get filter options
        $categories = ExpenseCategory::where('company_id', auth()->user()->company_id)
                                   ->active()
                                   ->ordered()
                                   ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $users = User::where('company_id', auth()->user()->company_id)
                    ->orderBy('name')
                    ->get();

        $statuses = Expense::getStatuses();

        // Get summary statistics
        $stats = $this->getExpenseStats();

        return view('financial.expenses.index', compact(
            'expenses', 
            'categories', 
            'clients', 
            'users',
            'statuses',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new expense
     */
    public function create(Request $request)
    {
        $categories = ExpenseCategory::where('company_id', auth()->user()->company_id)
                                   ->active()
                                   ->ordered()
                                   ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $projects = Project::where('company_id', auth()->user()->company_id)
                          ->where('status', 'active')
                          ->orderBy('name')
                          ->get();

        $selectedClientId = $request->get('client_id');
        $selectedProjectId = $request->get('project_id');

        $paymentMethods = Expense::getPaymentMethods();

        return view('financial.expenses.create', compact(
            'categories',
            'clients', 
            'projects', 
            'selectedClientId', 
            'selectedProjectId',
            'paymentMethods'
        ));
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:expense_categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|string|max:3',
            'expense_date' => 'required|date|before_or_equal:today',
            'vendor' => 'nullable|string|max:255',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Expense::getPaymentMethods())),
            'reference_number' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
            'business_purpose' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'attendees' => 'nullable|array',
            'attendees.*' => 'string|max:255',
            'mileage' => 'nullable|numeric|min:0|max:9999.99',
            'mileage_rate' => 'nullable|numeric|min:0|max:10',
            'is_billable' => 'boolean',
            'markup_percentage' => 'nullable|numeric|min:0|max:100',
            'markup_amount' => 'nullable|numeric|min:0|max:99999.99',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'submit_for_approval' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            $expense = new Expense($request->except(['receipt', 'attendees', 'tags']));
            $expense->company_id = auth()->user()->company_id;
            $expense->submitted_by = Auth::id();
            $expense->currency = $expense->currency ?? 'USD';
            
            // Handle attendees
            if ($request->has('attendees')) {
                $expense->attendees = array_filter($request->attendees);
            }

            // Handle tags
            if ($request->has('tags')) {
                $expense->tags = array_filter($request->tags);
            }

            // Set status based on submission choice
            if ($request->boolean('submit_for_approval')) {
                // Check if category requires approval or amount exceeds limit
                $category = ExpenseCategory::find($expense->category_id);
                if ($category && ($category->requiresApprovalForAmount($expense->amount) || $category->requires_approval)) {
                    $expense->status = Expense::STATUS_PENDING_APPROVAL;
                } else {
                    $expense->status = Expense::STATUS_APPROVED; // Auto-approve if under limit
                }
            } else {
                $expense->status = Expense::STATUS_DRAFT;
            }

            // Calculate billable amount if needed
            if ($expense->is_billable) {
                $expense->total_billable_amount = $expense->calculateBillableAmount();
            }

            // Handle receipt upload
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store(
                    'expenses/' . auth()->user()->company_id,
                    'private'
                );
                $expense->receipt_path = $receiptPath;
            }

            $expense->save();

            DB::commit();

            $message = $expense->status === Expense::STATUS_DRAFT 
                ? 'Expense saved as draft successfully.' 
                : 'Expense submitted for approval successfully.';

            return redirect()->route('financial.expenses.index')
                           ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Failed to create expense: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified expense
     */
    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);

        $expense->load([
            'client', 
            'project', 
            'submittedBy', 
            'approvedBy', 
            'rejectedBy', 
            'category'
        ]);

        return view('financial.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit(Expense $expense)
    {
        $this->authorize('update', $expense);

        // Only allow editing of draft or rejected expenses
        if (!in_array($expense->status, [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED])) {
            return redirect()->route('financial.expenses.show', $expense)
                           ->with('error', 'This expense cannot be edited in its current status.');
        }

        $categories = ExpenseCategory::where('company_id', auth()->user()->company_id)
                                   ->active()
                                   ->ordered()
                                   ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $projects = Project::where('company_id', auth()->user()->company_id)
                          ->where('status', 'active')
                          ->orderBy('name')
                          ->get();

        $paymentMethods = Expense::getPaymentMethods();

        return view('financial.expenses.edit', compact(
            'expense',
            'categories',
            'clients', 
            'projects',
            'paymentMethods'
        ));
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        // Only allow updating of draft or rejected expenses
        if (!in_array($expense->status, [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED])) {
            return redirect()->route('financial.expenses.show', $expense)
                           ->with('error', 'This expense cannot be updated in its current status.');
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:expense_categories,id',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|string|max:3',
            'expense_date' => 'required|date|before_or_equal:today',
            'vendor' => 'nullable|string|max:255',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(Expense::getPaymentMethods())),
            'reference_number' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'business_purpose' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'attendees' => 'nullable|array',
            'attendees.*' => 'string|max:255',
            'mileage' => 'nullable|numeric|min:0|max:9999.99',
            'mileage_rate' => 'nullable|numeric|min:0|max:10',
            'is_billable' => 'boolean',
            'markup_percentage' => 'nullable|numeric|min:0|max:100',
            'markup_amount' => 'nullable|numeric|min:0|max:99999.99',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'submit_for_approval' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            DB::beginTransaction();

            $expense->fill($request->except(['receipt', 'attendees', 'tags']));
            
            // Handle attendees
            if ($request->has('attendees')) {
                $expense->attendees = array_filter($request->attendees);
            }

            // Handle tags
            if ($request->has('tags')) {
                $expense->tags = array_filter($request->tags);
            }

            // Update status based on submission choice
            if ($request->boolean('submit_for_approval') && $expense->status === Expense::STATUS_DRAFT) {
                $category = ExpenseCategory::find($expense->category_id);
                if ($category && ($category->requiresApprovalForAmount($expense->amount) || $category->requires_approval)) {
                    $expense->status = Expense::STATUS_PENDING_APPROVAL;
                } else {
                    $expense->status = Expense::STATUS_APPROVED;
                }
            } elseif ($request->boolean('submit_for_approval') && $expense->status === Expense::STATUS_REJECTED) {
                $expense->status = Expense::STATUS_PENDING_APPROVAL;
                $expense->rejection_reason = null;
                $expense->rejected_by = null;
            }

            // Calculate billable amount if needed
            if ($expense->is_billable) {
                $expense->total_billable_amount = $expense->calculateBillableAmount();
            }

            // Handle receipt upload
            if ($request->hasFile('receipt')) {
                // Delete old receipt
                if ($expense->receipt_path) {
                    Storage::disk('private')->delete($expense->receipt_path);
                }
                
                $receiptPath = $request->file('receipt')->store(
                    'expenses/' . auth()->user()->company_id,
                    'private'
                );
                $expense->receipt_path = $receiptPath;
            }

            $expense->save();

            DB::commit();

            return redirect()->route('financial.expenses.index')
                           ->with('success', 'Expense updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Failed to update expense: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        // Only allow deletion of draft or rejected expenses
        if (!in_array($expense->status, [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED])) {
            return redirect()->back()
                           ->with('error', 'This expense cannot be deleted in its current status.');
        }

        try {
            DB::beginTransaction();

            // Delete receipt file
            if ($expense->receipt_path) {
                Storage::disk('private')->delete($expense->receipt_path);
            }

            $expense->delete();

            DB::commit();

            return redirect()->route('financial.expenses.index')
                           ->with('success', 'Expense deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }

    /**
     * Approve expense (for managers/admin)
     */
    public function approve(Request $request, Expense $expense)
    {
        $this->authorize('approve', $expense);

        if (!$expense->canBeApproved()) {
            return redirect()->back()
                           ->with('error', 'This expense cannot be approved.');
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $success = $expense->approve(auth()->user(), $request->approval_notes);
            
            if ($success) {
                return redirect()->back()
                               ->with('success', 'Expense approved successfully.');
            } else {
                return redirect()->back()
                               ->with('error', 'Failed to approve expense.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to approve expense: ' . $e->getMessage());
        }
    }

    /**
     * Reject expense (for managers/admin)
     */
    public function reject(Request $request, Expense $expense)
    {
        $this->authorize('reject', $expense);

        if (!$expense->canBeRejected()) {
            return redirect()->back()
                           ->with('error', 'This expense cannot be rejected.');
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $success = $expense->reject(auth()->user(), $request->rejection_reason);
            
            if ($success) {
                return redirect()->back()
                               ->with('success', 'Expense rejected successfully.');
            } else {
                return redirect()->back()
                               ->with('error', 'Failed to reject expense.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to reject expense: ' . $e->getMessage());
        }
    }

    /**
     * Submit expense for approval
     */
    public function submit(Expense $expense)
    {
        $this->authorize('update', $expense);

        if ($expense->status !== Expense::STATUS_DRAFT) {
            return redirect()->back()
                           ->with('error', 'Only draft expenses can be submitted for approval.');
        }

        try {
            $success = $expense->submit();
            
            if ($success) {
                return redirect()->back()
                               ->with('success', 'Expense submitted for approval successfully.');
            } else {
                return redirect()->back()
                               ->with('error', 'Failed to submit expense.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to submit expense: ' . $e->getMessage());
        }
    }

    /**
     * Download receipt file
     */
    public function downloadReceipt(Expense $expense)
    {
        $this->authorize('view', $expense);

        if (!$expense->receipt_path || !Storage::disk('private')->exists($expense->receipt_path)) {
            return redirect()->back()
                           ->with('error', 'Receipt file not found.');
        }

        $originalName = 'receipt_' . $expense->id . '_' . pathinfo($expense->receipt_path, PATHINFO_EXTENSION);
        
        return Storage::disk('private')->download($expense->receipt_path, $originalName);
    }

    /**
     * Get expense statistics for dashboard
     */
    private function getExpenseStats(): array
    {
        $companyId = auth()->user()->company_id;
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'total_expenses' => Expense::where('company_id', $companyId)->count(),
            'total_amount' => Expense::where('company_id', $companyId)
                                   ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID])
                                   ->sum('amount'),
            'this_month_amount' => Expense::where('company_id', $companyId)
                                         ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID])
                                         ->where('expense_date', '>=', $currentMonth)
                                         ->sum('amount'),
            'last_month_amount' => Expense::where('company_id', $companyId)
                                         ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_PAID])
                                         ->whereBetween('expense_date', [$lastMonth, $currentMonth])
                                         ->sum('amount'),
            'pending_approval_count' => Expense::where('company_id', $companyId)
                                              ->where('status', Expense::STATUS_PENDING_APPROVAL)
                                              ->count(),
            'rejected_count' => Expense::where('company_id', $companyId)
                                      ->where('status', Expense::STATUS_REJECTED)
                                      ->count(),
            'billable_amount' => Expense::where('company_id', $companyId)
                                       ->where('is_billable', true)
                                       ->whereNull('invoiced_at')
                                       ->where('status', Expense::STATUS_APPROVED)
                                       ->sum('total_billable_amount'),
        ];
    }
}