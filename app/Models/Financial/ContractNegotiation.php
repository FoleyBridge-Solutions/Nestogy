<?php

namespace App\Models\Financial;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ContractNegotiation Model
 * 
 * Manages contract negotiation processes and workflow
 */
class ContractNegotiation extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'contract_id',
        'client_id',
        'quote_id',
        'negotiation_number',
        'title',
        'description',
        'status',
        'phase',
        'round',
        'started_at',
        'deadline',
        'completed_at',
        'last_activity_at',
        'internal_participants',
        'client_participants',
        'permissions',
        'objectives',
        'constraints',
        'competitive_context',
        'current_version_id',
        'pricing_history',
        'target_value',
        'minimum_value',
        'final_value',
        'duration_days',
        'won',
        'outcome_notes',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'internal_participants' => 'array',
        'client_participants' => 'array',
        'permissions' => 'array',
        'objectives' => 'array',
        'constraints' => 'array',
        'competitive_context' => 'array',
        'pricing_history' => 'array',
        'target_value' => 'decimal:2',
        'minimum_value' => 'decimal:2',
        'final_value' => 'decimal:2',
        'won' => 'boolean',
    ];

    // Negotiation statuses
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Negotiation phases
    const PHASE_PREPARATION = 'preparation';
    const PHASE_PROPOSAL = 'proposal';
    const PHASE_NEGOTIATION = 'negotiation';
    const PHASE_APPROVAL = 'approval';
    const PHASE_FINALIZATION = 'finalization';

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Contract::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Quote::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'current_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class, 'negotiation_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContractComment::class, 'negotiation_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByPhase($query, $phase)
    {
        return $query->where('phase', $phase);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Helper methods
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->isCompleted();
    }

    public function getDurationInDays(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endDate = $this->completed_at ?? now();
        return $this->started_at->diffInDays($endDate);
    }

    public function getParticipants(): array
    {
        $internal = $this->internal_participants ?? [];
        $client = $this->client_participants ?? [];
        
        return [
            'internal' => $internal,
            'client' => $client,
            'total_count' => count($internal) + count($client)
        ];
    }

    public function addParticipant(int $userId, string $type = 'internal', array $permissions = []): bool
    {
        $field = $type === 'internal' ? 'internal_participants' : 'client_participants';
        $participants = $this->{$field} ?? [];
        
        // Check if already a participant
        $existingIndex = collect($participants)->search(function ($p) use ($userId) {
            return $p['user_id'] === $userId;
        });
        
        if ($existingIndex !== false) {
            return false; // Already a participant
        }
        
        $participants[] = [
            'user_id' => $userId,
            'added_at' => now()->toISOString(),
            'permissions' => $permissions
        ];
        
        return $this->update([$field => $participants]);
    }

    public function removeParticipant(int $userId, string $type = 'internal'): bool
    {
        $field = $type === 'internal' ? 'internal_participants' : 'client_participants';
        $participants = $this->{$field} ?? [];
        
        $filtered = collect($participants)->filter(function ($p) use ($userId) {
            return $p['user_id'] !== $userId;
        })->values()->toArray();
        
        return $this->update([$field => $filtered]);
    }

    public function advancePhase(): bool
    {
        $phases = [
            self::PHASE_PREPARATION => self::PHASE_PROPOSAL,
            self::PHASE_PROPOSAL => self::PHASE_NEGOTIATION,
            self::PHASE_NEGOTIATION => self::PHASE_APPROVAL,
            self::PHASE_APPROVAL => self::PHASE_FINALIZATION,
        ];
        
        $nextPhase = $phases[$this->phase] ?? null;
        if (!$nextPhase) {
            return false;
        }
        
        return $this->update([
            'phase' => $nextPhase,
            'last_activity_at' => now()
        ]);
    }

    public function complete(bool $won = true, string $notes = null): bool
    {
        $updates = [
            'status' => self::STATUS_COMPLETED,
            'phase' => self::PHASE_FINALIZATION,
            'completed_at' => now(),
            'duration_days' => $this->getDurationInDays(),
            'won' => $won,
            'last_activity_at' => now()
        ];
        
        if ($notes) {
            $updates['outcome_notes'] = $notes;
        }
        
        if ($won && $this->currentVersion) {
            $updates['final_value'] = $this->currentVersion->getTotalValue();
        }
        
        return $this->update($updates);
    }

    public function pause(string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAUSED,
            'outcome_notes' => $reason,
            'last_activity_at' => now()
        ]);
    }

    public function resume(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'last_activity_at' => now()
        ]);
    }

    public function recordPricingChange(float $newValue, string $reason = null): void
    {
        $history = $this->pricing_history ?? [];
        $history[] = [
            'value' => $newValue,
            'reason' => $reason,
            'round' => $this->round,
            'recorded_at' => now()->toISOString(),
            'recorded_by' => auth()->id()
        ];
        
        $this->update(['pricing_history' => $history]);
    }

    public function incrementRound(): bool
    {
        return $this->update([
            'round' => $this->round + 1,
            'last_activity_at' => now()
        ]);
    }

    public function updateActivity(): bool
    {
        return $this->update(['last_activity_at' => now()]);
    }

    public function getProgressPercentage(): int
    {
        $phases = [
            self::PHASE_PREPARATION => 20,
            self::PHASE_PROPOSAL => 40,
            self::PHASE_NEGOTIATION => 60,
            self::PHASE_APPROVAL => 80,
            self::PHASE_FINALIZATION => 100,
        ];
        
        return $phases[$this->phase] ?? 0;
    }

    public function canUserEdit(\App\Models\User $user): bool
    {
        // Creator and assignee can always edit
        if ($this->created_by === $user->id || $this->assigned_to === $user->id) {
            return true;
        }
        
        // Check participant permissions
        $participants = $this->internal_participants ?? [];
        $participant = collect($participants)->firstWhere('user_id', $user->id);
        
        if ($participant) {
            $permissions = $participant['permissions'] ?? [];
            return in_array('edit', $permissions);
        }
        
        return false;
    }

    public static function generateNegotiationNumber(): string
    {
        $prefix = 'NEG';
        $year = date('Y');
        $month = date('m');
        
        // Find the next sequential number for this month
        $lastNegotiation = self::where('negotiation_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('negotiation_number', 'desc')
            ->first();
        
        if ($lastNegotiation) {
            $lastNumber = (int) substr($lastNegotiation->negotiation_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }
}