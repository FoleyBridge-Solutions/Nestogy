<?php

namespace App\Domains\Product\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'product_id',
        'bundle_id',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'billing_cycle',
        'amount',
        'quantity',
        'next_billing_date',
        'trial_ends_at',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'next_billing_date' => 'datetime',
        'trial_ends_at' => 'datetime',
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'pending_cancellation']);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTrialing(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function daysUntilNextBilling(): int
    {
        if (! $this->next_billing_date) {
            return 0;
        }

        return now()->diffInDays($this->next_billing_date, false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeDueForBilling($query)
    {
        return $query->where('status', 'active')
            ->where('next_billing_date', '<=', now());
    }

    public function scopeTrialing($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }
}
