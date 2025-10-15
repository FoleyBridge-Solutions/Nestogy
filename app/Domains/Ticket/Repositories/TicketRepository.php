<?php

namespace App\Domains\Ticket\Repositories;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TicketRepository
{
    /**
     * Find ticket by ID with optional relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Ticket
    {
        $query = Ticket::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Get filtered query builder
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = Ticket::query();

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

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query;
    }

    /**
     * Get paginated tickets with filters
     */
    public function getPaginated(array $filters, int $perPage = 25, array $relations = []): LengthAwarePaginator
    {
        $query = $this->getFilteredQuery($filters);

        if (! empty($relations)) {
            $query->with($relations);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Get tickets by company
     */
    public function getByCompany(int $companyId, array $relations = []): Collection
    {
        return Ticket::where('company_id', $companyId)
            ->with($relations)
            ->get();
    }

    /**
     * Get tickets by client
     */
    public function getByClient(int $clientId, array $relations = []): Collection
    {
        return Ticket::where('client_id', $clientId)
            ->with($relations)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get overdue tickets by SLA
     */
    public function getOverdueBySla(int $companyId): Collection
    {
        return Ticket::where('company_id', $companyId)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->whereHas('priorityQueue', function ($query) {
                $query->whereNotNull('sla_deadline')
                    ->where('sla_deadline', '<', now());
            })
            ->with(['client', 'assignee', 'priorityQueue'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get unassigned tickets
     */
    public function getUnassigned(int $companyId, array $statuses = []): Collection
    {
        $query = Ticket::where('company_id', $companyId)
            ->whereNull('assigned_to');

        if (! empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        return $query->with(['client'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get tickets due today
     */
    public function getDueToday(int $companyId): Collection
    {
        return Ticket::where('company_id', $companyId)
            ->whereDate('scheduled_at', today())
            ->whereNotIn('status', ['closed', 'resolved'])
            ->with(['client', 'assignee'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get active tickets for user
     */
    public function getActiveForUser(int $userId, int $companyId): Collection
    {
        return Ticket::where('company_id', $companyId)
            ->where('assigned_to', $userId)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->with(['client'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get watched tickets for user
     */
    public function getWatchedByUser(int $userId, int $companyId): Collection
    {
        return Ticket::where('company_id', $companyId)
            ->whereHas('watchers', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('is_active', true);
            })
            ->with(['client', 'assignee', 'watchers'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Search tickets
     */
    public function search(string $query, int $companyId, int $limit = 10, ?int $excludeId = null): Collection
    {
        $ticketsQuery = Ticket::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                    ->orWhere('subject', 'like', "%{$query}%")
                    ->orWhereHas('client', function ($cq) use ($query) {
                        $cq->where('name', 'like', "%{$query}%");
                    });
            });

        if ($excludeId) {
            $ticketsQuery->where('id', '!=', $excludeId);
        }

        return $ticketsQuery->with(['client:id,name', 'assignee:id,name'])
            ->orderBy('number', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get ticket statistics for company
     */
    public function getCompanyStatistics(int $companyId): array
    {
        $baseQuery = Ticket::where('company_id', $companyId);

        return [
            'total' => $baseQuery->count(),
            'open' => (clone $baseQuery)->whereIn('status', ['new', 'open', 'in_progress'])->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_to')
                ->whereNotIn('status', ['closed', 'resolved'])->count(),
            'overdue_sla' => $this->getOverdueBySla($companyId)->count(),
        ];
    }

    /**
     * Create ticket
     */
    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    /**
     * Update ticket
     */
    public function update(Ticket $ticket, array $data): bool
    {
        return $ticket->update($data);
    }

    /**
     * Delete ticket (soft delete)
     */
    public function delete(Ticket $ticket): bool
    {
        return $ticket->delete();
    }

    /**
     * Get next ticket number for company
     */
    public function getNextTicketNumber(int $companyId): int
    {
        $lastTicket = Ticket::where('company_id', $companyId)
            ->orderBy('number', 'desc')
            ->first();

        return $lastTicket ? $lastTicket->number + 1 : 1001;
    }
}
