<?php

namespace App\Plugins\StatusTransitions;

use App\Contracts\StatusTransitionInterface;
use App\Domains\Contract\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * Standard status transition handler
 * Provides basic status transition functionality
 */
class StandardStatusTransition implements StatusTransitionInterface
{
    protected array $config = [];
    protected array $statusWorkflow = [];

    public function getName(): string
    {
        return 'Standard Status Transition';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Standard contract status transition handler with approval workflows';
    }

    public function getAuthor(): string
    {
        return 'Nestogy Core Team';
    }

    public function getConfigurationSchema(): array
    {
        return [
            'workflow' => [
                'type' => 'object',
                'description' => 'Status transition workflow definition',
                'properties' => [
                    'transitions' => [
                        'type' => 'object',
                        'description' => 'Available transitions from each status',
                        'additionalProperties' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'to_status' => ['type' => 'string'],
                                    'required_permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'required_fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    'requires_approval' => ['type' => 'boolean'],
                                    'conditions' => ['type' => 'array'],
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'notifications' => [
                'type' => 'object',
                'description' => 'Notification settings for transitions',
                'properties' => [
                    'enabled' => ['type' => 'boolean', 'default' => true],
                    'channels' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'templates' => ['type' => 'object'],
                ]
            ],
            'audit_logging' => [
                'type' => 'boolean',
                'description' => 'Enable audit logging for transitions',
                'default' => true,
            ],
        ];
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (isset($config['workflow']['transitions'])) {
            foreach ($config['workflow']['transitions'] as $fromStatus => $transitions) {
                foreach ($transitions as $index => $transition) {
                    if (!isset($transition['to_status'])) {
                        $errors[] = "Workflow transition {$fromStatus}[{$index}] missing 'to_status'";
                    }
                }
            }
        }

        return $errors;
    }

    public function initialize(array $config = []): void
    {
        $this->config = array_merge([
            'workflow' => [
                'transitions' => [
                    'draft' => [
                        ['to_status' => 'pending_review', 'required_permissions' => ['submit-contract']],
                        ['to_status' => 'cancelled', 'required_permissions' => ['cancel-contract']],
                    ],
                    'pending_review' => [
                        ['to_status' => 'under_negotiation', 'required_permissions' => ['review-contract']],
                        ['to_status' => 'pending_signature', 'required_permissions' => ['approve-contract']],
                        ['to_status' => 'draft', 'required_permissions' => ['reject-contract']],
                    ],
                    'under_negotiation' => [
                        ['to_status' => 'pending_signature', 'required_permissions' => ['approve-contract']],
                        ['to_status' => 'draft', 'required_permissions' => ['reject-contract']],
                    ],
                    'pending_signature' => [
                        ['to_status' => 'signed', 'required_permissions' => ['sign-contract'], 'required_fields' => ['signed_at']],
                    ],
                    'signed' => [
                        ['to_status' => 'active', 'required_permissions' => ['activate-contract']],
                    ],
                    'active' => [
                        ['to_status' => 'suspended', 'required_permissions' => ['suspend-contract'], 'required_fields' => ['suspension_reason']],
                        ['to_status' => 'terminated', 'required_permissions' => ['terminate-contract'], 'required_fields' => ['termination_reason']],
                    ],
                    'suspended' => [
                        ['to_status' => 'active', 'required_permissions' => ['reactivate-contract']],
                        ['to_status' => 'terminated', 'required_permissions' => ['terminate-contract'], 'required_fields' => ['termination_reason']],
                    ],
                ]
            ],
            'notifications' => [
                'enabled' => true,
                'channels' => ['mail', 'database'],
                'templates' => [],
            ],
            'audit_logging' => true,
        ], $config);

        $this->statusWorkflow = $this->config['workflow']['transitions'];
    }

    public function isCompatible(): bool
    {
        return true;
    }

    public function getRequiredPermissions(): array
    {
        return ['manage-contracts', 'view-contracts'];
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function canTransition(Contract $contract, string $toStatus, User $user, array $context = []): bool
    {
        $currentStatus = $contract->status;
        
        // Get available transitions from current status
        $availableTransitions = $this->statusWorkflow[$currentStatus] ?? [];
        
        // Find the specific transition
        $transition = collect($availableTransitions)->firstWhere('to_status', $toStatus);
        
        if (!$transition) {
            return false;
        }

        // Check permissions
        if (!empty($transition['required_permissions'])) {
            foreach ($transition['required_permissions'] as $permission) {
                if (!$user->can($permission)) {
                    return false;
                }
            }
        }

        // Check conditions
        if (!empty($transition['conditions'])) {
            foreach ($transition['conditions'] as $condition) {
                if (!$this->evaluateCondition($condition, $contract, $user, $context)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function executeTransition(Contract $contract, string $toStatus, User $user, array $context = []): void
    {
        $fromStatus = $contract->status;

        // Execute pre-transition hooks
        $this->beforeTransition($contract, $toStatus, $user, $context);

        // Update contract status
        $contract->update(['status' => $toStatus]);

        // Execute post-transition hooks
        $this->afterTransition($contract, $fromStatus, $toStatus, $user, $context);

        // Send notifications
        if ($this->config['notifications']['enabled']) {
            $this->sendTransitionNotifications($contract, $fromStatus, $toStatus, $user);
        }

        // Log audit trail
        if ($this->config['audit_logging']) {
            $this->logTransition($contract, $fromStatus, $toStatus, $user, $context);
        }

        // Fire events
        Event::dispatch('contract.status.changed', [$contract, $fromStatus, $toStatus, $user]);
    }

    public function getRequiredFields(string $fromStatus, string $toStatus): array
    {
        $transitions = $this->statusWorkflow[$fromStatus] ?? [];
        $transition = collect($transitions)->firstWhere('to_status', $toStatus);
        
        return $transition['required_fields'] ?? [];
    }

    public function getAvailableTransitions(Contract $contract, User $user): array
    {
        $currentStatus = $contract->status;
        $availableTransitions = $this->statusWorkflow[$currentStatus] ?? [];
        
        $allowedTransitions = [];
        
        foreach ($availableTransitions as $transition) {
            if ($this->canTransition($contract, $transition['to_status'], $user)) {
                $allowedTransitions[] = [
                    'to_status' => $transition['to_status'],
                    'label' => $this->getStatusLabel($transition['to_status']),
                    'requires_approval' => $transition['requires_approval'] ?? false,
                    'required_fields' => $transition['required_fields'] ?? [],
                    'ui_config' => $this->getTransitionUiConfig($currentStatus, $transition['to_status']),
                ];
            }
        }
        
        return $allowedTransitions;
    }

    public function validateTransitionData(Contract $contract, string $toStatus, array $data): array
    {
        $errors = [];
        $requiredFields = $this->getRequiredFields($contract->status, $toStatus);
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '{$field}' is required for this transition";
            }
        }
        
        // Additional validation based on transition type
        switch ($toStatus) {
            case 'signed':
                if (!isset($data['signed_at'])) {
                    $errors[] = 'Signature date is required';
                }
                break;
                
            case 'terminated':
                if (!isset($data['termination_reason']) || empty($data['termination_reason'])) {
                    $errors[] = 'Termination reason is required';
                }
                break;
                
            case 'suspended':
                if (!isset($data['suspension_reason']) || empty($data['suspension_reason'])) {
                    $errors[] = 'Suspension reason is required';
                }
                break;
        }
        
        return $errors;
    }

    public function getTransitionConditions(string $fromStatus, string $toStatus): array
    {
        $transitions = $this->statusWorkflow[$fromStatus] ?? [];
        $transition = collect($transitions)->firstWhere('to_status', $toStatus);
        
        return $transition['conditions'] ?? [];
    }

    public function beforeTransition(Contract $contract, string $toStatus, User $user, array $context = []): void
    {
        // Pre-transition logic
        switch ($toStatus) {
            case 'active':
                // Set execution date
                $contract->executed_at = now();
                break;
                
            case 'signed':
                // Set signature information
                $contract->signed_at = $context['signed_at'] ?? now();
                $contract->signed_by = $user->id;
                break;
                
            case 'terminated':
                // Set termination information
                $contract->terminated_at = now();
                $contract->termination_reason = $context['termination_reason'] ?? null;
                break;
        }
    }

    public function afterTransition(Contract $contract, string $fromStatus, string $toStatus, User $user, array $context = []): void
    {
        // Post-transition logic
        switch ($toStatus) {
            case 'active':
                // Start any scheduled processes
                // Generate recurring invoices if needed
                break;
                
            case 'terminated':
                // Stop recurring processes
                // Send termination notifications
                break;
                
            case 'expired':
                // Handle contract expiration
                // Check for renewal opportunities
                break;
        }
    }

    public function getTransitionPermissions(string $fromStatus, string $toStatus): array
    {
        $transitions = $this->statusWorkflow[$fromStatus] ?? [];
        $transition = collect($transitions)->firstWhere('to_status', $toStatus);
        
        return $transition['required_permissions'] ?? [];
    }

    public function getTransitionNotifications(Contract $contract, string $fromStatus, string $toStatus): array
    {
        $notifications = [];
        
        // Standard notifications based on transition
        switch ($toStatus) {
            case 'pending_signature':
                $notifications[] = [
                    'type' => 'contract_ready_for_signature',
                    'recipients' => ['client', 'account_manager'],
                    'template' => 'contract.ready_for_signature',
                ];
                break;
                
            case 'signed':
                $notifications[] = [
                    'type' => 'contract_signed',
                    'recipients' => ['account_manager', 'operations'],
                    'template' => 'contract.signed',
                ];
                break;
                
            case 'active':
                $notifications[] = [
                    'type' => 'contract_activated',
                    'recipients' => ['client', 'account_manager', 'billing'],
                    'template' => 'contract.activated',
                ];
                break;
                
            case 'terminated':
                $notifications[] = [
                    'type' => 'contract_terminated',
                    'recipients' => ['client', 'account_manager'],
                    'template' => 'contract.terminated',
                ];
                break;
        }
        
        return $notifications;
    }

    public function requiresApproval(Contract $contract, string $toStatus, User $user): bool
    {
        $transitions = $this->statusWorkflow[$contract->status] ?? [];
        $transition = collect($transitions)->firstWhere('to_status', $toStatus);
        
        return $transition['requires_approval'] ?? false;
    }

    public function getApprovalWorkflow(Contract $contract, string $toStatus): array
    {
        // Simple approval workflow - could be more complex
        return [
            'approvers' => ['contract_manager', 'account_manager'],
            'approval_type' => 'any', // or 'all'
            'auto_approve_threshold' => null,
        ];
    }

    public function formatTransitionHistory(array $transition): string
    {
        $from = $this->getStatusLabel($transition['from_status']);
        $to = $this->getStatusLabel($transition['to_status']);
        $user = $transition['user']['name'] ?? 'System';
        $date = $transition['created_at'] ?? now();
        
        return "Status changed from {$from} to {$to} by {$user} on {$date}";
    }

    public function getTransitionUiConfig(string $fromStatus, string $toStatus): array
    {
        $config = [
            'button_style' => $this->getButtonStyle($toStatus),
            'confirm_message' => $this->getConfirmMessage($fromStatus, $toStatus),
            'show_modal' => $this->requiresModal($fromStatus, $toStatus),
            'modal_fields' => $this->getModalFields($fromStatus, $toStatus),
        ];
        
        return $config;
    }

    public function supportsBulkTransition(): bool
    {
        return true;
    }

    public function executeBulkTransition(array $contracts, string $toStatus, User $user, array $context = []): array
    {
        $results = [];
        
        foreach ($contracts as $contract) {
            try {
                if ($this->canTransition($contract, $toStatus, $user, $context)) {
                    $this->executeTransition($contract, $toStatus, $user, $context);
                    $results[$contract->id] = ['success' => true];
                } else {
                    $results[$contract->id] = [
                        'success' => false,
                        'error' => 'Transition not allowed'
                    ];
                }
            } catch (\Exception $e) {
                $results[$contract->id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Evaluate condition
     */
    protected function evaluateCondition(array $condition, Contract $contract, User $user, array $context): bool
    {
        switch ($condition['type']) {
            case 'contract_value':
                return $this->evaluateValueCondition($condition, $contract);
                
            case 'user_role':
                return $user->hasRole($condition['value']);
                
            case 'contract_age':
                return $this->evaluateAgeCondition($condition, $contract);
                
            default:
                return true;
        }
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'under_negotiation' => 'Under Negotiation',
            'pending_signature' => 'Pending Signature',
            'signed' => 'Signed',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
        ];
        
        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get button style for transition
     */
    protected function getButtonStyle(string $toStatus): string
    {
        $styles = [
            'active' => 'btn-success',
            'terminated' => 'btn-danger',
            'suspended' => 'btn-warning',
            'signed' => 'btn-primary',
            'cancelled' => 'btn-secondary',
        ];
        
        return $styles[$toStatus] ?? 'btn-outline-primary';
    }

    /**
     * Get confirmation message
     */
    protected function getConfirmMessage(string $fromStatus, string $toStatus): string
    {
        $from = $this->getStatusLabel($fromStatus);
        $to = $this->getStatusLabel($toStatus);
        
        return "Are you sure you want to change the status from {$from} to {$to}?";
    }

    /**
     * Check if transition requires modal
     */
    protected function requiresModal(string $fromStatus, string $toStatus): bool
    {
        $modalRequired = ['terminated', 'suspended', 'signed'];
        return in_array($toStatus, $modalRequired);
    }

    /**
     * Get modal fields for transition
     */
    protected function getModalFields(string $fromStatus, string $toStatus): array
    {
        switch ($toStatus) {
            case 'terminated':
                return [
                    'termination_reason' => [
                        'type' => 'textarea',
                        'label' => 'Termination Reason',
                        'required' => true,
                    ],
                ];
                
            case 'suspended':
                return [
                    'suspension_reason' => [
                        'type' => 'textarea',
                        'label' => 'Suspension Reason',
                        'required' => true,
                    ],
                ];
                
            case 'signed':
                return [
                    'signed_at' => [
                        'type' => 'datetime',
                        'label' => 'Signature Date',
                        'required' => true,
                        'default' => now()->format('Y-m-d H:i:s'),
                    ],
                ];
                
            default:
                return [];
        }
    }

    /**
     * Send transition notifications
     */
    protected function sendTransitionNotifications(Contract $contract, string $fromStatus, string $toStatus, User $user): void
    {
        $notifications = $this->getTransitionNotifications($contract, $fromStatus, $toStatus);
        
        foreach ($notifications as $notification) {
            // Queue notification for processing
            // This would integrate with the notification system
        }
    }

    /**
     * Log transition
     */
    protected function logTransition(Contract $contract, string $fromStatus, string $toStatus, User $user, array $context): void
    {
        // Log to audit trail
        activity()
            ->performedOn($contract)
            ->causedBy($user)
            ->withProperties([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'context' => $context,
            ])
            ->log('Status changed from ' . $this->getStatusLabel($fromStatus) . ' to ' . $this->getStatusLabel($toStatus));
    }

    /**
     * Evaluate value condition
     */
    protected function evaluateValueCondition(array $condition, Contract $contract): bool
    {
        $value = $contract->contract_value;
        $operator = $condition['operator'] ?? '>=';
        $threshold = $condition['value'];
        
        switch ($operator) {
            case '>=':
                return $value >= $threshold;
            case '<=':
                return $value <= $threshold;
            case '>':
                return $value > $threshold;
            case '<':
                return $value < $threshold;
            case '=':
                return $value == $threshold;
            default:
                return true;
        }
    }

    /**
     * Evaluate age condition
     */
    protected function evaluateAgeCondition(array $condition, Contract $contract): bool
    {
        $ageInDays = $contract->created_at->diffInDays(now());
        $threshold = $condition['days'] ?? 0;
        $operator = $condition['operator'] ?? '>=';
        
        switch ($operator) {
            case '>=':
                return $ageInDays >= $threshold;
            case '<=':
                return $ageInDays <= $threshold;
            case '>':
                return $ageInDays > $threshold;
            case '<':
                return $ageInDays < $threshold;
            default:
                return true;
        }
    }
}