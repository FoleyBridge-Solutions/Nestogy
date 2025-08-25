<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractApproval;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * ContractApprovalService
 * 
 * Enterprise contract approval workflow service with multi-level approvals,
 * role-based routing, automatic escalation, and comprehensive audit trails.
 */
class ContractApprovalService
{
    /**
     * Submit contract for approval
     */
    public function submitForApproval(Contract $contract, array $approvalSettings = []): bool
    {
        return DB::transaction(function () use ($contract, $approvalSettings) {
            Log::info('Submitting contract for approval', [
                'contract_id' => $contract->id,
                'current_status' => $contract->status,
                'user_id' => Auth::id()
            ]);

            // Validate contract can be submitted for approval
            $this->validateContractForApproval($contract);

            // Determine approval requirements
            $approvalRequirements = $this->determineApprovalRequirements($contract, $approvalSettings);

            if (empty($approvalRequirements)) {
                // No approval required
                $contract->update([
                    'status' => Contract::STATUS_PENDING_SIGNATURE,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                Log::info('Contract approved automatically (no approval required)', [
                    'contract_id' => $contract->id
                ]);

                return true;
            }

            // Update contract status
            $contract->update(['status' => Contract::STATUS_PENDING_REVIEW]);

            // Create approval records
            foreach ($approvalRequirements as $requirement) {
                ContractApproval::create([
                    'company_id' => $contract->company_id,
                    'contract_id' => $contract->id,
                    'approval_level' => $requirement['level'],
                    'approver_user_id' => $requirement['user_id'],
                    'approver_role' => $requirement['role'],
                    'required_by' => $requirement['required_by'] ?? null,
                    'approval_order' => $requirement['order'],
                    'is_required' => $requirement['is_required'],
                    'status' => 'pending',
                    'submitted_by' => Auth::id(),
                    'submitted_at' => now(),
                ]);
            }

            // Send notifications to first level approvers
            $this->notifyApprovers($contract, 1);

            Log::info('Contract submitted for approval', [
                'contract_id' => $contract->id,
                'approval_levels' => count($approvalRequirements),
                'user_id' => Auth::id()
            ]);

            return true;
        });
    }

    /**
     * Process approval decision
     */
    public function processApproval(
        Contract $contract, 
        string $decision, 
        string $comments = null,
        array $conditions = []
    ): bool {
        return DB::transaction(function () use ($contract, $decision, $comments, $conditions) {
            $user = Auth::user();
            
            Log::info('Processing contract approval decision', [
                'contract_id' => $contract->id,
                'decision' => $decision,
                'user_id' => $user->id,
                'user_role' => $user->role ?? 'unknown'
            ]);

            // Find the pending approval for this user
            $approval = $this->findPendingApprovalForUser($contract, $user);
            
            if (!$approval) {
                throw new \Exception('No pending approval found for this user');
            }

            // Validate user can approve
            $this->validateApprovalPermissions($approval, $user);

            // Process the decision
            switch ($decision) {
                case 'approve':
                    return $this->approveContract($contract, $approval, $comments, $conditions);
                    
                case 'reject':
                    return $this->rejectContract($contract, $approval, $comments);
                    
                case 'request_changes':
                    return $this->requestContractChanges($contract, $approval, $comments, $conditions);
                    
                case 'escalate':
                    return $this->escalateApproval($contract, $approval, $comments);
                    
                default:
                    throw new \Exception("Invalid approval decision: {$decision}");
            }
        });
    }

    /**
     * Check contract approval status and advance workflow
     */
    public function checkApprovalStatus(Contract $contract): array
    {
        $approvals = $contract->approvals()->orderBy('approval_order')->get();
        
        $pending = $approvals->where('status', 'pending')->count();
        $approved = $approvals->where('status', 'approved')->count();
        $rejected = $approvals->where('status', 'rejected')->count();
        $total = $approvals->count();

        $status = [
            'total_approvals' => $total,
            'pending_approvals' => $pending,
            'approved_count' => $approved,
            'rejected_count' => $rejected,
            'current_level' => $this->getCurrentApprovalLevel($approvals),
            'next_approvers' => $this->getNextApprovers($contract),
            'is_fully_approved' => $pending === 0 && $rejected === 0 && $approved === $total,
            'is_rejected' => $rejected > 0,
            'requires_escalation' => $this->requiresEscalation($approvals),
        ];

        // Auto-advance workflow if ready
        if ($status['is_fully_approved'] && $contract->status === Contract::STATUS_PENDING_REVIEW) {
            $this->advanceToNextStage($contract);
            $status['advanced_to_next_stage'] = true;
        }

        return $status;
    }

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovalsForUser(User $user): Collection
    {
        return ContractApproval::with(['contract.client'])
            ->where('company_id', $user->company_id)
            ->where('status', 'pending')
            ->where(function ($query) use ($user) {
                $query->where('approver_user_id', $user->id)
                      ->orWhere('approver_role', $user->role);
            })
            ->orderBy('required_by')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get approval statistics for dashboard
     */
    public function getApprovalStatistics(int $companyId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = ContractApproval::where('company_id', $companyId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $approvals = $query->get();

        return [
            'total_approvals' => $approvals->count(),
            'pending_approvals' => $approvals->where('status', 'pending')->count(),
            'approved_count' => $approvals->where('status', 'approved')->count(),
            'rejected_count' => $approvals->where('status', 'rejected')->count(),
            'average_approval_time' => $this->calculateAverageApprovalTime($approvals),
            'approval_rate' => $approvals->count() > 0 ? 
                ($approvals->where('status', 'approved')->count() / $approvals->count()) * 100 : 0,
            'overdue_approvals' => $this->getOverdueApprovals($companyId)->count(),
        ];
    }

    /**
     * Validate contract can be submitted for approval
     */
    protected function validateContractForApproval(Contract $contract): void
    {
        if (!in_array($contract->status, [Contract::STATUS_DRAFT, Contract::STATUS_UNDER_NEGOTIATION])) {
            throw new \Exception('Contract must be in draft or under negotiation status to submit for approval');
        }

        if (!$contract->contract_value || $contract->contract_value <= 0) {
            throw new \Exception('Contract must have a valid contract value');
        }

        if (!$contract->client_id) {
            throw new \Exception('Contract must be associated with a client');
        }
    }

    /**
     * Determine approval requirements based on contract and company policies
     */
    protected function determineApprovalRequirements(Contract $contract, array $settings = []): array
    {
        $requirements = [];
        $contractValue = $contract->contract_value;
        $contractType = $contract->contract_type;

        // Get approval thresholds from company settings or defaults
        $thresholds = $settings['thresholds'] ?? $this->getDefaultApprovalThresholds();

        $order = 1;

        // Manager approval for contracts over threshold
        if ($contractValue >= ($thresholds['manager'] ?? 5000)) {
            $requirements[] = [
                'level' => 'manager',
                'user_id' => $this->findApproverByRole($contract->company_id, 'manager'),
                'role' => 'manager',
                'order' => $order++,
                'is_required' => true,
                'required_by' => now()->addBusinessDays(2),
            ];
        }

        // Director approval for contracts over higher threshold
        if ($contractValue >= ($thresholds['director'] ?? 25000)) {
            $requirements[] = [
                'level' => 'director',
                'user_id' => $this->findApproverByRole($contract->company_id, 'director'),
                'role' => 'director',
                'order' => $order++,
                'is_required' => true,
                'required_by' => now()->addBusinessDays(3),
            ];
        }

        // Executive approval for very high value contracts
        if ($contractValue >= ($thresholds['executive'] ?? 100000)) {
            $requirements[] = [
                'level' => 'executive',
                'user_id' => $this->findApproverByRole($contract->company_id, 'executive'),
                'role' => 'executive',
                'order' => $order++,
                'is_required' => true,
                'required_by' => now()->addBusinessDays(5),
            ];
        }

        // Legal review for certain contract types
        if (in_array($contractType, ['master_service', 'data_processing', 'international_service'])) {
            $requirements[] = [
                'level' => 'legal',
                'user_id' => $this->findApproverByRole($contract->company_id, 'legal'),
                'role' => 'legal',
                'order' => $order++,
                'is_required' => true,
                'required_by' => now()->addBusinessDays(3),
            ];
        }

        return $requirements;
    }

    /**
     * Approve contract
     */
    protected function approveContract(
        Contract $contract, 
        ContractApproval $approval, 
        string $comments = null,
        array $conditions = []
    ): bool {
        $approval->update([
            'status' => 'approved',
            'approved_at' => now(),
            'comments' => $comments,
            'conditions' => $conditions,
        ]);

        Log::info('Contract approval granted', [
            'contract_id' => $contract->id,
            'approval_id' => $approval->id,
            'level' => $approval->approval_level,
            'user_id' => Auth::id()
        ]);

        // Check if all required approvals are complete
        $approvalStatus = $this->checkApprovalStatus($contract);
        
        if ($approvalStatus['is_fully_approved']) {
            $this->finalizeApproval($contract);
        } else {
            // Notify next level approvers
            $this->notifyApprovers($contract, $approvalStatus['current_level'] + 1);
        }

        return true;
    }

    /**
     * Reject contract
     */
    protected function rejectContract(
        Contract $contract, 
        ContractApproval $approval, 
        string $comments = null
    ): bool {
        $approval->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'comments' => $comments,
        ]);

        $contract->update([
            'status' => Contract::STATUS_UNDER_NEGOTIATION,
        ]);

        Log::info('Contract approval rejected', [
            'contract_id' => $contract->id,
            'approval_id' => $approval->id,
            'level' => $approval->approval_level,
            'user_id' => Auth::id()
        ]);

        // Notify contract creator of rejection
        $this->notifyContractCreator($contract, 'rejected', $comments);

        return true;
    }

    /**
     * Request contract changes
     */
    protected function requestContractChanges(
        Contract $contract, 
        ContractApproval $approval, 
        string $comments = null,
        array $conditions = []
    ): bool {
        $approval->update([
            'status' => 'changes_requested',
            'requested_at' => now(),
            'comments' => $comments,
            'conditions' => $conditions,
        ]);

        $contract->update([
            'status' => Contract::STATUS_UNDER_NEGOTIATION,
        ]);

        Log::info('Contract changes requested', [
            'contract_id' => $contract->id,
            'approval_id' => $approval->id,
            'level' => $approval->approval_level,
            'user_id' => Auth::id()
        ]);

        // Notify contract creator of requested changes
        $this->notifyContractCreator($contract, 'changes_requested', $comments);

        return true;
    }

    /**
     * Escalate approval
     */
    protected function escalateApproval(
        Contract $contract, 
        ContractApproval $approval, 
        string $comments = null
    ): bool {
        // Find next level approver
        $nextLevelApprover = $this->findNextLevelApprover($contract, $approval);
        
        if (!$nextLevelApprover) {
            throw new \Exception('No higher level approver available for escalation');
        }

        $approval->update([
            'status' => 'escalated',
            'escalated_at' => now(),
            'escalated_to' => $nextLevelApprover->id,
            'comments' => $comments,
        ]);

        // Create new approval record for escalated approver
        ContractApproval::create([
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'approval_level' => 'escalated',
            'approver_user_id' => $nextLevelApprover->id,
            'approval_order' => $approval->approval_order,
            'is_required' => true,
            'status' => 'pending',
            'escalated_from' => $approval->id,
            'required_by' => now()->addBusinessDays(1),
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        Log::info('Contract approval escalated', [
            'contract_id' => $contract->id,
            'approval_id' => $approval->id,
            'escalated_to' => $nextLevelApprover->id,
            'user_id' => Auth::id()
        ]);

        return true;
    }

    /**
     * Finalize approval and advance contract to next stage
     */
    protected function finalizeApproval(Contract $contract): void
    {
        $contract->update([
            'status' => Contract::STATUS_PENDING_SIGNATURE,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        Log::info('Contract fully approved', [
            'contract_id' => $contract->id,
            'user_id' => Auth::id()
        ]);

        // Notify relevant parties that contract is ready for signature
        $this->notifyContractCreator($contract, 'approved');
    }

    /**
     * Find pending approval for user
     */
    protected function findPendingApprovalForUser(Contract $contract, User $user): ?ContractApproval
    {
        return $contract->approvals()
            ->where('status', 'pending')
            ->where(function ($query) use ($user) {
                $query->where('approver_user_id', $user->id)
                      ->orWhere('approver_role', $user->role);
            })
            ->orderBy('approval_order')
            ->first();
    }

    /**
     * Validate approval permissions
     */
    protected function validateApprovalPermissions(ContractApproval $approval, User $user): void
    {
        $canApprove = $approval->approver_user_id === $user->id || 
                     $approval->approver_role === $user->role;

        if (!$canApprove) {
            throw new \Exception('User does not have permission to approve this contract');
        }

        if ($approval->required_by && now()->gt($approval->required_by)) {
            Log::warning('Approval processed after deadline', [
                'approval_id' => $approval->id,
                'required_by' => $approval->required_by,
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Helper methods
     */
    protected function getDefaultApprovalThresholds(): array
    {
        return [
            'manager' => 5000,
            'director' => 25000,
            'executive' => 100000,
        ];
    }

    protected function findApproverByRole(int $companyId, string $role): ?int
    {
        $user = User::where('company_id', $companyId)
            ->where('role', $role)
            ->where('is_active', true)
            ->first();

        return $user?->id;
    }

    protected function getCurrentApprovalLevel(Collection $approvals): int
    {
        $pendingApproval = $approvals->where('status', 'pending')->first();
        return $pendingApproval ? $pendingApproval->approval_order : 0;
    }

    protected function getNextApprovers(Contract $contract): Collection
    {
        return $contract->approvals()
            ->where('status', 'pending')
            ->with('approverUser')
            ->orderBy('approval_order')
            ->get();
    }

    protected function requiresEscalation(Collection $approvals): bool
    {
        return $approvals->where('status', 'pending')
            ->where('required_by', '<', now())
            ->isNotEmpty();
    }

    protected function advanceToNextStage(Contract $contract): void
    {
        $this->finalizeApproval($contract);
    }

    protected function calculateAverageApprovalTime(Collection $approvals): ?float
    {
        $completedApprovals = $approvals->whereIn('status', ['approved', 'rejected'])
            ->filter(function ($approval) {
                return $approval->approved_at || $approval->rejected_at;
            });

        if ($completedApprovals->isEmpty()) {
            return null;
        }

        $totalHours = $completedApprovals->sum(function ($approval) {
            $completedAt = $approval->approved_at ?? $approval->rejected_at;
            return $approval->submitted_at->diffInHours($completedAt);
        });

        return $totalHours / $completedApprovals->count();
    }

    protected function getOverdueApprovals(int $companyId): Collection
    {
        return ContractApproval::where('company_id', $companyId)
            ->where('status', 'pending')
            ->where('required_by', '<', now())
            ->get();
    }

    protected function findNextLevelApprover(Contract $contract, ContractApproval $approval): ?User
    {
        // Implementation would find appropriate escalation target
        return User::where('company_id', $contract->company_id)
            ->where('role', 'executive')
            ->first();
    }

    protected function notifyApprovers(Contract $contract, int $level): void
    {
        // Implementation would send notifications to approvers
        Log::info('Approval notifications sent', [
            'contract_id' => $contract->id,
            'level' => $level
        ]);
    }

    protected function notifyContractCreator(Contract $contract, string $action, string $comments = null): void
    {
        // Implementation would notify contract creator
        Log::info('Contract creator notified', [
            'contract_id' => $contract->id,
            'action' => $action
        ]);
    }
}