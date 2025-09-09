<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\CreditNoteApproval;
use App\Models\User;
use App\Models\Company;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

/**
 * Credit Note Approval Service
 * 
 * Comprehensive approval workflow management including:
 * - Multi-level approval hierarchies
 * - Dynamic approval routing based on amount and risk
 * - Delegation and escalation capabilities
 * - SLA tracking and breach detection
 * - Emergency approval bypasses
 * - Automatic approval rules and conditions
 * - Comprehensive audit trails
 */
class CreditNoteApprovalService
{
    /**
     * Create approval workflow for credit note
     */
    public function createApprovalWorkflow(CreditNote $creditNote): Collection
    {
        $approvals = collect();

        // Determine required approval levels
        $levels = $this->determineApprovalLevels($creditNote);

        // Create approval records for each level
        foreach ($levels as $sequence => $levelData) {
            $approval = $this->createApprovalRecord($creditNote, $levelData, $sequence + 1);
            $approvals->push($approval);
        }

        // Update credit note status
        $creditNote->update([
            'status' => CreditNote::STATUS_PENDING_APPROVAL,
            'approval_workflow' => $levels,
            'requires_executive_approval' => $this->hasExecutiveLevel($levels),
            'requires_finance_review' => $this->hasFinanceLevel($levels),
            'requires_legal_review' => $this->hasLegalLevel($levels)
        ]);

        // Send initial notifications
        $this->sendWorkflowCreationNotifications($creditNote, $approvals);

        Log::info('Credit note approval workflow created', [
            'credit_note_id' => $creditNote->id,
            'approval_levels' => $levels->count(),
            'total_approvals' => $approvals->count()
        ]);

        return $approvals;
    }

    /**
     * Process approval decision
     */
    public function processApprovalDecision(
        CreditNoteApproval $approval,
        string $decision,
        string $comments = null,
        array $evidence = []
    ): bool {
        return DB::transaction(function () use ($approval, $decision, $comments, $evidence) {
            $approver = Auth::user();
            
            // Validate approver permissions
            if (!$this->canApprove($approval, $approver)) {
                throw new Exception('User does not have permission to approve this credit note');
            }

            // Process the decision
            switch ($decision) {
                case 'approve':
                    return $this->processApproval($approval, $comments, $evidence);
                case 'reject':
                    return $this->processRejection($approval, $comments);
                case 'escalate':
                    return $this->processEscalation($approval, $comments);
                case 'delegate':
                    // For delegation, we need a target user from evidence
                    $targetUser = isset($evidence['delegate_to']) ? User::find($evidence['delegate_to']) : null;
                    if (!$targetUser) {
                        throw new Exception('Delegation target user not specified');
                    }
                    return $this->processDelegationDecision($approval, $targetUser, $comments);
                default:
                    throw new Exception('Invalid approval decision');
            }
        });
    }

    /**
     * Auto-approve based on predefined rules
     */
    public function processAutoApprovals(): array
    {
        $results = [
            'processed' => 0,
            'approved' => 0,
            'failed' => 0
        ];

        // Get pending approvals eligible for auto-approval
        $eligibleApprovals = $this->getAutoApprovalEligible();

        foreach ($eligibleApprovals as $approval) {
            try {
                $rules = $this->getAutoApprovalRules($approval);
                
                if ($this->evaluateAutoApprovalRules($approval, $rules)) {
                    $approval->autoApprove($rules, 'Auto-approved based on system rules');
                    $this->checkWorkflowCompletion($approval->creditNote);
                    $results['approved']++;
                }
                
                $results['processed']++;
                
            } catch (Exception $e) {
                Log::error('Auto-approval failed', [
                    'approval_id' => $approval->id,
                    'error' => $e->getMessage()
                ]);
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Process SLA breaches and escalations
     */
    public function processSlaBreaches(): array
    {
        $results = [
            'breached' => 0,
            'escalated' => 0,
            'notifications_sent' => 0
        ];

        // Find overdue approvals
        $overdueApprovals = CreditNoteApproval::where('status', CreditNoteApproval::STATUS_PENDING)
            ->where('sla_deadline', '<', now())
            ->where('sla_breached', false)
            ->get();

        foreach ($overdueApprovals as $approval) {
            // Mark as SLA breached
            $approval->update(['sla_breached' => true]);
            $results['breached']++;

            // Auto-escalate if configured
            if ($this->shouldAutoEscalate($approval)) {
                $escalationTarget = $this->findEscalationTarget($approval);
                
                if ($escalationTarget) {
                    $approval->escalate($escalationTarget, 'SLA breach auto-escalation');
                    $results['escalated']++;
                }
            }

            // Send breach notifications
            $this->sendSlaBreachNotifications($approval);
            $results['notifications_sent']++;
        }

        return $results;
    }

    /**
     * Process delegation decision (private helper)
     */
    private function processDelegationDecision(
        CreditNoteApproval $approval,
        User $delegateTo,
        string $reason = null
    ): bool {
        return $this->processDelegation($approval, $delegateTo, $reason ?? 'Approval delegated');
    }

    /**
     * Process delegation requests
     */
    public function processDelegation(
        CreditNoteApproval $approval,
        User $delegateTo,
        string $reason,
        Carbon $expiry = null
    ): bool {
        if (!$approval->isPending()) {
            return false;
        }

        // Validate delegation permissions
        if (!$this->canDelegate($approval, Auth::user(), $delegateTo)) {
            throw new Exception('Delegation not permitted');
        }

        $success = $approval->delegate($delegateTo, $reason, $expiry);

        if ($success) {
            $this->sendDelegationNotifications($approval, $delegateTo);
            
            Log::info('Approval delegated', [
                'approval_id' => $approval->id,
                'from_user' => Auth::id(),
                'to_user' => $delegateTo->id,
                'reason' => $reason
            ]);
        }

        return $success;
    }

    /**
     * Process emergency bypass
     */
    public function processEmergencyBypass(
        CreditNote $creditNote,
        string $reason,
        User $bypassedBy = null
    ): bool {
        $bypassedBy = $bypassedBy ?? Auth::user();

        // Validate bypass permissions
        if (!$this->canBypass($creditNote, $bypassedBy)) {
            throw new Exception('Emergency bypass not permitted');
        }

        return DB::transaction(function () use ($creditNote, $reason, $bypassedBy) {
            // Mark all pending approvals as bypassed
            $creditNote->approvals()
                ->where('status', CreditNoteApproval::STATUS_PENDING)
                ->update([
                    'status' => CreditNoteApproval::STATUS_BYPASSED,
                    'approval_bypassed' => true,
                    'bypassed_by' => $bypassedBy->id,
                    'bypassed_at' => now(),
                    'bypass_reason' => $reason,
                    'emergency_approval' => true
                ]);

            // Approve the credit note
            $creditNote->approve($bypassedBy, "Emergency bypass: $reason");

            // Send bypass notifications
            $this->sendBypassNotifications($creditNote, $bypassedBy, $reason);

            Log::warning('Emergency approval bypass executed', [
                'credit_note_id' => $creditNote->id,
                'bypassed_by' => $bypassedBy->id,
                'reason' => $reason
            ]);

            return true;
        });
    }

    /**
     * Get approval workflow status
     */
    public function getWorkflowStatus(CreditNote $creditNote): array
    {
        $approvals = $creditNote->approvals()->orderBy('sequence_order')->get();
        
        return [
            'total_levels' => $approvals->count(),
            'completed_levels' => $approvals->where('status', CreditNoteApproval::STATUS_APPROVED)->count(),
            'pending_levels' => $approvals->where('status', CreditNoteApproval::STATUS_PENDING)->count(),
            'rejected_levels' => $approvals->where('status', CreditNoteApproval::STATUS_REJECTED)->count(),
            'current_level' => $this->getCurrentApprovalLevel($approvals),
            'next_approver' => $this->getNextApprover($approvals),
            'sla_status' => $this->getSlaStatus($approvals),
            'overall_status' => $this->getOverallWorkflowStatus($approvals),
            'estimated_completion' => $this->estimateCompletionTime($approvals)
        ];
    }

    /**
     * Bulk approval processing
     */
    public function processBulkApprovals(Collection $approvals, string $decision, string $comments = null): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_processed' => 0
        ];

        foreach ($approvals as $approval) {
            try {
                $success = $this->processApprovalDecision($approval, $decision, $comments);
                
                if ($success) {
                    $results['successful'][] = $approval->id;
                } else {
                    $results['failed'][] = ['id' => $approval->id, 'reason' => 'Processing returned false'];
                }
                
            } catch (Exception $e) {
                $results['failed'][] = [
                    'id' => $approval->id,
                    'reason' => $e->getMessage()
                ];
            }
            
            $results['total_processed']++;
        }

        return $results;
    }

    /**
     * Get approval analytics
     */
    public function getApprovalAnalytics(array $filters = []): array
    {
        $query = CreditNoteApproval::forCompany();

        // Apply filters
        if (isset($filters['date_range'])) {
            $query->whereBetween('requested_at', [
                $filters['date_range']['start'],
                $filters['date_range']['end']
            ]);
        }

        if (isset($filters['approval_level'])) {
            $query->where('approval_level', $filters['approval_level']);
        }

        $approvals = $query->with(['creditNote', 'approver'])->get();

        return [
            'summary' => [
                'total_approvals' => $approvals->count(),
                'approved' => $approvals->where('status', CreditNoteApproval::STATUS_APPROVED)->count(),
                'rejected' => $approvals->where('status', CreditNoteApproval::STATUS_REJECTED)->count(),
                'pending' => $approvals->where('status', CreditNoteApproval::STATUS_PENDING)->count(),
                'average_response_time' => $approvals->avg('response_time_hours'),
                'sla_breach_rate' => $approvals->where('sla_breached', true)->count() / max($approvals->count(), 1) * 100
            ],
            'by_level' => $approvals->groupBy('approval_level')->map->count(),
            'by_approver' => $this->getApproverStatistics($approvals),
            'sla_performance' => $this->getSlaPerformanceMetrics($approvals),
            'escalation_patterns' => $this->getEscalationPatterns($approvals),
            'auto_approval_effectiveness' => $this->getAutoApprovalMetrics($approvals)
        ];
    }

    /**
     * Private helper methods
     */
    private function determineApprovalLevels(CreditNote $creditNote): Collection
    {
        $levels = collect();
        $amount = $creditNote->total_amount;
        $reason = $creditNote->reason_code;
        $clientRisk = $this->assessClientRisk($creditNote->client);

        // Supervisor level (always required for manual credits)
        if ($amount >= 50 || in_array($reason, ['billing_error', 'goodwill'])) {
            $levels->push([
                'level' => CreditNoteApproval::LEVEL_SUPERVISOR,
                'threshold' => 50,
                'approvers' => $this->findApprovers(CreditNoteApproval::LEVEL_SUPERVISOR),
                'sla_hours' => 24,
                'required' => true
            ]);
        }

        // Manager level
        if ($amount >= 500 || $reason === 'billing_error' || $clientRisk === 'high') {
            $levels->push([
                'level' => CreditNoteApproval::LEVEL_MANAGER,
                'threshold' => 500,
                'approvers' => $this->findApprovers(CreditNoteApproval::LEVEL_MANAGER),
                'sla_hours' => 48,
                'required' => true
            ]);
        }

        // Finance Manager level
        if ($amount >= 2000 || in_array($reason, ['billing_error', 'regulatory_adjustment'])) {
            $levels->push([
                'level' => CreditNoteApproval::LEVEL_FINANCE_MANAGER,
                'threshold' => 2000,
                'approvers' => $this->findApprovers(CreditNoteApproval::LEVEL_FINANCE_MANAGER),
                'sla_hours' => 72,
                'required' => true
            ]);
        }

        // Executive level
        if ($amount >= 10000 || $clientRisk === 'critical') {
            $levels->push([
                'level' => CreditNoteApproval::LEVEL_EXECUTIVE,
                'threshold' => 10000,
                'approvers' => $this->findApprovers(CreditNoteApproval::LEVEL_EXECUTIVE),
                'sla_hours' => 96,
                'required' => true
            ]);
        }

        // Legal review for disputes and regulatory issues
        if (in_array($reason, ['chargeback', 'regulatory_adjustment']) || $amount >= 25000) {
            $levels->push([
                'level' => CreditNoteApproval::LEVEL_LEGAL,
                'threshold' => 25000,
                'approvers' => $this->findApprovers(CreditNoteApproval::LEVEL_LEGAL),
                'sla_hours' => 120,
                'required' => true
            ]);
        }

        return $levels;
    }

    private function createApprovalRecord(CreditNote $creditNote, array $levelData, int $sequence): CreditNoteApproval
    {
        $approver = $this->selectApprover($levelData['approvers']);
        
        return CreditNoteApproval::create([
            'company_id' => $creditNote->company_id,
            'credit_note_id' => $creditNote->id,
            'approver_id' => $approver->id,
            'requested_by' => Auth::id(),
            'approval_level' => $levelData['level'],
            'sequence_order' => $sequence,
            'approval_threshold' => $levelData['threshold'],
            'sla_hours' => $levelData['sla_hours'],
            'sla_deadline' => now()->addHours($levelData['sla_hours']),
            'approval_criteria' => [
                'amount_based' => true,
                'reason_based' => in_array($creditNote->reason_code, ['billing_error', 'goodwill']),
                'risk_based' => $this->assessClientRisk($creditNote->client) !== 'low'
            ],
            'requested_at' => now()
        ]);
    }

    private function processApproval(CreditNoteApproval $approval, string $comments = null, array $evidence = []): bool
    {
        $success = $approval->approve($comments, $evidence);
        
        if ($success) {
            $this->checkWorkflowCompletion($approval->creditNote);
        }

        return $success;
    }

    private function processRejection(CreditNoteApproval $approval, string $reason): bool
    {
        $success = $approval->reject($reason);
        
        if ($success) {
            // Reject the entire credit note workflow
            $this->rejectWorkflow($approval->creditNote, $reason);
        }

        return $success;
    }

    private function processEscalation(CreditNoteApproval $approval, string $reason): bool
    {
        $escalationTarget = $this->findEscalationTarget($approval);
        
        if (!$escalationTarget) {
            throw new Exception('No escalation target available');
        }

        return $approval->escalate($escalationTarget, $reason, Auth::user());
    }

    private function checkWorkflowCompletion(CreditNote $creditNote): void
    {
        $pendingApprovals = $creditNote->approvals()
            ->where('status', CreditNoteApproval::STATUS_PENDING)
            ->count();

        if ($pendingApprovals === 0) {
            // All approvals completed - approve the credit note
            $creditNote->approve(Auth::user(), 'All approval levels completed');
            
            Log::info('Credit note approval workflow completed', [
                'credit_note_id' => $creditNote->id
            ]);
        }
    }

    private function rejectWorkflow(CreditNote $creditNote, string $reason): void
    {
        // Mark all pending approvals as cancelled
        $creditNote->approvals()
            ->where('status', CreditNoteApproval::STATUS_PENDING)
            ->update([
                'status' => CreditNoteApproval::STATUS_CANCELLED
            ]);

        // Reject the credit note
        $creditNote->reject(Auth::user(), $reason);
    }

    private function canApprove(CreditNoteApproval $approval, User $user): bool
    {
        return $approval->approver_id === $user->id && $approval->isPending();
    }

    private function canDelegate(CreditNoteApproval $approval, User $from, User $to): bool
    {
        // Check if user can delegate and target user can receive delegation
        return $approval->approver_id === $from->id && 
               $this->hasApprovalRole($to, $approval->approval_level);
    }

    private function canBypass(CreditNote $creditNote, User $user): bool
    {
        // Only executives and above can bypass approvals
        return $user->hasRole(['executive', 'admin', 'cfo']);
    }

    private function getAutoApprovalEligible(): Collection
    {
        return CreditNoteApproval::where('status', CreditNoteApproval::STATUS_PENDING)
            ->whereHas('creditNote', function ($query) {
                $query->where('total_amount', '<', 100); // Configurable threshold
            })
            ->where('approval_level', CreditNoteApproval::LEVEL_SUPERVISOR)
            ->get();
    }

    private function getAutoApprovalRules(CreditNoteApproval $approval): array
    {
        return [
            'max_amount' => 100,
            'allowed_reasons' => ['duplicate_billing', 'system_error'],
            'client_standing' => 'good',
            'frequency_limit' => 5 // per month
        ];
    }

    private function evaluateAutoApprovalRules(CreditNoteApproval $approval, array $rules): bool
    {
        $creditNote = $approval->creditNote;
        
        // Check amount threshold
        if ($creditNote->total_amount > $rules['max_amount']) {
            return false;
        }

        // Check reason codes
        if (!in_array($creditNote->reason_code, $rules['allowed_reasons'])) {
            return false;
        }

        // Check client standing
        if ($this->assessClientRisk($creditNote->client) !== 'low') {
            return false;
        }

        // Check frequency limits
        if ($this->getMonthlyAutoApprovals($creditNote->client_id) >= $rules['frequency_limit']) {
            return false;
        }

        return true;
    }

    private function findApprovers(string $level): Collection
    {
        return User::where('company_id', Auth::user()->company_id)
            ->whereHas('roles', function ($query) use ($level) {
                $query->where('name', $level);
            })
            ->where('status', true)
            ->get();
    }

    private function selectApprover(Collection $approvers): User
    {
        // Simple round-robin selection - could be enhanced with load balancing
        return $approvers->random();
    }

    private function assessClientRisk(Client $client): string
    {
        // Implementation would assess client risk based on various factors
        return 'low';
    }

    private function hasApprovalRole(User $user, string $level): bool
    {
        return $user->hasRole($level);
    }

    private function getCurrentApprovalLevel(Collection $approvals): ?CreditNoteApproval
    {
        return $approvals->where('status', CreditNoteApproval::STATUS_PENDING)->first();
    }

    private function getNextApprover(Collection $approvals): ?User
    {
        $current = $this->getCurrentApprovalLevel($approvals);
        return $current?->approver;
    }

    private function getSlaStatus(Collection $approvals): array
    {
        $pending = $approvals->where('status', CreditNoteApproval::STATUS_PENDING);
        
        return [
            'on_time' => $pending->where('sla_deadline', '>', now())->count(),
            'at_risk' => $pending->where('sla_deadline', '<=', now()->addHours(4))->count(),
            'breached' => $pending->where('sla_breached', true)->count()
        ];
    }

    private function getOverallWorkflowStatus(Collection $approvals): string
    {
        if ($approvals->where('status', CreditNoteApproval::STATUS_REJECTED)->count() > 0) {
            return 'rejected';
        }
        
        if ($approvals->where('status', CreditNoteApproval::STATUS_PENDING)->count() > 0) {
            return 'pending';
        }
        
        return 'completed';
    }

    private function estimateCompletionTime(Collection $approvals): ?Carbon
    {
        $pending = $approvals->where('status', CreditNoteApproval::STATUS_PENDING);
        
        if ($pending->isEmpty()) {
            return null;
        }
        
        return $pending->max('sla_deadline');
    }

    private function shouldAutoEscalate(CreditNoteApproval $approval): bool
    {
        return $approval->sla_breached && 
               in_array($approval->approval_level, [
                   CreditNoteApproval::LEVEL_SUPERVISOR,
                   CreditNoteApproval::LEVEL_MANAGER
               ]);
    }

    private function findEscalationTarget(CreditNoteApproval $approval): ?User
    {
        $nextLevel = $this->getNextEscalationLevel($approval->approval_level);
        
        if (!$nextLevel) {
            return null;
        }
        
        $approvers = $this->findApprovers($nextLevel);
        return $approvers->first();
    }

    private function getNextEscalationLevel(string $currentLevel): ?string
    {
        $hierarchy = [
            CreditNoteApproval::LEVEL_SUPERVISOR => CreditNoteApproval::LEVEL_MANAGER,
            CreditNoteApproval::LEVEL_MANAGER => CreditNoteApproval::LEVEL_FINANCE_MANAGER,
            CreditNoteApproval::LEVEL_FINANCE_MANAGER => CreditNoteApproval::LEVEL_EXECUTIVE,
            CreditNoteApproval::LEVEL_EXECUTIVE => null
        ];
        
        return $hierarchy[$currentLevel] ?? null;
    }

    private function getMonthlyAutoApprovals(int $clientId): int
    {
        return CreditNoteApproval::whereHas('creditNote', function ($query) use ($clientId) {
                $query->where('client_id', $clientId);
            })
            ->where('auto_approved', true)
            ->whereBetween('approved_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    private function getApproverStatistics(Collection $approvals): array
    {
        return $approvals->groupBy('approver_id')
            ->map(function ($group) {
                return [
                    'approver_name' => $group->first()->approver->name,
                    'total_approvals' => $group->count(),
                    'approved' => $group->where('status', CreditNoteApproval::STATUS_APPROVED)->count(),
                    'rejected' => $group->where('status', CreditNoteApproval::STATUS_REJECTED)->count(),
                    'average_response_time' => $group->avg('response_time_hours'),
                    'sla_breach_rate' => $group->where('sla_breached', true)->count() / $group->count() * 100
                ];
            })
            ->sortByDesc('total_approvals')
            ->values()
            ->toArray();
    }

    private function getSlaPerformanceMetrics(Collection $approvals): array
    {
        return [
            'overall_compliance' => $approvals->where('sla_breached', false)->count() / max($approvals->count(), 1) * 100,
            'by_level' => $approvals->groupBy('approval_level')
                ->map(fn($group) => $group->where('sla_breached', false)->count() / $group->count() * 100),
            'average_response_time' => $approvals->avg('response_time_hours'),
            'fastest_approval' => $approvals->min('response_time_hours'),
            'slowest_approval' => $approvals->max('response_time_hours')
        ];
    }

    private function getEscalationPatterns(Collection $approvals): array
    {
        $escalated = $approvals->where('escalated', true);
        
        return [
            'total_escalations' => $escalated->count(),
            'escalation_rate' => $escalated->count() / max($approvals->count(), 1) * 100,
            'by_level' => $escalated->groupBy('approval_level')->map->count(),
            'common_reasons' => $escalated->groupBy('escalation_reason')->map->count()
        ];
    }

    private function getAutoApprovalMetrics(Collection $approvals): array
    {
        $autoApproved = $approvals->where('auto_approved', true);
        
        return [
            'total_auto_approvals' => $autoApproved->count(),
            'auto_approval_rate' => $autoApproved->count() / max($approvals->count(), 1) * 100,
            'average_processing_time' => $autoApproved->avg('response_time_hours'),
            'success_rate' => 100 // Auto-approvals are always successful by definition
        ];
    }

    private function hasExecutiveLevel(Collection $levels): bool
    {
        return $levels->contains('level', CreditNoteApproval::LEVEL_EXECUTIVE);
    }

    private function hasFinanceLevel(Collection $levels): bool
    {
        return $levels->contains('level', CreditNoteApproval::LEVEL_FINANCE_MANAGER);
    }

    private function hasLegalLevel(Collection $levels): bool
    {
        return $levels->contains('level', CreditNoteApproval::LEVEL_LEGAL);
    }

    private function sendWorkflowCreationNotifications(CreditNote $creditNote, Collection $approvals): void
    {
        // Implementation would send notifications to approvers
    }

    private function sendDelegationNotifications(CreditNoteApproval $approval, User $delegateTo): void
    {
        // Implementation would send delegation notifications
    }

    private function sendBypassNotifications(CreditNote $creditNote, User $bypassedBy, string $reason): void
    {
        // Implementation would send bypass notifications
    }

    private function sendSlaBreachNotifications(CreditNoteApproval $approval): void
    {
        // Implementation would send SLA breach notifications
    }
}