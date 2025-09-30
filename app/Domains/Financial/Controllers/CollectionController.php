<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Financial\Models\Invoice;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function overdue(Request $request): View
    {
        $overdueInvoices = Invoice::where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'paid')
            ->with(['client', 'payments'])
            ->orderBy('due_date', 'asc')
            ->paginate(20);

        $totalOverdue = $overdueInvoices->sum('balance_due');
        $avgDaysOverdue = $overdueInvoices->avg(function ($invoice) {
            return Carbon::now()->diffInDays($invoice->due_date);
        });

        return view('financial.collections.overdue', compact(
            'overdueInvoices',
            'totalOverdue',
            'avgDaysOverdue'
        ));
    }

    public function disputes(Request $request): View
    {
        $disputes = Invoice::where('status', 'disputed')
            ->with(['client', 'dispute_notes'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $totalDisputed = $disputes->sum('total');

        // Calculate average resolution time from resolved disputes
        $avgResolutionTime = \App\Models\Invoice::where('company_id', $companyId)
            ->where('status', 'disputed')
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(DATEDIFF(resolved_at, disputed_at)) as avg_days')
            ->value('avg_days') ?? 14; // Default to 14 days if no data

        return view('financial.collections.disputes', compact(
            'disputes',
            'totalDisputed',
            'avgResolutionTime'
        ));
    }

    public function reminders(Request $request): View
    {
        $scheduledReminders = collect(); // TODO: Load from reminder schedule table
        $reminderTemplates = collect(); // TODO: Load email templates

        $recentReminders = collect(); // TODO: Load recent sent reminders

        return view('financial.collections.reminders', compact(
            'scheduledReminders',
            'reminderTemplates',
            'recentReminders'
        ));
    }

    public function sendReminder(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'template_id' => 'nullable|exists:reminder_templates,id',
            'custom_message' => 'nullable|string|max:1000',
            'cc_emails' => 'nullable|string',
        ]);

        // TODO: Send reminder email

        return redirect()->back()->with('success', 'Reminder sent successfully');
    }

    public function markDisputed(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'dispute_reason' => 'required|string|max:500',
            'dispute_amount' => 'nullable|numeric|min:0',
        ]);

        $invoice->update([
            'status' => 'disputed',
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_amount' => $validated['dispute_amount'] ?? $invoice->total,
        ]);

        return redirect()->back()->with('success', 'Invoice marked as disputed');
    }

    public function resolveDispute(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'resolution_type' => 'required|in:paid,waived,adjusted,cancelled',
            'resolution_notes' => 'required|string|max:500',
            'adjusted_amount' => 'nullable|numeric|min:0',
        ]);

        // TODO: Handle dispute resolution logic

        return redirect()->back()->with('success', 'Dispute resolved successfully');
    }
}
