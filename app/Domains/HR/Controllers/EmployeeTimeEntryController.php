<?php

namespace App\Domains\HR\Controllers;

use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use App\Domains\HR\Services\PayrollTimeCalculationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeTimeEntryController extends Controller
{
    protected PayrollTimeCalculationService $payrollService;

    public function __construct(PayrollTimeCalculationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Use Livewire component for time entry management',
            ]);
        }

        return view('hr.time-entries.index');
    }

    public function show(EmployeeTimeEntry $entry)
    {
        $this->authorize('view', $entry);

        $entry->load(['user', 'shift', 'payPeriod', 'approvedBy', 'rejectedBy']);

        return view('hr.time-entries.show', compact('entry'));
    }

    public function edit(EmployeeTimeEntry $entry)
    {
        $this->authorize('update', $entry);

        if ($entry->exported_to_payroll) {
            return redirect()->back()->with('error', 'Cannot edit exported time entries');
        }

        $entry->load(['user', 'shift']);

        return view('hr.time-entries.edit', compact('entry'));
    }

    public function update(Request $request, EmployeeTimeEntry $entry)
    {
        $this->authorize('update', $entry);

        if ($entry->exported_to_payroll) {
            return redirect()->back()->with('error', 'Cannot edit exported time entries');
        }

        $validated = $request->validate([
            'clock_in' => 'required|date',
            'clock_out' => 'required|date|after:clock_in',
            'break_minutes' => 'required|integer|min:0|max:480',
            'notes' => 'nullable|string|max:1000',
        ]);

        $entry->clock_in = $validated['clock_in'];
        $entry->clock_out = $validated['clock_out'];
        $entry->break_minutes = $validated['break_minutes'];
        $entry->notes = $validated['notes'] ?? $entry->notes;
        $entry->entry_type = EmployeeTimeEntry::TYPE_ADJUSTED;

        $totalMinutes = \Carbon\Carbon::parse($entry->clock_in)->diffInMinutes($entry->clock_out);
        $workMinutes = $totalMinutes - $entry->break_minutes;

        $entry->total_minutes = $workMinutes;
        $entry->regular_minutes = $workMinutes;
        $entry->overtime_minutes = 0;

        $entry->save();

        return redirect()->route('hr.time-entries.show', $entry)->with('success', 'Time entry updated successfully');
    }

    public function destroy(EmployeeTimeEntry $entry)
    {
        $this->authorize('delete', $entry);

        if ($entry->exported_to_payroll) {
            return redirect()->back()->with('error', 'Cannot delete exported time entries');
        }

        $entry->delete();

        return redirect()->route('hr.time-entries.index')->with('success', 'Time entry deleted successfully');
    }

    public function approve(Request $request, EmployeeTimeEntry $entry)
    {
        $this->authorize('approve', $entry);

        $entry->status = EmployeeTimeEntry::STATUS_APPROVED;
        $entry->approved_by = auth()->id();
        $entry->approved_at = now();
        $entry->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time entry approved',
                'entry' => $entry,
            ]);
        }

        return redirect()->back()->with('success', 'Time entry approved');
    }

    public function reject(Request $request, EmployeeTimeEntry $entry)
    {
        $this->authorize('approve', $entry);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $entry->status = EmployeeTimeEntry::STATUS_REJECTED;
        $entry->rejected_by = auth()->id();
        $entry->rejected_at = now();
        $entry->rejection_reason = $request->input('rejection_reason');
        $entry->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time entry rejected',
                'entry' => $entry,
            ]);
        }

        return redirect()->back()->with('success', 'Time entry rejected');
    }

    public function bulkApprove(Request $request)
    {
        $this->authorize('manage-hr');

        $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:employee_time_entries,id',
        ]);

        $entries = EmployeeTimeEntry::whereIn('id', $request->input('entry_ids'))
            ->where('company_id', auth()->user()->company_id)
            ->get();

        foreach ($entries as $entry) {
            $entry->status = EmployeeTimeEntry::STATUS_APPROVED;
            $entry->approved_by = auth()->id();
            $entry->approved_at = now();
            $entry->save();
        }

        return redirect()->back()->with('success', count($entries) . ' time entries approved');
    }

    public function bulkExport(Request $request)
    {
        $this->authorize('manage-hr');

        $request->validate([
            'pay_period_id' => 'required|exists:pay_periods,id',
        ]);

        $payPeriod = PayPeriod::findOrFail($request->input('pay_period_id'));

        return redirect()->route('hr.payroll.export', $payPeriod);
    }
}
