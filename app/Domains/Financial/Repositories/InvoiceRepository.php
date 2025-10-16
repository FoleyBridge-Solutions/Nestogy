<?php

namespace App\Domains\Financial\Repositories;

use App\Domains\Financial\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceRepository
{
    /**
     * Find invoice by ID with relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Invoice
    {
        return Invoice::with($relations)->find($id);
    }

    /**
     * Get filtered query
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = Invoice::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Get paginated invoices
     */
    public function getPaginated(array $filters, int $perPage = 25, array $relations = []): LengthAwarePaginator
    {
        $query = $this->getFilteredQuery($filters);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get invoices by company
     */
    public function getByCompany(int $companyId): Collection
    {
        return Invoice::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get invoices by client
     */
    public function getByClient(int $clientId): Collection
    {
        return Invoice::where('client_id', $clientId)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue(int $companyId): Collection
    {
        return Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['Paid', 'Cancelled'])
            ->where('due_date', '<', now())
            ->with(['client'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get unpaid invoices
     */
    public function getUnpaid(int $companyId): Collection
    {
        return Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['Paid', 'Cancelled'])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Get company revenue statistics
     */
    public function getRevenueStatistics(int $companyId, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $query = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('status', 'Paid');

        if ($from) {
            $query->where('paid_at', '>=', $from);
        }

        if ($to) {
            $query->where('paid_at', '<=', $to);
        }

        $stats = $query->selectRaw('
            SUM(amount) as total_revenue,
            SUM(paid_amount) as total_paid,
            COUNT(*) as invoice_count,
            AVG(amount) as average_amount
        ')->first();

        return [
            'total_revenue' => $stats->total_revenue ?? 0,
            'total_paid' => $stats->total_paid ?? 0,
            'invoice_count' => $stats->invoice_count ?? 0,
            'average_amount' => $stats->average_amount ?? 0,
        ];
    }

    /**
     * Get next invoice number
     */
    public function getNextInvoiceNumber(int $companyId, ?string $prefix = null): int
    {
        $query = Invoice::where('company_id', $companyId);

        if ($prefix) {
            $query->where('prefix', $prefix);
        }

        $lastInvoice = $query->orderBy('number', 'desc')->first();

        return $lastInvoice ? $lastInvoice->number + 1 : 1;
    }

    /**
     * Create invoice
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Update invoice
     */
    public function update(Invoice $invoice, array $data): bool
    {
        return $invoice->update($data);
    }

    /**
     * Delete invoice
     */
    public function delete(Invoice $invoice): bool
    {
        return $invoice->delete();
    }
}
