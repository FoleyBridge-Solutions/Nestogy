<?php

namespace App\Livewire\Client\Concerns;

trait HasDashboardInvoices
{
    protected function getInvoices()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->invoices()
            ->with(['payments'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getInvoiceStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $invoices = $this->client->invoices();

        return [
            'total_invoices' => $invoices->count(),
            'outstanding_amount' => $invoices->where('status', 'sent')->sum('amount'),
            'overdue_count' => $invoices->where('status', 'sent')->where('due_date', '<', now())->count(),
            'paid_this_month' => $invoices->where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->sum('amount'),
        ];
    }

    protected function getPaymentHistory()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->invoices()
            ->with('payments')
            ->whereHas('payments')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($invoice) {
                return [
                    'date' => $invoice->payments->first()->created_at ?? $invoice->created_at,
                    'amount' => $invoice->payments->sum('amount'),
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'method' => $invoice->payments->first()->payment_method ?? 'Unknown',
                ];
            });
    }

    protected function getSpendingTrends()
    {
        if (! $this->client) {
            return [];
        }

        $months = [];
        $amounts = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');

            $amounts[] = $this->client->invoices()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
        }

        return [
            'labels' => $months,
            'amounts' => $amounts,
        ];
    }
}
