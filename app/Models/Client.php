<?php

namespace App\Models;

use App\Domains\Contract\Models\Contract;
use App\Domains\Ticket\Models\Ticket;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Client extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

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
        'hourly_rate',
        'billing_contact',
        'technical_contact',
        'custom_fields',
        'contract_start_date',
        'contract_end_date',
        'lead',
        'type',
        'accessed_at',
        'sla_id',
        'referral',
        'rate',
        'currency_code',
        'net_terms',
        'tax_id_number',
        'rmm_id',
        // Custom rate fields
        'custom_standard_rate',
        'custom_after_hours_rate',
        'custom_emergency_rate',
        'custom_weekend_rate',
        'custom_holiday_rate',
        'custom_after_hours_multiplier',
        'custom_emergency_multiplier',
        'custom_weekend_multiplier',
        'custom_holiday_multiplier',
        'custom_rate_calculation_method',
        'custom_minimum_billing_increment',
        'custom_time_rounding_method',
        'use_custom_rates',
        // Subscription fields
        'company_link_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
        'subscription_plan_id',
        'trial_ends_at',
        'next_billing_date',
        'subscription_started_at',
        'subscription_canceled_at',
        'current_user_count',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'hourly_rate' => 'decimal:2',
        'custom_fields' => 'array',
        'contract_start_date' => 'datetime',
        'contract_end_date' => 'datetime',
        'lead' => 'boolean',
        'accessed_at' => 'datetime',
        'sla_id' => 'integer',
        // Custom rate field casts
        'custom_standard_rate' => 'decimal:2',
        'custom_after_hours_rate' => 'decimal:2',
        'custom_emergency_rate' => 'decimal:2',
        'custom_weekend_rate' => 'decimal:2',
        'custom_holiday_rate' => 'decimal:2',
        'custom_after_hours_multiplier' => 'decimal:2',
        'custom_emergency_multiplier' => 'decimal:2',
        'custom_weekend_multiplier' => 'decimal:2',
        'custom_holiday_multiplier' => 'decimal:2',
        'custom_minimum_billing_increment' => 'decimal:2',
        'use_custom_rates' => 'boolean',
        // Subscription field casts
        'company_link_id' => 'integer',
        'subscription_plan_id' => 'integer',
        'trial_ends_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'subscription_started_at' => 'datetime',
        'subscription_canceled_at' => 'datetime',
        'current_user_count' => 'integer',
    ];

    protected $dates = [
        'contract_start_date',
        'contract_end_date',
        'accessed_at',
        'deleted_at',
        'trial_ends_at',
        'next_billing_date',
        'subscription_started_at',
        'subscription_canceled_at',
    ];

    /**
     * Scope to get recently accessed clients
     */
    public function scopeRecentlyAccessed($query, $limit = 5)
    {
        return $query->whereNotNull('accessed_at')
            ->orderBy('accessed_at', 'desc')
            ->limit($limit);
    }

    /**
     * Scope to get active clients
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNull('archived_at');
    }

    /**
     * Mark this client as recently accessed
     */
    public function markAsAccessed()
    {
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Get the client's contacts.
     */
    public function contacts()
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
     * Get the technicians assigned to this client.
     */
    public function assignedTechnicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_clients')
            ->withPivot(['access_level', 'is_primary', 'assigned_at', 'expires_at', 'notes'])
            ->withTimestamps()
            ->whereNull('user_clients.expires_at')
            ->orWhere('user_clients.expires_at', '>', now());
    }

    /**
     * Get the primary technician for this client.
     */
    public function primaryTechnician()
    {
        return $this->assignedTechnicians()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Check if a specific user is assigned to this client.
     */
    public function hasAssignedTechnician($userId): bool
    {
        return $this->assignedTechnicians()
            ->where('users.id', $userId)
            ->exists();
    }

    /**
     * Assign a technician to this client.
     */
    public function assignTechnician(User $user, array $options = []): void
    {
        $this->assignedTechnicians()->syncWithoutDetaching([
            $user->id => array_merge([
                'access_level' => 'view',
                'is_primary' => false,
                'assigned_at' => now(),
            ], $options),
        ]);
    }

    /**
     * Remove a technician from this client.
     */
    public function removeTechnician(User $user): void
    {
        $this->assignedTechnicians()->detach($user->id);
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
     * Get the client's locations/addresses.
     */
    public function locations()
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
     * Get the client's addresses.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the client's communication logs.
     */
    public function communicationLogs()
    {
        return $this->hasMany(CommunicationLog::class);
    }

    /**
     * Get the tags associated with the client.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'client_tags');
    }

    /**
     * Get the client's assets.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the client's RMM client mappings.
     */
    public function rmmClientMappings()
    {
        return $this->hasMany(\App\Domains\Integration\Models\RmmClientMapping::class);
    }

    /**
     * Get the client's tickets.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the client's ticket ratings.
     */
    public function ticketRatings()
    {
        return $this->hasMany(TicketRating::class);
    }

    /**
     * Get the client's invoices.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the client's payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the client's projects.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the client's contracts.
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the client's quotes.
     */
    public function quotes()
    {
        return $this->hasMany(\App\Models\Quote::class);
    }

    /**
     * Get the client's active contract.
     */
    public function activeContract()
    {
        return $this->hasOne(Contract::class)
            ->where('status', Contract::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the client's recurring invoices.
     */
    public function recurringInvoices()
    {
        return $this->hasMany(Recurring::class);
    }

    /**
     * Get the subscription plan for this client.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get the linked company (tenant) for this client.
     */
    public function linkedCompany()
    {
        return $this->belongsTo(Company::class, 'company_link_id');
    }

    /**
     * Get the payment methods for this client.
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'client_id');
    }

    /**
     * Get the default payment method for this client.
     */
    public function defaultPaymentMethod()
    {
        return $this->hasOne(PaymentMethod::class, 'client_id')->where('is_default', true);
    }

    /**
     * Get users who have favorited this client.
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'user_favorite_clients', 'client_id', 'user_id')
            ->where('users.company_id', $this->company_id)
            ->withTimestamps();
    }

    /**
     * Scope a query to only include leads.
     */
    public function scopeLeads($query)
    {
        return $query->where('lead', true);
    }

    /**
     * Scope a query to exclude leads.
     */
    public function scopeClients($query)
    {
        return $query->where('lead', false);
    }

    /**
     * Get the client's full address as a string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the client's display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->company_name ?: $this->name;
    }

    /**
     * Get the client's balance.
     */
    public function getBalance()
    {
        $totalInvoiced = $this->invoices()->sum('total');
        $totalPaid = $this->invoices()->sum('paid');

        return $totalInvoiced - $totalPaid;
    }

    /**
     * Get the client's monthly recurring revenue.
     */
    public function getMonthlyRecurring()
    {
        return $this->recurringInvoices()
            ->where('status', true)
            ->sum('amount');
    }

    /**
     * Check if this client is a lead.
     */
    public function isLead()
    {
        return (bool) $this->lead;
    }

    /**
     * Convert lead to customer.
     */
    public function convertToCustomer()
    {
        $this->lead = false;
        $this->save();

        return $this;
    }

    /**
     * Sync tags for the client.
     */
    public function syncTags($tagIds)
    {
        return $this->tags()->sync($tagIds);
    }

    /**
     * Get the client's SLA.
     */
    public function sla()
    {
        return $this->belongsTo(\App\Domains\Ticket\Models\SLA::class, 'sla_id');
    }

    /**
     * Get hourly rate for a specific rate type.
     * Returns client-specific rate if available, otherwise falls back to company rates.
     */
    public function getHourlyRate(string $rateType): float
    {
        // Use custom rates if client has them configured
        if ($this->use_custom_rates) {
            if ($this->custom_rate_calculation_method === 'fixed_rates') {
                return $this->getCustomFixedRate($rateType);
            }

            // Use custom multiplier method
            $baseRate = $this->custom_standard_rate ?? $this->hourly_rate ?? $this->company->getHourlyRate('standard');
            $multiplier = $this->getCustomMultiplier($rateType);

            return round($baseRate * $multiplier, 2);
        }

        // Fall back to company rates
        return $this->company->getHourlyRate($rateType);
    }

    /**
     * Get custom fixed rate for rate type.
     */
    protected function getCustomFixedRate(string $rateType): float
    {
        $rates = [
            'standard' => $this->custom_standard_rate ?? $this->hourly_rate,
            'after_hours' => $this->custom_after_hours_rate,
            'emergency' => $this->custom_emergency_rate,
            'weekend' => $this->custom_weekend_rate,
            'holiday' => $this->custom_holiday_rate,
        ];

        // Return custom rate if set, otherwise fall back to company rate
        return $rates[$rateType] ?? $this->company->getHourlyRate($rateType);
    }

    /**
     * Get custom multiplier for rate type.
     */
    protected function getCustomMultiplier(string $rateType): float
    {
        $multipliers = [
            'standard' => 1.0,
            'after_hours' => $this->custom_after_hours_multiplier,
            'emergency' => $this->custom_emergency_multiplier,
            'weekend' => $this->custom_weekend_multiplier,
            'holiday' => $this->custom_holiday_multiplier,
        ];

        // Return custom multiplier if set, otherwise fall back to company multiplier
        return $multipliers[$rateType] ?? $this->company->getMultiplier($rateType);
    }

    /**
     * Round time according to client settings or company defaults.
     */
    public function roundTime(float $hours): float
    {
        if ($this->use_custom_rates && $this->custom_minimum_billing_increment) {
            $increment = $this->custom_minimum_billing_increment;
            $method = $this->custom_time_rounding_method ?? 'nearest';

            switch ($method) {
                case 'up':
                    return ceil($hours / $increment) * $increment;
                case 'down':
                    return floor($hours / $increment) * $increment;
                case 'nearest':
                    return round($hours / $increment) * $increment;
                default:
                    return $hours;
            }
        }

        // Fall back to company settings
        return $this->company->roundTime($hours);
    }

    /**
     * Get the effective SLA for this client (specific SLA or company default).
     */
    public function getEffectiveSLA()
    {
        // First try to get the client's specific SLA
        if ($this->sla_id && $this->sla && $this->sla->is_active) {
            return $this->sla;
        }

        // Fallback to company default SLA
        return \App\Domains\Ticket\Models\SLA::where('company_id', $this->company_id)
            ->where('is_default', true)
            ->where('is_active', true)
            ->effectiveOn()
            ->first();
    }

    public function rateCards()
    {
        return $this->hasMany(\App\Domains\Financial\Models\RateCard::class);
    }

    public function activeRateCards()
    {
        return $this->hasMany(\App\Domains\Financial\Models\RateCard::class)
            ->where('is_active', true);
    }

    public function getEffectiveRateCard(string $serviceType = 'standard', ?\Carbon\Carbon $date = null)
    {
        return \App\Domains\Financial\Models\RateCard::findApplicableRate(
            $this->id,
            $serviceType,
            $date
        );
    }

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically create RMM client when Nestogy client is created
        static::created(function ($client) {
            // Check if there's an active RMM integration for this company
            $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', $client->company_id)
                ->where('is_active', true)
                ->first();

            if (! $integration) {
                return; // No integration, skip auto-creation
            }

            try {
                // Get the RMM service
                $serviceFactory = new \App\Domains\Integration\Services\RmmServiceFactory;
                $rmmService = $serviceFactory->make($integration);

                // Create client in RMM system
                $rmmClientData = [
                    'name' => $client->display_name,
                    'description' => 'Auto-created from Nestogy',
                    // Add other fields as needed by TacticalRMM
                ];

                $rmmClient = $rmmService->createClient($rmmClientData);

                if ($rmmClient && isset($rmmClient['id'])) {
                    // Auto-create the mapping
                    \App\Domains\Integration\Models\RmmClientMapping::create([
                        'company_id' => $client->company_id,
                        'client_id' => $client->id,
                        'integration_id' => $integration->id,
                        'rmm_client_id' => (string) $rmmClient['id'],
                        'rmm_client_name' => $rmmClient['name'] ?? $client->display_name,
                        'is_active' => true,
                    ]);

                    Log::info('Auto-created RMM client and mapping', [
                        'nestogy_client_id' => $client->id,
                        'rmm_client_id' => $rmmClient['id'],
                        'integration_id' => $integration->id,
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail client creation
                Log::warning('Failed to auto-create RMM client', [
                    'nestogy_client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
