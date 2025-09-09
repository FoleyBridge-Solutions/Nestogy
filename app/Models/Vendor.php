<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendor Model
 * 
 * Represents vendors/suppliers that provide services or products.
 * Vendors can be client-specific or global templates.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $extension
 * @property string|null $email
 * @property string|null $website
 * @property string|null $hours
 * @property string|null $sla
 * @property string|null $code
 * @property string|null $account_number
 * @property string|null $notes
 * @property bool $template
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $accessed_at
 * @property int|null $client_id
 * @property int|null $template_id
 */
class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'vendors';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'contact_name',
        'phone',
        'extension',
        'email',
        'website',
        'hours',
        'sla',
        'code',
        'account_number',
        'notes',
        'template',
        'client_id',
        'template_id',
        'accessed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'template' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'accessed_at' => 'datetime',
        'client_id' => 'integer',
        'template_id' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Get the client that owns the vendor.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the template vendor this vendor is based on.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'template_id');
    }

    /**
     * Get vendors created from this template.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(Vendor::class, 'template_id');
    }

    /**
     * Get contacts associated with this vendor.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get assets from this vendor.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get tickets related to this vendor.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get expenses from this vendor.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get formatted phone with extension.
     */
    public function getFormattedPhone(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        $phone = $this->phone;
        if ($this->extension) {
            $phone .= ' ext. ' . $this->extension;
        }

        return $phone;
    }

    /**
     * Get formatted hours.
     */
    public function getFormattedHours(): string
    {
        return $this->hours ?: 'Not specified';
    }

    /**
     * Get formatted SLA.
     */
    public function getFormattedSla(): string
    {
        return $this->sla ?: 'Not specified';
    }

    /**
     * Check if this is a template vendor.
     */
    public function isTemplate(): bool
    {
        return $this->template === true;
    }

    /**
     * Check if vendor is archived.
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Check if vendor is global (not client-specific).
     */
    public function isGlobal(): bool
    {
        return is_null($this->client_id);
    }

    /**
     * Check if vendor is client-specific.
     */
    public function isClientSpecific(): bool
    {
        return !is_null($this->client_id);
    }

    /**
     * Check if vendor was created from a template.
     */
    public function isFromTemplate(): bool
    {
        return !is_null($this->template_id);
    }

    /**
     * Get the vendor's display name with code if available.
     */
    public function getDisplayName(): string
    {
        if ($this->code) {
            return $this->name . ' (' . $this->code . ')';
        }

        return $this->name;
    }

    /**
     * Update last accessed timestamp.
     */
    public function updateAccessedAt(): void
    {
        $this->update(['accessed_at' => now()]);
    }

    /**
     * Get contact count for this vendor.
     */
    public function getContactCount(): int
    {
        return $this->contacts()->count();
    }

    /**
     * Get asset count for this vendor.
     */
    public function getAssetCount(): int
    {
        return $this->assets()->count();
    }

    /**
     * Get ticket count for this vendor.
     */
    public function getTicketCount(): int
    {
        return $this->tickets()->count();
    }

    /**
     * Get expense count for this vendor.
     */
    public function getExpenseCount(): int
    {
        return $this->expenses()->count();
    }

    /**
     * Create vendor instance from template.
     */
    public function createInstance(int $clientId, array $overrides = []): self
    {
        if (!$this->isTemplate()) {
            throw new \InvalidArgumentException('Can only create instances from template vendors');
        }

        $data = array_merge($this->toArray(), $overrides, [
            'client_id' => $clientId,
            'template_id' => $this->id,
            'template' => false,
            'id' => null,
            'created_at' => null,
            'updated_at' => null,
        ]);

        return static::create($data);
    }

    /**
     * Scope to get only template vendors.
     */
    public function scopeTemplates($query)
    {
        return $query->where('template', true);
    }

    /**
     * Scope to get only non-template vendors.
     */
    public function scopeInstances($query)
    {
        return $query->where('template', false);
    }

    /**
     * Scope to get only global vendors.
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('client_id');
    }

    /**
     * Scope to get vendors for a specific client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to search vendors.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%')
              ->orWhere('contact_name', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%')
              ->orWhere('code', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope to get recently accessed vendors.
     */
    public function scopeRecentlyAccessed($query, int $days = 30)
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get vendors created from a specific template.
     */
    public function scopeFromTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Get validation rules for vendor creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'extension' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'hours' => 'nullable|string|max:255',
            'sla' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'template' => 'boolean',
            'client_id' => 'nullable|integer|exists:clients,id',
            'template_id' => 'nullable|integer|exists:vendors,id',
        ];
    }

    /**
     * Get validation rules for vendor update.
     */
    public static function getUpdateValidationRules(int $vendorId): array
    {
        return self::getValidationRules();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update accessed_at when vendor is retrieved
        static::retrieved(function ($vendor) {
            if (!$vendor->wasRecentlyCreated) {
                $vendor->updateAccessedAt();
            }
        });

        // Prevent deletion of template vendors that have instances
        static::deleting(function ($vendor) {
            if ($vendor->isTemplate() && $vendor->instances()->exists()) {
                throw new \Exception('Cannot delete template vendor that has instances');
            }
        });
    }
}