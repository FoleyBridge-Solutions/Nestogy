<?php

namespace App\Models\Financial;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContractVersion Model
 * 
 * Manages contract versioning for negotiation and change tracking
 */
class ContractVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'version_number',
        'version_type',
        'status',
        'title',
        'description',
        'change_summary',
        'changes',
        'contract_data',
        'components',
        'pricing_snapshot',
        'approval_status',
        'approvals',
        'rejection_reason',
        'negotiation_id',
        'branch',
        'is_client_visible',
        'is_final',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'contract_data' => 'array',
        'components' => 'array',
        'pricing_snapshot' => 'array',
        'approvals' => 'array',
        'is_client_visible' => 'boolean',
        'is_final' => 'boolean',
        'approved_at' => 'datetime',
    ];

    // Version types
    const TYPE_INITIAL = 'initial';
    const TYPE_REVISION = 'revision';
    const TYPE_AMENDMENT = 'amendment';
    const TYPE_RENEWAL = 'renewal';

    // Version statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEW = 'review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FINAL = 'final';

    // Approval statuses
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Contract::class);
    }

    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(ContractNegotiation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContractComment::class, 'version_id');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopeClientVisible($query)
    {
        return $query->where('is_client_visible', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Helper methods
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function isFinal(): bool
    {
        return $this->is_final === true;
    }

    public function getTotalValue(): float
    {
        $snapshot = $this->pricing_snapshot ?? [];
        return (float) ($snapshot['total_value'] ?? 0);
    }

    public function getComponentCount(): int
    {
        $components = $this->components ?? [];
        return count($components);
    }

    public function getChangeCount(): int
    {
        $changes = $this->changes ?? [];
        return count($changes['items'] ?? []);
    }

    public function hasChanges(): bool
    {
        return $this->getChangeCount() > 0;
    }

    public function approve(\App\Models\User $user, string $notes = null): bool
    {
        $approvals = $this->approvals ?? [];
        $approvals[] = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'approved',
            'notes' => $notes,
            'timestamp' => now()->toISOString()
        ];

        $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approvals' => $approvals,
            'approved_by' => $user->id,
            'approved_at' => now()
        ]);

        return true;
    }

    public function reject(\App\Models\User $user, string $reason): bool
    {
        $approvals = $this->approvals ?? [];
        $approvals[] = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'rejected',
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ];

        $this->update([
            'approval_status' => self::APPROVAL_REJECTED,
            'rejection_reason' => $reason,
            'approvals' => $approvals
        ]);

        return true;
    }

    public function markAsFinal(): bool
    {
        return $this->update([
            'is_final' => true,
            'status' => self::STATUS_FINAL
        ]);
    }

    public function createSnapshot(): array
    {
        return [
            'contract_data' => $this->contract->toArray(),
            'components' => $this->contract->componentAssignments()
                ->with('component')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'component_id' => $assignment->component_id,
                        'component_name' => $assignment->component->name,
                        'configuration' => $assignment->configuration,
                        'pricing_override' => $assignment->pricing_override,
                        'variable_values' => $assignment->variable_values,
                        'calculated_price' => $assignment->calculatePrice()
                    ];
                }),
            'pricing_snapshot' => [
                'total_value' => $this->contract->calculateTotalValue(),
                'component_count' => $this->contract->componentAssignments()->count(),
                'snapshot_date' => now()->toISOString()
            ]
        ];
    }

    public static function getNextVersionNumber(int $contractId, string $branch = null): string
    {
        $query = self::where('contract_id', $contractId);
        
        if ($branch) {
            $query->where('branch', $branch);
        }
        
        $lastVersion = $query->orderBy('version_number', 'desc')->first();
        
        if (!$lastVersion) {
            return 'v1.0';
        }
        
        // Extract version number and increment
        $versionParts = explode('.', str_replace('v', '', $lastVersion->version_number));
        $major = (int) ($versionParts[0] ?? 1);
        $minor = (int) ($versionParts[1] ?? 0);
        
        // Increment minor version
        $minor++;
        
        return "v{$major}.{$minor}";
    }

    public function compareWith(ContractVersion $other): array
    {
        $thisData = $this->contract_data ?? [];
        $otherData = $other->contract_data ?? [];
        
        $changes = [];
        
        // Compare basic contract fields
        foreach (['title', 'value', 'start_date', 'end_date'] as $field) {
            if (($thisData[$field] ?? null) !== ($otherData[$field] ?? null)) {
                $changes[] = [
                    'field' => $field,
                    'old_value' => $otherData[$field] ?? null,
                    'new_value' => $thisData[$field] ?? null,
                    'type' => 'field_change'
                ];
            }
        }
        
        // Compare components
        $thisComponents = collect($this->components ?? []);
        $otherComponents = collect($other->components ?? []);
        
        // Find added components
        $added = $thisComponents->whereNotIn('component_id', $otherComponents->pluck('component_id'));
        foreach ($added as $component) {
            $changes[] = [
                'type' => 'component_added',
                'component_name' => $component['component_name'],
                'component_id' => $component['component_id']
            ];
        }
        
        // Find removed components
        $removed = $otherComponents->whereNotIn('component_id', $thisComponents->pluck('component_id'));
        foreach ($removed as $component) {
            $changes[] = [
                'type' => 'component_removed',
                'component_name' => $component['component_name'],
                'component_id' => $component['component_id']
            ];
        }
        
        return $changes;
    }
}