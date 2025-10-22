<?php

namespace App\Livewire\Dashboard\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class OverdueInvoices extends Component
{
    public array $data = [];

    public bool $loading = true;

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-overdueinvoices')]
    public function loadData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;

        $overdueInvoices = DB::table('invoices')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.due_date', '<', Carbon::today())
            ->whereNotIn('invoices.status', ['Paid', 'Cancelled'])
            ->join('clients', 'invoices.client_id', '=', 'clients.id')
            ->select('invoices.id', 'invoices.number', 'invoices.amount', 'invoices.due_date', 'clients.name as client_name')
            ->orderBy('invoices.due_date', 'asc')
            ->limit(10)
            ->get();

        $totalOverdue = DB::table('invoices')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.due_date', '<', Carbon::today())
            ->whereNotIn('invoices.status', ['Paid', 'Cancelled'])
            ->sum('invoices.amount');

        $overdueCount = DB::table('invoices')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.due_date', '<', Carbon::today())
            ->whereNotIn('invoices.status', ['Paid', 'Cancelled'])
            ->count();

        $this->data = [
            'items' => $overdueInvoices->map(function ($invoice) {
                $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(Carbon::today());
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'client_name' => $invoice->client_name,
                    'amount' => (float)$invoice->amount,
                    'due_date' => Carbon::parse($invoice->due_date)->format('M d, Y'),
                    'days_overdue' => $daysOverdue,
                    'last_contact' => null,
                    'payment_method' => null,
                ];
            })->toArray(),
            'stats' => [
                [
                    'label' => 'Total Overdue',
                    'value' => (float)$totalOverdue,
                    'type' => 'currency',
                    'icon' => 'exclamation-triangle',
                ],
                [
                    'label' => 'Overdue Count',
                    'value' => $overdueCount,
                    'type' => 'number',
                    'icon' => 'document-text',
                ],
            ],
        ];

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.overdue-invoices');
    }
}
