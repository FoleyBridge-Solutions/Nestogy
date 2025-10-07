<?php

namespace App\Domains\Financial\Services;

use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AccountingExportService
{
    public function exportTimeEntries(
        Carbon $startDate,
        Carbon $endDate,
        string $format = 'csv',
        array $filters = []
    ): string {
        $timeEntries = $this->getTimeEntries($startDate, $endDate, $filters);

        return match ($format) {
            'csv' => $this->exportToCsv($timeEntries),
            'quickbooks_iif' => $this->exportToQuickBooksIIF($timeEntries),
            'xero' => $this->exportToXero($timeEntries),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    protected function getTimeEntries(Carbon $startDate, Carbon $endDate, array $filters): Collection
    {
        $query = TicketTimeEntry::with(['ticket.client', 'user'])
            ->whereBetween('work_date', [$startDate, $endDate]);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['client_id'])) {
            $query->whereHas('ticket', fn($q) => $q->where('client_id', $filters['client_id']));
        }

        if (isset($filters['technician_id'])) {
            $query->where('user_id', $filters['technician_id']);
        }

        if (isset($filters['billable_only']) && $filters['billable_only']) {
            $query->where('billable', true);
        }

        if (isset($filters['invoiced_only']) && $filters['invoiced_only']) {
            $query->whereNotNull('invoice_id');
        } elseif (isset($filters['uninvoiced_only']) && $filters['uninvoiced_only']) {
            $query->whereNull('invoice_id');
        }

        return $query->orderBy('work_date')->orderBy('created_at')->get();
    }

    protected function exportToCsv(Collection $timeEntries): string
    {
        $csvData = [];
        
        $csvData[] = [
            'Date',
            'Client',
            'Ticket',
            'Technician',
            'Description',
            'Hours',
            'Rate',
            'Amount',
            'Billable',
            'Invoiced',
            'Invoice #',
        ];

        foreach ($timeEntries as $entry) {
            $csvData[] = [
                $entry->work_date->format('Y-m-d'),
                $entry->ticket->client->name ?? 'N/A',
                "#{$entry->ticket->number}" ?? 'N/A',
                $entry->user->name ?? 'Unknown',
                $entry->description ?? '',
                number_format($entry->hours_worked, 2),
                number_format($entry->hourly_rate ?? 0, 2),
                number_format(($entry->hours_worked * ($entry->hourly_rate ?? 0)), 2),
                $entry->billable ? 'Yes' : 'No',
                $entry->invoice_id ? 'Yes' : 'No',
                $entry->invoice_id ?? '',
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    protected function exportToQuickBooksIIF(Collection $timeEntries): string
    {
        $lines = [];
        
        $lines[] = "!TIMACT\tDATE\tCUST\tSERV\tDURATION\tNOTE\tBILLSTATUS";
        
        foreach ($timeEntries as $entry) {
            $client = $entry->ticket->client->name ?? 'N/A';
            $service = 'Managed Services';
            $duration = $this->formatDurationForQuickBooks($entry->hours_worked);
            $note = $this->cleanForIIF($entry->description ?? '');
            $billStatus = $entry->billable ? 'Billable' : 'Not Billable';
            
            $lines[] = "TIMACT\t{$entry->work_date->format('m/d/Y')}\t{$client}\t{$service}\t{$duration}\t{$note}\t{$billStatus}";
        }

        return implode("\n", $lines);
    }

    protected function exportToXero(Collection $timeEntries): string
    {
        $csvData = [];
        
        $csvData[] = [
            '*ContactName',
            'Description',
            '*Date',
            'DurationInHours',
            'Rate',
            'Project',
            'Task',
        ];

        foreach ($timeEntries as $entry) {
            $csvData[] = [
                $entry->ticket->client->name ?? 'N/A',
                $entry->description ?? 'Professional Services',
                $entry->work_date->format('Y-m-d'),
                number_format($entry->hours_worked, 2),
                number_format($entry->hourly_rate ?? 0, 2),
                "Ticket #{$entry->ticket->number}",
                $entry->work_type ?? 'General Support',
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    protected function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    protected function formatDurationForQuickBooks(float $hours): string
    {
        $totalMinutes = $hours * 60;
        $hoursInt = floor($totalMinutes / 60);
        $minutesInt = $totalMinutes % 60;
        
        return sprintf('%d:%02d', $hoursInt, $minutesInt);
    }

    protected function cleanForIIF(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    public function downloadExport(
        Carbon $startDate,
        Carbon $endDate,
        string $format,
        array $filters = []
    ): array {
        $content = $this->exportTimeEntries($startDate, $endDate, $format, $filters);
        
        $extension = match ($format) {
            'quickbooks_iif' => 'iif',
            'xero' => 'csv',
            default => 'csv',
        };
        
        $filename = sprintf(
            'time_entries_%s_to_%s.%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $extension
        );
        
        return [
            'content' => $content,
            'filename' => $filename,
            'mime_type' => $this->getMimeType($extension),
        ];
    }

    protected function getMimeType(string $extension): string
    {
        return match ($extension) {
            'csv' => 'text/csv',
            'iif' => 'application/octet-stream',
            default => 'text/plain',
        };
    }

    public function getSupportedFormats(): array
    {
        return [
            'csv' => 'CSV (Comma Separated Values)',
            'quickbooks_iif' => 'QuickBooks IIF',
            'xero' => 'Xero CSV',
        ];
    }

    public function exportInvoicedTimeByClient(
        int $clientId,
        Carbon $startDate,
        Carbon $endDate
    ): string {
        $client = Client::findOrFail($clientId);
        
        $timeEntries = TicketTimeEntry::with(['ticket', 'user', 'invoice'])
            ->whereHas('ticket', fn($q) => $q->where('client_id', $clientId))
            ->whereBetween('work_date', [$startDate, $endDate])
            ->whereNotNull('invoice_id')
            ->orderBy('invoice_id')
            ->orderBy('work_date')
            ->get();

        $csvData = [];
        $csvData[] = [
            'Invoice #',
            'Date',
            'Ticket',
            'Technician',
            'Description',
            'Hours',
            'Rate',
            'Amount',
        ];

        foreach ($timeEntries as $entry) {
            $csvData[] = [
                $entry->invoice ? "INV-{$entry->invoice->number}" : 'N/A',
                $entry->work_date->format('Y-m-d'),
                "#{$entry->ticket->number}",
                $entry->user->name ?? 'Unknown',
                $entry->description ?? '',
                number_format($entry->hours_worked, 2),
                number_format($entry->hourly_rate ?? 0, 2),
                number_format(($entry->hours_worked * ($entry->hourly_rate ?? 0)), 2),
            ];
        }

        return $this->arrayToCsv($csvData);
    }

    public function exportSummaryByClient(Carbon $startDate, Carbon $endDate, int $companyId): string
    {
        $timeEntries = TicketTimeEntry::with(['ticket.client', 'user'])
            ->where('company_id', $companyId)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->get();

        $summary = $timeEntries->groupBy('ticket.client_id')->map(function ($entries) {
            $client = $entries->first()->ticket->client;
            
            return [
                'client_name' => $client->name ?? 'Unknown',
                'total_hours' => $entries->sum('hours_worked'),
                'billable_hours' => $entries->where('billable', true)->sum('hours_worked'),
                'non_billable_hours' => $entries->where('billable', false)->sum('hours_worked'),
                'invoiced_hours' => $entries->whereNotNull('invoice_id')->sum('hours_worked'),
                'uninvoiced_hours' => $entries->whereNull('invoice_id')->sum('hours_worked'),
                'entry_count' => $entries->count(),
            ];
        });

        $csvData = [];
        $csvData[] = [
            'Client',
            'Total Hours',
            'Billable Hours',
            'Non-Billable Hours',
            'Invoiced Hours',
            'Uninvoiced Hours',
            'Entry Count',
        ];

        foreach ($summary as $row) {
            $csvData[] = [
                $row['client_name'],
                number_format($row['total_hours'], 2),
                number_format($row['billable_hours'], 2),
                number_format($row['non_billable_hours'], 2),
                number_format($row['invoiced_hours'], 2),
                number_format($row['uninvoiced_hours'], 2),
                $row['entry_count'],
            ];
        }

        return $this->arrayToCsv($csvData);
    }
}
