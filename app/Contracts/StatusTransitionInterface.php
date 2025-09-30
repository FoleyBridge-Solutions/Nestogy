<?php

namespace App\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Models\User;

/**
 * Interface for status transition plugins
 */
interface StatusTransitionInterface extends ContractPluginInterface
{
    /**
     * Check if transition is allowed
     */
    public function canTransition(Contract $contract, string $toStatus, User $user, array $context = []): bool;

    /**
     * Execute status transition
     */
    public function executeTransition(Contract $contract, string $toStatus, User $user, array $context = []): void;

    /**
     * Get required fields for transition
     */
    public function getRequiredFields(string $fromStatus, string $toStatus): array;

    /**
     * Get available transitions from current status
     */
    public function getAvailableTransitions(Contract $contract, User $user): array;

    /**
     * Validate transition data
     */
    public function validateTransitionData(Contract $contract, string $toStatus, array $data): array;

    /**
     * Get transition conditions
     */
    public function getTransitionConditions(string $fromStatus, string $toStatus): array;

    /**
     * Execute pre-transition hooks
     */
    public function beforeTransition(Contract $contract, string $toStatus, User $user, array $context = []): void;

    /**
     * Execute post-transition hooks
     */
    public function afterTransition(Contract $contract, string $fromStatus, string $toStatus, User $user, array $context = []): void;

    /**
     * Get transition permissions required
     */
    public function getTransitionPermissions(string $fromStatus, string $toStatus): array;

    /**
     * Get transition notifications to send
     */
    public function getTransitionNotifications(Contract $contract, string $fromStatus, string $toStatus): array;

    /**
     * Check if transition requires approval
     */
    public function requiresApproval(Contract $contract, string $toStatus, User $user): bool;

    /**
     * Get approval workflow for transition
     */
    public function getApprovalWorkflow(Contract $contract, string $toStatus): array;

    /**
     * Get transition history format
     */
    public function formatTransitionHistory(array $transition): string;

    /**
     * Get transition UI configuration
     */
    public function getTransitionUiConfig(string $fromStatus, string $toStatus): array;

    /**
     * Get bulk transition support
     */
    public function supportsBulkTransition(): bool;

    /**
     * Execute bulk transition
     */
    public function executeBulkTransition(array $contracts, string $toStatus, User $user, array $context = []): array;
}
