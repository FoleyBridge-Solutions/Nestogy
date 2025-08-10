<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Client Model
 * 
 * Represents clients/customers in the ERP system.
 * Clients can have multiple locations, contacts, assets, tickets, and financial records.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $company_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string $country
 * @property string|null $website
 * @property string|null $notes
 * @property string $status
 * @property float|null $hourly_rate
 * @property string|null $billing_contact
 * @property string|null $technical_contact
 * @property array|null $custom_fields
 * @property \Illuminate\Support\Carbon|null $contract_start_date
 * @property \Illuminate\Support\Carbon|null $contract_end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'lead',
        'name',
        'company_name',
        'type',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'website',
        'referral',
        'rate',
        'currency_code',
        'net_terms',
        'tax_id_number',
        'rmm_id',
        'notes',
        'status',
        'hourly_rate',
        'billing_contact',
        'technical_contact',
        'custom_fields',
        'contract_start_date',
        'contract_end_date',
        'accessed_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'lead' => 'boolean',
        'rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'net_terms' => 'integer',
        'rmm_id' => 'integer',
        'custom_fields' => 'array',
        'contract_start_date' => 'datetime',
        'contract_end_date' => 'datetime',
        'accessed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Client status enumeration
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Status labels mapping
     */
    const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    /**
     * Get the client's locations.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the client's primary location.
     */
    public function primaryLocation()
    {
        return $this->hasOne(Location::class)->where('primary', true);
    }

    /**
     * Get the client's contacts.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the client's primary contact.
     */
    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('primary', true);
    }

    /**
     * Get the client's billing contact.
     */
    public function billingContact()
    {
        return $this->hasOne(Contact::class)->where('billing', true);
    }

    /**
     * Get the client's technical contact.
     */
    public function technicalContact()
    {
        return $this->hasOne(Contact::class)->where('technical', true);
    }

    /**
     * Get the client's vendors.
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * Get the client's networks.
     */
    public function networks(): HasMany
    {
        return $this->hasMany(Network::class);
    }

    /**
     * Get the client's assets.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the client's tickets.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the client's projects.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the client's invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the client's quotes.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Get the client's recurring invoices.
     */
    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(Recurring::class);
    }

    /**
     * Get the client's expenses.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the client's tags.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'client_tags', 'client_id', 'tag_id');
    }

    /**
     * Get the user who created the client.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Commented out relationships for models that don't exist yet
    // TODO: Uncomment these as the models are created
    
    // /**
    //  * Get the client's certificates.
    //  */
    // public function certificates(): HasMany
    // {
    //     return $this->hasMany(Certificate::class);
    // }

    // /**
    //  * Get the client's domains.
    //  */
    // public function domains(): HasMany
    // {
    //     return $this->hasMany(Domain::class);
    // }

    // /**
    //  * Get the client's logins.
    //  */
    // public function logins(): HasMany
    // {
    //     return $this->hasMany(Login::class);
    // }

    // /**
    //  * Get the client's documents.
    //  */
    // public function documents(): HasMany
    // {
    //     return $this->hasMany(Document::class);
    // }

    // /**
    //  * Get the client's files.
    //  */
    // public function files(): HasMany
    // {
    //     return $this->hasMany(File::class);
    // }

    // /**
    //  * Get the client's software.
    //  */
    // public function software(): HasMany
    // {
    //     return $this->hasMany(Software::class);
    // }

    // /**
    //  * Get the client's services.
    //  */
    // public function services(): HasMany
    // {
    //     return $this->hasMany(Service::class);
    // }

    // /**
    //  * Get the client's shared items.
    //  */
    // public function sharedItems(): HasMany
    // {
    //     return $this->hasMany(SharedItem::class);
    // }

    // /**
    //  * Get the client's trips.
    //  */
    // public function trips(): HasMany
    // {
    //     return $this->hasMany(Trip::class);
    // }

    // /**
    //  * Get the client's events.
    //  */
    // public function events(): HasMany
    // {
    //     return $this->hasMany(Event::class);
    // }

    // /**
    //  * Get the client's scheduled tickets.
    //  */
    // public function scheduledTickets(): HasMany
    // {
    //     return $this->hasMany(ScheduledTicket::class);
    // }

    // /**
    //  * Get the client's logs.
    //  */
    // public function logs(): HasMany
    // {
    //     return $this->hasMany(Log::class);
    // }

    // /**
    //  * Get the client's notifications.
    //  */
    // public function notifications(): HasMany
    // {
    //     return $this->hasMany(Notification::class);
    // }

    // /**
    //  * Get the client's credits.
    //  */
    // public function credits(): HasMany
    // {
    //     return $this->hasMany(Credit::class);
    // }

    // /**
    //  * Get the client's revenues.
    //  */
    // public function revenues(): HasMany
    // {
    //     return $this->hasMany(Revenue::class);
    // }

    // /**
    //  * Get the client's recurring expenses.
    //  */
    // public function recurringExpenses(): HasMany
    // {
    //     return $this->hasMany(RecurringExpense::class);
    // }

    // /**
    //  * Get the client's inventory.
    //  */
    // public function inventory(): HasMany
    // {
    //     return $this->hasMany(Inventory::class);
    // }

    /**
     * Get the client's ticket replies through tickets.
     */
    public function ticketReplies()
    {
        return $this->hasManyThrough(TicketReply::class, Ticket::class);
    }

    /**
     * Get the client's invoice items through invoices.
     */
    public function invoiceItems()
    {
        return $this->hasManyThrough(InvoiceItem::class, Invoice::class);
    }

    /**
     * Get the client's payments through invoices.
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    /**
     * Get the client's full address.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    /**
     * Check if client is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if client is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if client is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if client is a lead.
     */
    public function isLead(): bool
    {
        return $this->lead === true;
    }

    /**
     * Check if client is a customer (not a lead).
     */
    public function isCustomer(): bool
    {
        return $this->lead === false;
    }

    /**
     * Check if client has an active contract.
     */
    public function hasActiveContract(): bool
    {
        if (!$this->contract_start_date || !$this->contract_end_date) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->contract_start_date, $this->contract_end_date);
    }

    /**
     * Check if contract is expired.
     */
    public function isContractExpired(): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }

        return Carbon::now()->gt($this->contract_end_date);
    }

    /**
     * Get days until contract expires.
     */
    public function getDaysUntilContractExpires(): ?int
    {
        if (!$this->contract_end_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->contract_end_date, false);
    }

    /**
     * Get the display name (company name or personal name).
     */
    public function getDisplayName(): string
    {
        return $this->company_name ?: $this->name;
    }

    /**
     * Get formatted hourly rate.
     */
    public function getFormattedHourlyRate(): string
    {
        if (!$this->hourly_rate) {
            return 'Not set';
        }

        return '$' . number_format($this->hourly_rate, 2) . '/hr';
    }

    /**
     * Scope to get only active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only inactive clients.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope to get only suspended clients.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope to search clients.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('company_name', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope to get clients with expiring contracts.
     */
    public function scopeContractExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('contract_end_date')
                    ->where('contract_end_date', '>=', Carbon::now())
                    ->where('contract_end_date', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope to get clients with expired contracts.
     */
    public function scopeContractExpired($query)
    {
        return $query->whereNotNull('contract_end_date')
                    ->where('contract_end_date', '<', Carbon::now());
    }

    /**
     * Scope to get only leads.
     */
    public function scopeLeads($query)
    {
        return $query->where('lead', true);
    }

    /**
     * Scope to get only customers (not leads).
     */
    public function scopeCustomers($query)
    {
        return $query->where('lead', false);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to order by accessed at.
     */
    public function scopeOrderByAccessed($query, string $direction = 'desc')
    {
        return $query->orderBy('accessed_at', $direction);
    }

    /**
     * Update the accessed_at timestamp.
     */
    public function touch($attribute = null)
    {
        if ($attribute === 'accessed_at') {
            $this->accessed_at = $this->freshTimestamp();
            return $this->save();
        }

        return parent::touch($attribute);
    }

    /**
     * Get validation rules for client creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'lead' => 'boolean',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'referral' => 'nullable|string|max:255',
            'rate' => 'nullable|numeric|min:0|max:999999.99',
            'currency_code' => 'nullable|string|size:3',
            'net_terms' => 'nullable|integer|min:0|max:365',
            'tax_id_number' => 'nullable|string|max:255',
            'rmm_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',
            'billing_contact' => 'nullable|string|max:255',
            'technical_contact' => 'nullable|string|max:255',
            'custom_fields' => 'nullable|array',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
        ];
    }

    /**
     * Get validation rules for client update.
     */
    public static function getUpdateValidationRules(int $clientId): array
    {
        $rules = self::getValidationRules();
        $rules['email'] = 'nullable|email|unique:clients,email,' . $clientId;
        return $rules;
    }

    /**
     * Get available statuses for selection.
     */
    public static function getAvailableStatuses(): array
    {
        return self::STATUS_LABELS;
    }

    /**
     * Get available client types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            'MSP' => 'MSP',
            'Individual' => 'Individual',
            'Business' => 'Business',
            'Non-Profit' => 'Non-Profit',
            'Government' => 'Government',
            'Education' => 'Education',
            'Healthcare' => 'Healthcare',
            'Other' => 'Other',
        ];
    }

    /**
     * Get the formatted rate.
     */
    public function getFormattedRate(): string
    {
        if (!$this->rate) {
            return 'Not set';
        }

        return $this->currency_code . ' ' . number_format($this->rate, 2) . '/hr';
    }

    /**
     * Get the client's balance (total unpaid invoices).
     */
    public function getBalance(): float
    {
        return $this->invoices()
            ->whereIn('status', ['Draft', 'Sent', 'Viewed'])
            ->sum('amount');
    }

    /**
     * Get the client's total paid amount.
     */
    public function getTotalPaid(): float
    {
        return $this->invoices()
            ->where('status', 'Paid')
            ->sum('amount');
    }

    /**
     * Get the client's monthly recurring amount.
     */
    public function getMonthlyRecurring(): float
    {
        return $this->recurringInvoices()
            ->where('status', true)
            ->where('frequency', 'month')
            ->sum('amount');
    }

    /**
     * Get the client's past due amount.
     */
    public function getPastDueAmount(): float
    {
        return $this->invoices()
            ->whereIn('status', ['Sent', 'Viewed'])
            ->where('due', '<', Carbon::now())
            ->sum('amount');
    }

    /**
     * Convert lead to customer.
     */
    public function convertToCustomer(): bool
    {
        $this->lead = false;
        return $this->save();
    }

    /**
     * Get the client's tag names as a comma-separated string.
     */
    public function getTagNamesAttribute(): string
    {
        return $this->tags->pluck('name')->implode(', ');
    }

    /**
     * Get the client's tag IDs.
     */
    public function getTagIdsAttribute(): array
    {
        return $this->tags->pluck('id')->toArray();
    }

    /**
     * Sync client tags.
     */
    public function syncTags(array $tagIds): void
    {
        $this->tags()->sync($tagIds);
    }

    /**
     * Sync client tags by name.
     */
    public function syncTagsByName(array $tagNames): void
    {
        $tagIds = [];
        
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }
            
            $tag = Tag::firstOrCreate(
                ['name' => $tagName, 'company_id' => $this->company_id],
                ['color' => '#' . substr(md5($tagName), 0, 6)]
            );
            
            $tagIds[] = $tag->id;
        }
        
        $this->tags()->sync($tagIds);
    }
}