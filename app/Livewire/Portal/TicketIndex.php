<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('client-portal.layouts.app')]
class TicketIndex extends Component
{
    use WithPagination;

    protected $casts = [
        'statuses' => 'array',
        'priorities' => 'array',
    ];

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public $statuses = [];

    public $priorities = [];

    public int $perPage = 10;

    /**
     * Allowed sortable columns (whitelist for security)
     */
    protected array $sortableColumns = [
        'number',
        'subject',
        'status',
        'priority',
        'created_at',
        'updated_at',
    ];

    /**
     * Available page size options
     */
    protected array $perPageOptions = [10, 25, 50, 100];

    public function mount(): void
    {
        // Restore preferences from session
        $this->sortBy = session('portal.tickets.sortBy', 'created_at');
        $this->sortDirection = session('portal.tickets.sortDirection', 'desc');
        $this->search = session('portal.tickets.search', '');
        $this->statuses = session('portal.tickets.statuses', []);
        $this->priorities = session('portal.tickets.priorities', []);
        $this->perPage = session('portal.tickets.perPage', 10);
    }

    public function sort(string $column): void
    {
        // Validate column is sortable
        if (! in_array($column, $this->sortableColumns)) {
            return;
        }

        // Toggle direction if same column, otherwise reset to ascending
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        // Persist to session
        session([
            'portal.tickets.sortBy' => $this->sortBy,
            'portal.tickets.sortDirection' => $this->sortDirection,
        ]);
    }

    public function updatedSearch(string $value): void
    {
        session(['portal.tickets.search' => $value]);
        $this->resetPage();
    }

    public function updatedStatuses(): void
    {
        Log::info('Statuses updated', ['statuses' => $this->statuses]);
        session(['portal.tickets.statuses' => $this->statuses]);
        $this->resetPage();
    }

    public function updatedPriorities(): void
    {
        Log::info('Priorities updated', ['priorities' => $this->priorities]);
        session(['portal.tickets.priorities' => $this->priorities]);
        $this->resetPage();
    }

    public function updatedPerPage(int $value): void
    {
        // Validate perPage is in allowed options
        if (! in_array($value, $this->perPageOptions)) {
            $value = 10;
        }

        session(['portal.tickets.perPage' => $value]);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statuses = [];
        $this->priorities = [];

        // Clear from session
        session()->forget([
            'portal.tickets.search',
            'portal.tickets.statuses',
            'portal.tickets.priorities',
        ]);

        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return ! empty($this->search) || ! empty($this->statuses) || ! empty($this->priorities);
    }

    #[Computed]
    public function contact()
    {
        return Auth::guard('client')->user();
    }

    #[Computed]
    public function tickets(): LengthAwarePaginator
    {
        if (! $this->contact || ! $this->contact->client) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        $query = $this->contact->client->tickets();

        // Search filter
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ILIKE', "%{$search}%")
                    ->orWhere('details', 'ILIKE', "%{$search}%")
                    ->orWhere('prefix', 'ILIKE', "%{$search}%");

                // If search is numeric, also search by number
                if (is_numeric($search)) {
                    $q->orWhere('number', (int) $search);
                }
            });
        }

        // Status filter (multi-select) - case-insensitive
        if (! empty($this->statuses)) {
            $query->where(function ($q) {
                foreach ($this->statuses as $status) {
                    $q->orWhereRaw('LOWER(status) = ?', [strtolower($status)]);
                }
            });
        }

        // Priority filter (multi-select) - case-insensitive
        if (! empty($this->priorities)) {
            $query->where(function ($q) {
                foreach ($this->priorities as $priority) {
                    $q->orWhereRaw('LOWER(priority) = ?', [strtolower($priority)]);
                }
            });
        }

        // Apply sorting (validated column)
        if (in_array($this->sortBy, $this->sortableColumns)) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function stats(): array
    {
        if (! $this->contact || ! $this->contact->client) {
            return [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'resolved_this_month' => 0,
                'avg_response_time' => '< 1h',
            ];
        }

        $tickets = $this->contact->client->tickets();

        return [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->whereIn('status', ['Open', 'In Progress', 'Waiting', 'On Hold'])->count(),
            'resolved_this_month' => $tickets->whereIn('status', ['Resolved', 'Closed'])
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'avg_response_time' => '< 1h', // Placeholder - calculate from ticket history
        ];
    }

    #[Computed]
    public function permissions(): array
    {
        if (! $this->contact) {
            return [];
        }

        return $this->contact->portal_permissions ?? [];
    }

    public function render()
    {
        return view('livewire.portal.ticket-index');
    }
}
