<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Contract\Models\Contract;
use App\Traits\BelongsToCompany;
use App\Traits\HasCompanyScope;
use App\Traits\HasSearch;
use App\Traits\HasFilters;
use App\Traits\HasArchiving;
use App\Traits\HasActivity;
use Illuminate\Support\Facades\Log;

class ClientRefactored extends Model
{
    use HasFactory, 
        SoftDeletes, 
        BelongsToCompany,
        HasCompanyScope,
        HasSearch,
        HasFilters,
        HasArchiving,
        HasActivity;

    protected $table = 'clients';

    // Define searchable fields for the HasSearch trait
    protected array $searchableFields = [
        'name', 
        'company_name', 
        'email', 
        'type', 
        'website',
        'primaryContact.name',
        'primaryContact.email'
    ];

    protected $fillable = [
        'company_id',
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'website',
        'notes',
        'status',
        'type',
        'referral',
        'rate',
        'currency_code',
        'net_terms',
        'tax_id_number',
        'rmm_id',
        'lead',
        'hourly_rate',
        'accessed_at',
        'archived_at',
    ];

    protected $casts = [
        'lead' => 'boolean',
        'rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'net_terms' => 'integer',
        'accessed_at' => 'datetime',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'accessed_at',
        'archived_at',
        'deleted_at',
    ];

    // Override the applyCustomFilter method from HasFilters trait
    protected function applyCustomFilter($query, string $key, $value)
    {
        switch ($key) {
            case 'lead_status':
                return $query->where('lead', $value);
            case 'has_tickets':
                return $value ? $query->has('tickets') : $query->doesntHave('tickets');
            case 'has_invoices':
                return $value ? $query->has('invoices') : $query->doesntHave('invoices');
            case 'revenue_range':
                if (is_array($value) && count($value) === 2) {
                    return $query->whereHas('invoices', function ($q) use ($value) {
                        $q->where('status', 'Paid')
                          ->havingRaw('SUM(amount) BETWEEN ? AND ?', $value);
                    });
                }
                break;
        }
        
        return $query;
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('primary', true);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function primaryLocation()
    {
        return $this->hasOne(Location::class)->where('primary', true);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function recurringInvoices()
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function logins()
    {
        return $this->hasMany(Login::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function ticketReplies()
    {
        return $this->hasManyThrough(TicketReply::class, Ticket::class);
    }

    public function invoiceItems()
    {
        return $this->hasManyThrough(InvoiceItem::class, Invoice::class);
    }

    // Scopes (enhanced by traits)
    public function scopeLeads($query)
    {
        return $query->where('lead', true);
    }

    public function scopeCustomers($query)
    {
        return $query->where('lead', false);
    }

    public function scopeWithOpenTickets($query)
    {
        return $query->whereHas('tickets', function ($q) {
            $q->whereIn('status', ['Open', 'In Progress', 'Waiting']);
        });
    }

    public function scopeWithOverdueInvoices($query)
    {
        return $query->whereHas('invoices', function ($q) {
            $q->where('status', 'Sent')
              ->where('due_date', '<', now());
        });
    }

    public function scopeByRevenue($query, $operator = '>', $amount = 0)
    {
        return $query->whereHas('invoices', function ($q) use ($operator, $amount) {
            $q->where('status', 'Paid')
              ->havingRaw("SUM(amount) {$operator} ?", [$amount]);
        });
    }

    // Business methods
    public function isLead(): bool
    {
        return $this->lead;
    }

    public function isCustomer(): bool
    {
        return !$this->lead;
    }

    public function convertToCustomer(): bool
    {
        if (!$this->isLead()) {
            return false;
        }

        return $this->update(['lead' => false]);
    }

    public function getBalance(): float
    {
        return $this->invoices()
            ->whereIn('status', ['Sent', 'Viewed'])
            ->sum('amount');
    }

    public function getTotalRevenue(): float
    {
        return $this->invoices()
            ->where('status', 'Paid')
            ->sum('amount');
    }

    public function getMonthlyRecurring(): float
    {
        $monthly = $this->recurringInvoices()
            ->where('status', true)
            ->where('frequency', 'monthly')
            ->sum('amount');

        $yearly = $this->recurringInvoices()
            ->where('status', true)
            ->where('frequency', 'yearly')
            ->sum('amount') / 12;

        return $monthly + $yearly;
    }

    public function getOpenTicketsCount(): int
    {
        return $this->tickets()
            ->whereIn('status', ['Open', 'In Progress', 'Waiting'])
            ->count();
    }

    public function getOverdueInvoicesCount(): int
    {
        return $this->invoices()
            ->where('status', 'Sent')
            ->where('due_date', '<', now())
            ->count();
    }

    public function hasOverdueInvoices(): bool
    {
        return $this->getOverdueInvoicesCount() > 0;
    }

    public function getLastActivityDate()
    {
        $lastTicket = $this->tickets()->latest()->first()?->created_at;
        $lastInvoice = $this->invoices()->latest()->first()?->created_at;
        $lastProject = $this->projects()->latest()->first()?->created_at;

        return collect([$lastTicket, $lastInvoice, $lastProject])
            ->filter()
            ->max();
    }

    public function getHealthScore(): int
    {
        $score = 100;

        // Deduct points for overdue invoices
        $overdueInvoices = $this->getOverdueInvoicesCount();
        $score -= ($overdueInvoices * 10);

        // Deduct points for too many open tickets
        $openTickets = $this->getOpenTicketsCount();
        if ($openTickets > 5) {
            $score -= (($openTickets - 5) * 5);
        }

        // Deduct points for inactivity
        $lastActivity = $this->getLastActivityDate();
        if ($lastActivity && $lastActivity->diffInDays(now()) > 90) {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }

    public function getHealthStatus(): string
    {
        $score = $this->getHealthScore();

        if ($score >= 80) {
            return 'excellent';
        } elseif ($score >= 60) {
            return 'good';
        } elseif ($score >= 40) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    // Tag management
    public function syncTagsByName(array $tagNames): void
    {
        $tags = collect($tagNames)->map(function ($name) {
            return Tag::firstOrCreate([
                'name' => $name,
                'company_id' => $this->company_id
            ]);
        });

        $this->tags()->sync($tags->pluck('id'));
    }

    public function addTag(string $tagName): void
    {
        $tag = Tag::firstOrCreate([
            'name' => $tagName,
            'company_id' => $this->company_id
        ]);

        $this->tags()->syncWithoutDetaching([$tag->id]);
    }

    public function removeTag(string $tagName): void
    {
        $tag = Tag::where('name', $tagName)
            ->where('company_id', $this->company_id)
            ->first();

        if ($tag) {
            $this->tags()->detach($tag->id);
        }
    }

    // Override the getActivityName method from HasActivity trait
    protected function getActivityName(): string
    {
        return $this->name . ($this->company_name ? " ({$this->company_name})" : '');
    }
}