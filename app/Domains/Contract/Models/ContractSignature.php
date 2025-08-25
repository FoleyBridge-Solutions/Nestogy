<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use App\Domains\Contract\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ContractSignature Model
 * 
 * Digital signature management with multi-provider support,
 * legal compliance tracking, and audit trail maintenance.
 * 
 * @property int $id
 * @property int $contract_id
 * @property int $company_id
 * @property string $signatory_type
 * @property string $signatory_name
 * @property string $signatory_email
 * @property string|null $signatory_title
 * @property string|null $signatory_company
 * @property string $signature_type
 * @property string $status
 * @property string|null $signature_data
 * @property string|null $signature_hash
 * @property string|null $provider_reference_id
 * @property string|null $provider
 * @property array|null $provider_metadata
 * @property string|null $envelope_id
 * @property string|null $recipient_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $location
 * @property array|null $biometric_data
 * @property string|null $verification_code
 * @property bool $identity_verified
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $decline_reason
 * @property string|null $void_reason
 * @property \Illuminate\Support\Carbon|null $last_reminder_sent
 * @property int $reminder_count
 * @property array|null $notification_settings
 * @property bool $legally_binding
 * @property string|null $compliance_standard
 * @property array|null $audit_trail
 * @property string|null $certificate_id
 * @property int $signing_order
 * @property bool $is_required
 * @property array|null $required_fields
 * @property array|null $custom_fields
 * @property int|null $created_by
 * @property int|null $processed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ContractSignature extends Model
{
    use HasFactory, BelongsToCompany, HasAuditTrail;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_signatures';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contract_id',
        'company_id',
        'signatory_type',
        'signatory_name',
        'signatory_email',
        'signatory_title',
        'signatory_company',
        'signature_type',
        'status',
        'signature_data',
        'signature_hash',
        'provider_reference_id',
        'provider',
        'provider_metadata',
        'envelope_id',
        'recipient_id',
        'ip_address',
        'user_agent',
        'location',
        'biometric_data',
        'verification_code',
        'identity_verified',
        'sent_at',
        'viewed_at',
        'signed_at',
        'declined_at',
        'voided_at',
        'expires_at',
        'decline_reason',
        'void_reason',
        'last_reminder_sent',
        'reminder_count',
        'notification_settings',
        'legally_binding',
        'compliance_standard',
        'audit_trail',
        'certificate_id',
        'signing_order',
        'is_required',
        'required_fields',
        'custom_fields',
        'created_by',
        'processed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'contract_id' => 'integer',
        'company_id' => 'integer',
        'provider_metadata' => 'array',
        'biometric_data' => 'array',
        'identity_verified' => 'boolean',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'declined_at' => 'datetime',
        'voided_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_reminder_sent' => 'datetime',
        'reminder_count' => 'integer',
        'notification_settings' => 'array',
        'legally_binding' => 'boolean',
        'audit_trail' => 'array',
        'signing_order' => 'integer',
        'is_required' => 'boolean',
        'required_fields' => 'array',
        'custom_fields' => 'array',
        'created_by' => 'integer',
        'processed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Signatory type enumeration
     */
    const TYPE_CLIENT = 'client';
    const TYPE_COMPANY = 'company';
    const TYPE_WITNESS = 'witness';
    const TYPE_NOTARY = 'notary';

    /**
     * Signature type enumeration
     */
    const SIGNATURE_ELECTRONIC = 'electronic';
    const SIGNATURE_DIGITAL = 'digital';
    const SIGNATURE_WET = 'wet';
    const SIGNATURE_DOCUSIGN = 'docusign';
    const SIGNATURE_HELLOSIGN = 'hellosign';
    const SIGNATURE_ADOBE_SIGN = 'adobe_sign';

    /**
     * Status enumeration
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_VIEWED = 'viewed';
    const STATUS_SIGNED = 'signed';
    const STATUS_DECLINED = 'declined';
    const STATUS_EXPIRED = 'expired';
    const STATUS_VOIDED = 'voided';

    /**
     * Provider enumeration
     */
    const PROVIDER_DOCUSIGN = 'docusign';
    const PROVIDER_HELLOSIGN = 'hellosign';
    const PROVIDER_ADOBE_SIGN = 'adobe_sign';
    const PROVIDER_INTERNAL = 'internal';

    /**
     * Get the contract this signature belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who created this signature.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who processed this signature.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Check if signature is signed.
     */
    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    /**
     * Check if signature is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_VIEWED]);
    }

    /**
     * Check if signature is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return Carbon::now()->gt($this->expires_at) && !$this->isSigned();
    }

    /**
     * Check if signature is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if signature is voided.
     */
    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Check if signature is required.
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Send signature request.
     */
    public function send(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->updateWithTimestampAndAudit(
            ['status' => self::STATUS_SENT],
            'sent_at',
            'signature_sent',
            ['sent_by' => auth()->id()]
        );

        return true;
    }

    /**
     * Mark as viewed.
     */
    public function markAsViewed(?string $ipAddress = null, ?string $userAgent = null): void
    {
        if ($this->status === self::STATUS_SENT) {
            $this->update([
                'status' => self::STATUS_VIEWED,
                'viewed_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            $this->addToAuditTrail('signature_viewed', [
                'viewed_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }
    }

    /**
     * Sign the signature.
     */
    public function sign(array $signatureData = []): bool
    {
        if (!in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED])) {
            return false;
        }

        $updateData = array_merge(
            ['status' => self::STATUS_SIGNED],
            array_intersect_key($signatureData, array_flip([
                'signature_data', 'signature_hash', 'ip_address', 
                'user_agent', 'location', 'biometric_data'
            ]))
        );

        $this->updateWithTimestampAndAudit(
            $updateData,
            'signed_at',
            'signature_signed',
            $signatureData
        );

        return true;
    }

    /**
     * Decline the signature.
     */
    public function decline(?string $reason = null): bool
    {
        if (!in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED])) {
            return false;
        }

        $this->updateWithTimestampAndAudit(
            [
                'status' => self::STATUS_DECLINED,
                'decline_reason' => $reason,
            ],
            'declined_at',
            'signature_declined',
            ['reason' => $reason]
        );

        return true;
    }

    /**
     * Void the signature.
     */
    public function void(?string $reason = null): bool
    {
        $this->updateWithTimestampAndAudit(
            [
                'status' => self::STATUS_VOIDED,
                'void_reason' => $reason,
            ],
            'voided_at',
            'signature_voided',
            [
                'reason' => $reason,
                'voided_by' => auth()->id(),
            ]
        );

        return true;
    }

    /**
     * Send reminder.
     */
    public function sendReminder(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'last_reminder_sent' => now(),
            'reminder_count' => $this->reminder_count + 1,
        ]);

        $this->addToAuditTrail('reminder_sent', [
            'sent_at' => now(),
            'reminder_number' => $this->reminder_count,
        ]);

        return true;
    }

    /**
     * Verify identity.
     */
    public function verifyIdentity(array $verificationData): bool
    {
        $this->update([
            'identity_verified' => true,
            'verification_code' => $verificationData['code'] ?? null,
            'provider_metadata' => $this->mergeProviderMetadata([
                'identity_verification' => $verificationData,
                'verified_at' => now(),
            ]),
        ]);

        $this->addToAuditTrail('identity_verified', $verificationData);

        return true;
    }

    /**
     * Update provider status.
     */
    public function updateProviderStatus(array $providerData): void
    {
        $updateData = array_intersect_key($providerData, array_flip([
            'status', 'provider_reference_id', 'envelope_id', 'recipient_id'
        ]));
        
        $updateData['provider_metadata'] = $this->mergeProviderMetadata($providerData);

        $this->update($updateData);
        $this->addToAuditTrail('provider_status_updated', $providerData);
    }

    /**
     * Merge provider metadata with existing data
     */
    protected function mergeProviderMetadata(array $newData): array
    {
        return array_merge($this->provider_metadata ?? [], $newData);
    }
    
    // Audit trail methods moved to HasAuditTrail trait

    /**
     * Get signature URL for external providers.
     */
    public function getSignatureUrl(): ?string
    {
        if (!$this->provider_reference_id) {
            return null;
        }

        switch ($this->provider) {
            case self::PROVIDER_DOCUSIGN:
                return $this->provider_metadata['signing_url'] ?? null;
            case self::PROVIDER_HELLOSIGN:
                return $this->provider_metadata['sign_url'] ?? null;
            case self::PROVIDER_ADOBE_SIGN:
                return $this->provider_metadata['signing_url'] ?? null;
            default:
                return route('contracts.sign', ['signature' => $this->id]);
        }
    }

    /**
     * Get time until expiration.
     */
    public function getTimeUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, Carbon::now()->diffInHours($this->expires_at, false));
    }

    /**
     * Get formatted signatory info.
     */
    public function getFormattedSignatory(): string
    {
        $info = $this->signatory_name;
        
        if ($this->signatory_title) {
            $info .= ', ' . $this->signatory_title;
        }
        
        if ($this->signatory_company) {
            $info .= ' (' . $this->signatory_company . ')';
        }

        return $info;
    }

    /**
     * Scope to get signatures by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending signatures.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_VIEWED]);
    }

    /**
     * Scope to get signed signatures.
     */
    public function scopeSigned($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    /**
     * Scope to get expired signatures.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->whereNotIn('status', [self::STATUS_SIGNED, self::STATUS_DECLINED, self::STATUS_VOIDED]);
    }

    /**
     * Scope to get signatures requiring reminders.
     */
    public function scopeNeedsReminder($query, int $daysSinceSent = 3)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_VIEWED])
                    ->where(function ($q) use ($daysSinceSent) {
                        $q->whereNull('last_reminder_sent')
                          ->where('sent_at', '<', now()->subDays($daysSinceSent));
                    })
                    ->orWhere(function ($q) use ($daysSinceSent) {
                        $q->where('last_reminder_sent', '<', now()->subDays($daysSinceSent));
                    });
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set defaults when creating
        static::creating(function ($signature) {
            if (!$signature->status) {
                $signature->status = self::STATUS_PENDING;
            }

            if (!$signature->signing_order) {
                $signature->signing_order = 1;
            }

            if (!$signature->legally_binding) {
                $signature->legally_binding = true;
            }

            if (!$signature->expires_at) {
                $signature->expires_at = now()->addDays(30);
            }

            // Initialize audit trail
            $signature->audit_trail = [[
                'action' => 'signature_created',
                'timestamp' => now(),
                'user_id' => auth()->id(),
                'data' => [
                    'signatory_type' => $signature->signatory_type,
                    'signatory_name' => $signature->signatory_name,
                    'signatory_email' => $signature->signatory_email,
                ],
            ]];
        });

        // Update contract signature status when signature status changes
        static::updated(function ($signature) {
            if ($signature->isDirty('status')) {
                $signature->contract->updateSignatureStatus();
            }
        });
    }
}