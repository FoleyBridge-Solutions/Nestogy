<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    use BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'contract_id',
        'signer_type',
        'signer_role',
        'signer_name',
        'signer_email',
        'signer_title',
        'signer_company',
        'status',
        'signed_at',
        'signature_method',
        'signature_data',
        'signature_hash',
        'ip_address',
        'user_agent',
        'verification_code',
        'verification_sent_at',
        'verified_at',
        'document_version',
        'document_hash',
        'consent_to_electronic_signature',
        'consent_given_at',
        'additional_terms_accepted',
        'invitation_sent_at',
        'last_reminder_sent_at',
        'reminder_count',
        'expires_at',
        'decline_reason',
        'declined_at',
        'audit_trail',
        'signing_order',
        'requires_previous_signatures',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'audit_trail' => 'array',
        'consent_to_electronic_signature' => 'boolean',
        'requires_previous_signatures' => 'boolean',
        'signed_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'consent_given_at' => 'datetime',
        'invitation_sent_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    /**
     * Get the contract that owns the signature.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Scope a query to only include pending signatures.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include signed signatures.
     */
    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    /**
     * Check if the signature has been completed.
     */
    public function isSigned(): bool
    {
        return $this->status === 'signed' && $this->signed_at !== null;
    }

    /**
     * Check if the signature has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the signature was declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }
}