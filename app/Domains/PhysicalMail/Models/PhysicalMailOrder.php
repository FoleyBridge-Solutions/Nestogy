<?php

namespace App\Domains\PhysicalMail\Models;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PhysicalMailOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'physical_mail_orders';

    protected $fillable = [
        'company_id',
        'client_id',
        'mailable_type',
        'mailable_id',
        'postgrid_id',
        'status',
        'imb_status',
        'imb_date',
        'imb_zip_code',
        'tracking_number',
        'mailing_class',
        'send_date',
        'cost',
        'pdf_url',
        'metadata',
        'created_by',
        'latitude',
        'longitude',
        'formatted_address',
        'to_address_line1',
        'to_address_line2',
        'to_city',
        'to_state',
        'to_postal_code',
        'to_country',
    ];

    protected $casts = [
        'metadata' => 'array',
        'send_date' => 'datetime',
        'imb_date' => 'datetime',
        'cost' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the mailable model (letter, postcard, etc.)
     */
    public function mailable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the company for this order
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the client for this order
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created this order
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for createdBy relationship for backward compatibility
     */
    public function user(): BelongsTo
    {
        return $this->createdBy();
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'ready']);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order has tracking
     */
    public function hasTracking(): bool
    {
        return ! empty($this->tracking_number) || ! empty($this->imb_status);
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'ready' => 'Ready to Print',
            'printing' => 'Printing',
            'processed_for_delivery' => 'Processed for Delivery',
            'completed' => 'Delivered',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope for orders by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
