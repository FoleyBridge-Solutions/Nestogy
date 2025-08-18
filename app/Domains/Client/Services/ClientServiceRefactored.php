<?php

namespace App\Domains\Client\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\BaseService;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;

class ClientServiceRefactored extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = Client::class;
        $this->defaultEagerLoad = ['primaryContact', 'primaryLocation'];
        $this->searchableFields = ['name', 'type', 'website', 'email', 'primaryContact.name', 'primaryContact.email'];
        $this->defaultSortField = 'accessed_at';
        $this->defaultSortDirection = 'desc';
    }

    protected function applyCustomFilters($query, array $filters)
    {
        // Override default to exclude leads unless specifically requested
        if (!isset($filters['include_leads'])) {
            $query->where('lead', false);
        }
        
        return $query;
    }

    protected function afterCreate(\Illuminate\Database\Eloquent\Model $model, array $data): void
    {
        $location = null;
        
        // Create primary location if provided
        if (!empty($data['location_address']) || !empty($data['location_city']) || !empty($data['address'])) {
            $location = Location::create([
                'company_id' => Auth::user()->company_id,
                'client_id' => $model->id,
                'name' => $data['location_name'] ?? 'Primary Location',
                'address' => $data['location_address'] ?? $data['address'] ?? null,
                'city' => $data['location_city'] ?? $data['city'] ?? null,
                'state' => $data['location_state'] ?? $data['state'] ?? null,
                'zip' => $data['location_zip'] ?? $data['zip_code'] ?? null,
                'country' => $data['location_country'] ?? $data['country'] ?? null,
                'phone' => $data['location_phone'] ?? null,
                'primary' => true,
            ]);
        }

        // Create primary contact if provided
        if (!empty($data['contact_name']) || !empty($data['contact_email'])) {
            Contact::create([
                'company_id' => Auth::user()->company_id,
                'client_id' => $model->id,
                'location_id' => $location->id ?? null,
                'name' => $data['contact_name'] ?? null,
                'title' => $data['contact_title'] ?? null,
                'phone' => $data['contact_phone'] ?? null,
                'extension' => $data['contact_extension'] ?? null,
                'mobile' => $data['contact_mobile'] ?? null,
                'email' => $data['contact_email'] ?? null,
                'primary' => true,
                'technical' => $data['contact_technical'] ?? false,
                'billing' => $data['contact_billing'] ?? false,
            ]);
        }

        // Sync tags if provided
        if (!empty($data['tags'])) {
            $tags = is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags'];
            if (is_array($tags) && count($tags) > 0) {
                $model->syncTagsByName($tags);
            }
        }
    }

    protected function afterUpdate(\Illuminate\Database\Eloquent\Model $model, array $data): void
    {
        // Update access timestamp
        $model->touch('accessed_at');

        // Sync tags if provided
        if (isset($data['tags'])) {
            $tags = is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags'];
            if (is_array($tags)) {
                $model->syncTagsByName($tags);
            }
        }
    }

    protected function beforeArchive(\Illuminate\Database\Eloquent\Model $model): void
    {
        // Archive related records
        $model->contacts()->update(['archived_at' => now()]);
        $model->locations()->update(['archived_at' => now()]);
        $model->assets()->update(['archived_at' => now()]);
    }

    protected function beforeRestore(\Illuminate\Database\Eloquent\Model $model): void
    {
        // Restore related records
        $model->contacts()->update(['archived_at' => null]);
        $model->locations()->update(['archived_at' => null]);
        $model->assets()->update(['archived_at' => null]);
    }

    protected function beforeDelete(\Illuminate\Database\Eloquent\Model $model): void
    {
        // Delete related records in proper order
        $model->ticketReplies()->delete();
        $model->tickets()->delete();
        $model->invoiceItems()->delete();
        $model->payments()->delete();
        $model->invoices()->delete();
        $model->assets()->delete();
        $model->networks()->delete();
        $model->certificates()->delete();
        $model->domains()->delete();
        $model->logins()->delete();
        $model->documents()->delete();
        $model->files()->delete();
        $model->contacts()->delete();
        $model->locations()->delete();
        $model->vendors()->delete();
    }

    protected function getCustomStatistics(): array
    {
        $companyId = Auth::user()->company_id;
        
        return [
            'total_revenue' => Client::where('company_id', $companyId)
                ->whereHas('invoices', function($q) {
                    $q->where('status', 'Paid');
                })
                ->with('invoices')
                ->get()
                ->sum(function($client) {
                    return $client->invoices->where('status', 'Paid')->sum('amount');
                }),
            'outstanding_balance' => Client::where('company_id', $companyId)
                ->whereHas('invoices', function($q) {
                    $q->whereIn('status', ['Draft', 'Sent']);
                })
                ->with('invoices')
                ->get()
                ->sum(function($client) {
                    return $client->invoices->whereIn('status', ['Draft', 'Sent'])->sum('amount');
                }),
            'total_tickets' => Client::where('company_id', $companyId)
                ->withCount('tickets')
                ->get()
                ->sum('tickets_count'),
            'leads_count' => Client::where('company_id', $companyId)
                ->where('lead', true)
                ->whereNull('archived_at')
                ->count(),
        ];
    }

    // Legacy wrapper methods for backward compatibility
    public function createClient(array $data)
    {
        $client = $this->create($data);
        
        return [
            'client_id' => $client->id,
            'name' => $client->name,
            'client' => $client,
            'location' => $client->primaryLocation,
            'contact' => $client->primaryContact,
        ];
    }

    public function updateClient(Client $client, array $data)
    {
        return $this->update($client, $data);
    }

    public function archiveClient(Client $client)
    {
        return $this->archive($client);
    }

    public function restoreClient(Client $client)
    {
        return $this->restore($client);
    }

    public function deleteClient(Client $client)
    {
        return $this->delete($client);
    }

    // Business-specific methods
    public function getClientStats(Client $client): array
    {
        return [
            'total_tickets' => $client->tickets()->count(),
            'open_tickets' => $client->tickets()->whereIn('status', ['Open', 'In Progress', 'Waiting'])->count(),
            'closed_tickets' => $client->tickets()->where('status', 'Closed')->count(),
            'total_invoices' => $client->invoices()->count(),
            'draft_invoices' => $client->invoices()->where('status', 'Draft')->count(),
            'sent_invoices' => $client->invoices()->where('status', 'Sent')->count(),
            'paid_invoices' => $client->invoices()->where('status', 'Paid')->count(),
            'total_revenue' => $client->invoices()->where('status', 'Paid')->sum('amount'),
            'outstanding_balance' => $client->invoices()->whereIn('status', ['Draft', 'Sent'])->sum('amount'),
            'total_assets' => $client->assets()->whereNull('archived_at')->count(),
            'active_assets' => $client->assets()->whereNull('archived_at')->where('status', 'Active')->count(),
            'total_contacts' => $client->contacts()->whereNull('archived_at')->count(),
            'total_locations' => $client->locations()->whereNull('archived_at')->count(),
        ];
    }

    public function getClientActivity(Client $client, int $limit = 50): Collection
    {
        $activities = collect();

        // Recent tickets
        $tickets = $client->tickets()
            ->select('id', 'number', 'subject', 'status', 'created_at', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit / 2)
            ->get()
            ->map(function ($ticket) {
                return [
                    'type' => 'ticket',
                    'id' => $ticket->id,
                    'title' => "Ticket #{$ticket->number}: {$ticket->subject}",
                    'status' => $ticket->status,
                    'date' => $ticket->created_at,
                    'url' => route('tickets.show', $ticket->id),
                ];
            });

        // Recent invoices
        $invoices = $client->invoices()
            ->select('id', 'number', 'scope', 'status', 'amount', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit / 2)
            ->get()
            ->map(function ($invoice) {
                return [
                    'type' => 'invoice',
                    'id' => $invoice->id,
                    'title' => "Invoice #{$invoice->number}: {$invoice->scope}",
                    'status' => $invoice->status,
                    'amount' => $invoice->amount,
                    'date' => $invoice->created_at,
                    'url' => route('financial.invoices.show', $invoice->id),
                ];
            });

        return $activities
            ->merge($tickets)
            ->merge($invoices)
            ->sortByDesc('date')
            ->take($limit)
            ->values();
    }

    public function updateClientAccess(Client $client): void
    {
        $client->touch('accessed_at');
    }

    public function getClientsWithUpcomingRenewals(int $days = 30): Collection
    {
        return $this->buildBaseQuery()
            ->where(function ($query) use ($days) {
                $query->whereHas('domains', function ($q) use ($days) {
                    $q->where('expire', '<=', now()->addDays($days))
                      ->where('expire', '>=', now());
                })
                ->orWhereHas('certificates', function ($q) use ($days) {
                    $q->where('expire', '<=', now()->addDays($days))
                      ->where('expire', '>=', now());
                });
            })
            ->with(['domains' => function ($query) use ($days) {
                $query->where('expire', '<=', now()->addDays($days))
                      ->where('expire', '>=', now());
            }, 'certificates' => function ($query) use ($days) {
                $query->where('expire', '<=', now()->addDays($days))
                      ->where('expire', '>=', now());
            }])
            ->get();
    }

    public function getClientsByType(string $type): Collection
    {
        return $this->getAll(['type' => $type]);
    }

    public function getClientTypesWithCounts(): Collection
    {
        return $this->buildBaseQuery()
            ->whereNotNull('type')
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get();
    }

    public function getClientsWithoutPrimaryContact(): Collection
    {
        return $this->buildBaseQuery()
            ->whereDoesntHave('contacts', function ($query) {
                $query->where('primary', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function getClientsWithoutPrimaryLocation(): Collection
    {
        return $this->buildBaseQuery()
            ->whereDoesntHave('locations', function ($query) {
                $query->where('primary', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function getClientFinancialSummary(Client $client): array
    {
        $invoices = $client->invoices()
            ->selectRaw("
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status IN ('Sent', 'Viewed') THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ('Sent', 'Viewed') THEN amount ELSE 0 END) as unpaid_amount,
                SUM(CASE WHEN status IN ('Sent', 'Viewed') AND due_date < CURRENT_DATE THEN amount ELSE 0 END) as past_due_amount
            ")
            ->first();

        $recurring = $client->recurringInvoices()
            ->where('status', true)
            ->selectRaw("
                COUNT(*) as active_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN frequency = 'month' THEN amount ELSE 0 END) as monthly_amount,
                SUM(CASE WHEN frequency = 'year' THEN amount/12 ELSE 0 END) as yearly_monthly_amount
            ")
            ->first();

        return [
            'invoices' => $invoices,
            'recurring' => $recurring,
            'monthly_recurring' => ($recurring->monthly_amount ?? 0) + ($recurring->yearly_monthly_amount ?? 0),
            'balance' => $invoices->unpaid_amount ?? 0,
            'past_due' => $invoices->past_due_amount ?? 0,
            'total_revenue' => $invoices->paid_amount ?? 0,
        ];
    }

    public function getRecentlyAccessedClients(int $limit = 10): Collection
    {
        return $this->buildBaseQuery()
            ->whereNotNull('accessed_at')
            ->orderBy('accessed_at', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'type', 'accessed_at']);
    }

    public function bulkUpdateClients(array $clientIds, array $data): int
    {
        return $this->bulkUpdate($clientIds, $data);
    }
}