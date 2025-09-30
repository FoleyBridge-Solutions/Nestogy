<?php

namespace App\Domains\Financial\Services;

use App\Domains\Core\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;

abstract class FinancialBaseService extends BaseService
{
    protected array $defaultEagerLoad = ['client'];

    protected function applyCustomFilters($query, array $filters): Builder
    {
        // Apply client filter
        if (! empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Apply amount range filters
        if (! empty($filters['amount_from'])) {
            $query->where('amount', '>=', $filters['amount_from']);
        }

        if (! empty($filters['amount_to'])) {
            $query->where('amount', '<=', $filters['amount_to']);
        }

        // Apply payment status filter
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Apply due date filters
        if (! empty($filters['due_from'])) {
            $query->where('due_date', '>=', $filters['due_from']);
        }

        if (! empty($filters['due_to'])) {
            $query->where('due_date', '<=', $filters['due_to']);
        }

        // Apply overdue filter
        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->where('due_date', '<', now())
                ->where('payment_status', '!=', 'paid');
        }

        return parent::applyCustomFilters($query, $filters);
    }

    protected function prepareCreateData(array $data): array
    {
        // Set default payment status
        if (! isset($data['payment_status'])) {
            $data['payment_status'] = 'pending';
        }

        // Calculate totals if not provided
        if (! isset($data['total']) && isset($data['amount'])) {
            $data['total'] = $this->calculateTotal($data);
        }

        // Set due date if not provided
        if (! isset($data['due_date']) && isset($data['payment_terms'])) {
            $data['due_date'] = $this->calculateDueDate($data['payment_terms']);
        }

        return parent::prepareCreateData($data);
    }

    protected function calculateTotal(array $data): float
    {
        $amount = $data['amount'] ?? 0;
        $taxRate = $data['tax_rate'] ?? 0;
        $discount = $data['discount'] ?? 0;

        $subtotal = $amount - $discount;
        $tax = $subtotal * ($taxRate / 100);

        return $subtotal + $tax;
    }

    protected function calculateDueDate($paymentTerms): \Carbon\Carbon
    {
        $days = match ($paymentTerms) {
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'net_90' => 90,
            'immediate' => 0,
            default => 30,
        };

        return now()->addDays($days);
    }

    public function getFinancialSummary(array $filters = []): array
    {
        $query = $this->buildBaseQuery();
        $query = $this->applyFilters($query, $filters);

        return [
            'total_amount' => (clone $query)->sum('amount'),
            'paid_amount' => (clone $query)->where('payment_status', 'paid')->sum('amount'),
            'pending_amount' => (clone $query)->where('payment_status', 'pending')->sum('amount'),
            'overdue_amount' => (clone $query)
                ->where('due_date', '<', now())
                ->where('payment_status', '!=', 'paid')
                ->sum('amount'),
            'count' => (clone $query)->count(),
        ];
    }

    public function getOverdueItems(array $filters = [])
    {
        $filters['overdue'] = true;

        return $this->getPaginated($filters);
    }

    protected function getCustomStatistics(): array
    {
        $query = $this->buildBaseQuery();

        return [
            'financial_summary' => $this->getFinancialSummary(),
            'by_payment_status' => (clone $query)
                ->groupBy('payment_status')
                ->selectRaw('payment_status, count(*) as count, sum(amount) as total')
                ->get()
                ->keyBy('payment_status')
                ->toArray(),
        ];
    }

    protected function afterCreate($model, array $data): void
    {
        // Log financial transaction creation
        $this->logFinancialActivity($model, 'created', $data);

        parent::afterCreate($model, $data);
    }

    protected function afterUpdate($model, array $data): void
    {
        // Log financial transaction update
        $this->logFinancialActivity($model, 'updated', $data);

        parent::afterUpdate($model, $data);
    }

    protected function logFinancialActivity($model, string $action, array $data = []): void
    {
        // Enhanced logging for financial activities (required per docs/CLAUDE.md)
        \Log::info('Financial activity', [
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'amount' => $model->amount ?? null,
            'client_id' => $model->client_id ?? null,
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id,
            'timestamp' => now(),
            'data' => $data,
        ]);
    }
}
