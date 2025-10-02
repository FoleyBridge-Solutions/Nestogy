<?php

namespace App\Livewire\Billing;

use App\Domains\Financial\Services\TimeEntryInvoiceService;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Traits\HasFluxToasts;
use Livewire\WithPagination;

class TimeEntryApproval extends Component
{
    use HasFluxToasts;
    use WithPagination;

    public $selectedClient = null;
    public $selectedTechnician = null;
    public $startDate = '';
    public $endDate = '';
    public $billableOnly = true;
    public $selectedEntries = [];
    public $selectAll = false;
    public $groupBy = 'ticket';
    public $showPreview = false;
    public $previewData = null;

    protected $queryString = [
        'selectedClient' => ['except' => null],
        'selectedTechnician' => ['except' => null],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'billableOnly' => ['except' => true],
        'groupBy' => ['except' => 'ticket'],
    ];

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedEntries = $this->getTimeEntries()->pluck('id')->toArray();
        } else {
            $this->selectedEntries = [];
        }
    }

    public function updatingSelectedClient()
    {
        $this->resetPage();
        $this->selectedEntries = [];
        $this->selectAll = false;
    }

    public function updatingSelectedTechnician()
    {
        $this->resetPage();
        $this->selectedEntries = [];
        $this->selectAll = false;
    }

    public function updatingStartDate()
    {
        $this->resetPage();
    }

    public function updatingEndDate()
    {
        $this->resetPage();
    }

    public function getTimeEntries()
    {
        $query = TicketTimeEntry::query()
            ->where('company_id', Auth::user()->company_id)
            ->with(['ticket.client', 'user'])
            ->uninvoiced();

        if ($this->billableOnly) {
            $query->billable();
        }

        if ($this->selectedClient) {
            $query->whereHas('ticket', function ($q) {
                $q->where('client_id', $this->selectedClient);
            });
        }

        if ($this->selectedTechnician) {
            $query->where('user_id', $this->selectedTechnician);
        }

        if ($this->startDate) {
            $query->where('work_date', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->where('work_date', '<=', Carbon::parse($this->endDate));
        }

        return $query->orderBy('work_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
    }

    public function bulkApprove()
    {
        if (empty($this->selectedEntries)) {
            Flux::toast(
                text: 'Please select time entries to approve',
                variant: 'warning'
            );
            return;
        }

        $count = TicketTimeEntry::whereIn('id', $this->selectedEntries)
            ->where('company_id', Auth::user()->company_id)
            ->update([
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'status' => 'approved',
            ]);

        $this->selectedEntries = [];
        $this->selectAll = false;

        Flux::toast(
            text: "{$count} time entries approved successfully",
            variant: 'success'
        );
    }

    public function bulkReject()
    {
        if (empty($this->selectedEntries)) {
            Flux::toast(
                text: 'Please select time entries to reject',
                variant: 'warning'
            );
            return;
        }

        $count = TicketTimeEntry::whereIn('id', $this->selectedEntries)
            ->where('company_id', Auth::user()->company_id)
            ->update([
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'status' => 'rejected',
            ]);

        $this->selectedEntries = [];
        $this->selectAll = false;

        Flux::toast(
            text: "{$count} time entries rejected",
            variant: 'warning'
        );
    }

    public function previewInvoice()
    {
        if (empty($this->selectedEntries)) {
            Flux::toast(
                text: 'Please select time entries to preview',
                variant: 'warning'
            );
            return;
        }

        $entries = TicketTimeEntry::whereIn('id', $this->selectedEntries)
            ->with(['ticket.client'])
            ->get();

        $clientId = $entries->first()->ticket->client_id;

        if ($entries->pluck('ticket.client_id')->unique()->count() > 1) {
            Flux::toast(
                text: 'All selected time entries must belong to the same client',
                variant: 'danger'
            );
            return;
        }

        $service = new TimeEntryInvoiceService();
        $this->previewData = $service->previewInvoice(
            $this->selectedEntries,
            $clientId,
            ['groupBy' => $this->groupBy]
        );

        $this->showPreview = true;
    }

    public function closePreview()
    {
        $this->showPreview = false;
        $this->previewData = null;
    }

    public function generateInvoice()
    {
        if (empty($this->selectedEntries)) {
            Flux::toast(
                text: 'Please select time entries to invoice',
                variant: 'warning'
            );
            return;
        }

        try {
            $entries = TicketTimeEntry::whereIn('id', $this->selectedEntries)
                ->with(['ticket.client'])
                ->get();

            $clientId = $entries->first()->ticket->client_id;

            if ($entries->pluck('ticket.client_id')->unique()->count() > 1) {
                Flux::toast(
                    text: 'All selected time entries must belong to the same client',
                    variant: 'danger'
                );
                return;
            }

            $service = new TimeEntryInvoiceService();
            $invoice = $service->generateInvoiceFromTimeEntries(
                $this->selectedEntries,
                $clientId,
                ['groupBy' => $this->groupBy]
            );

            $this->selectedEntries = [];
            $this->selectAll = false;
            $this->showPreview = false;
            $this->previewData = null;

            Flux::toast(
                text: "Invoice #{$invoice->number} created successfully",
                variant: 'success'
            );

            return redirect()->route('invoices.show', $invoice->id);

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Failed to generate invoice: ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function exportTimeEntries(string $format = 'csv')
    {
        try {
            $exportService = new \App\Domains\Financial\Services\AccountingExportService();
            
            $filters = [
                'company_id' => Auth::user()->company_id,
                'client_id' => $this->selectedClient,
                'technician_id' => $this->selectedTechnician,
                'billable_only' => $this->billableOnly,
                'uninvoiced_only' => true,
            ];

            $export = $exportService->downloadExport(
                Carbon::parse($this->startDate),
                Carbon::parse($this->endDate),
                $format,
                $filters
            );

            return response()->streamDownload(
                fn () => print($export['content']),
                $export['filename'],
                ['Content-Type' => $export['mime_type']]
            );

        } catch (\Exception $e) {
            Flux::toast(
                text: 'Export failed: ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function render()
    {
        $timeEntries = $this->getTimeEntries();

        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $technicians = User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $summary = [
            'total_entries' => $timeEntries->total(),
            'total_hours' => TicketTimeEntry::query()
                ->where('company_id', Auth::user()->company_id)
                ->uninvoiced()
                ->when($this->billableOnly, fn($q) => $q->billable())
                ->when($this->selectedClient, fn($q) => $q->whereHas('ticket', fn($q2) => $q2->where('client_id', $this->selectedClient)))
                ->when($this->selectedTechnician, fn($q) => $q->where('user_id', $this->selectedTechnician))
                ->when($this->startDate, fn($q) => $q->where('work_date', '>=', Carbon::parse($this->startDate)))
                ->when($this->endDate, fn($q) => $q->where('work_date', '<=', Carbon::parse($this->endDate)))
                ->sum('hours_worked'),
            'selected_count' => count($this->selectedEntries),
        ];

        return view('livewire.billing.time-entry-approval', [
            'timeEntries' => $timeEntries,
            'clients' => $clients,
            'technicians' => $technicians,
            'summary' => $summary,
        ]);
    }
}
