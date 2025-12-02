<?php

namespace App\Domains\Financial\Models;

use App\Domains\Core\Models\User;
use App\Domains\Ticket\Models\Ticket;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingAuditLog extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'ticket_id',
        'invoice_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Action constants
     */
    const ACTION_INVOICE_GENERATED = 'invoice_generated';
    const ACTION_INVOICE_PREVIEW = 'invoice_preview';
    const ACTION_INVOICE_APPROVED = 'invoice_approved';
    const ACTION_INVOICE_VOIDED = 'invoice_voided';
    const ACTION_SETTINGS_CHANGED = 'settings_changed';
    const ACTION_BULK_PROCESSING = 'bulk_processing';
    const ACTION_DRY_RUN = 'dry_run';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Helper method to create audit log
     */
    public static function logBillingAction(
        string $action,
        ?int $ticketId = null,
        ?int $invoiceId = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'company_id' => auth()->user()->company_id ?? null,
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $ticketId ? 'Ticket' : ($invoiceId ? 'Invoice' : null),
            'entity_id' => $ticketId ?? $invoiceId,
            'ticket_id' => $ticketId,
            'invoice_id' => $invoiceId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
