<?php

namespace App\Traits;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * HasApprovalWorkflows Trait
 *
 * Provides approval workflow functionality with comprehensive permission checks.
 * Handles financial approvals, expense workflows, and multi-level authorization.
 */
trait HasApprovalWorkflows
{
    /**
     * Authorize expense approval workflow.
     */
    protected function authorizeExpenseApproval(string $action, $expense = null, array $context = []): void
    {
        $user = auth()->user();

        // Check base approval permission
        if (! $user->hasPermission('financial.expenses.approve')) {
            $this->logApprovalAttempt('expense', $action, 'denied', 'Missing base approval permission');
            abort(403, 'Insufficient permissions to approve expenses');
        }

        // Check workflow gates
        if (! Gate::allows('approve-expenses')) {
            abort(403, 'Expense approval workflow permissions denied');
        }

        // Apply amount-based approval limits
        if ($expense && $action === 'approve') {
            $this->checkExpenseApprovalLimits($expense, $user);
        }

        // Check approval hierarchy
        if ($expense && ! $this->checkApprovalHierarchy($expense, $user)) {
            abort(403, 'Insufficient approval authority for this expense amount');
        }

        $this->logApprovalAttempt('expense', $action, 'authorized', 'Approval authorized successfully', $expense);
    }

    /**
     * Authorize payment approval workflow.
     */
    protected function authorizePaymentApproval(string $action, $payment = null, array $context = []): void
    {
        $user = auth()->user();

        // Check payment management permission
        if (! $user->hasPermission('financial.payments.manage')) {
            abort(403, 'Insufficient permissions to manage payments');
        }

        // Check approval gates
        if (! Gate::allows('approve-payments')) {
            abort(403, 'Payment approval workflow permissions denied');
        }

        // Apply payment-specific checks
        if ($payment && $action === 'approve') {
            $this->checkPaymentApprovalRequirements($payment, $user);
        }

        $this->logApprovalAttempt('payment', $action, 'authorized', 'Payment approval authorized', $payment);
    }

    /**
     * Authorize budget approval workflow.
     */
    protected function authorizeBudgetApproval(string $action, $budget = null, array $context = []): void
    {
        $user = auth()->user();

        // Budget approval requires elevated permissions
        if (! $user->hasAnyPermission(['financial.manage', 'system.settings.manage'])) {
            abort(403, 'Insufficient permissions for budget approval');
        }

        if (! Gate::allows('approve-budgets')) {
            abort(403, 'Budget approval workflow permissions denied');
        }

        // Only admins and senior financial managers can approve budgets
        if (! $user->isAdmin() && ! $this->isSeniorFinancialManager($user)) {
            abort(403, 'Senior financial management or admin access required for budget approval');
        }

        $this->logApprovalAttempt('budget', $action, 'authorized', 'Budget approval authorized', $budget);
    }

    /**
     * Authorize invoice approval workflow.
     */
    protected function authorizeInvoiceApproval(string $action, $invoice = null, array $context = []): void
    {
        $user = auth()->user();

        if (! $user->hasPermission('financial.invoices.manage')) {
            abort(403, 'Insufficient permissions to manage invoices');
        }

        // Invoice approval checks
        if ($invoice && $action === 'approve') {
            $this->checkInvoiceApprovalRequirements($invoice, $user);
        }

        $this->logApprovalAttempt('invoice', $action, 'authorized', 'Invoice approval authorized', $invoice);
    }

    /**
     * Check expense approval limits based on amount and user role.
     */
    protected function checkExpenseApprovalLimits($expense, $user): void
    {
        $amount = $expense->amount ?? 0;
        $limits = $this->getApprovalLimits($user);

        if ($amount > $limits['expense_limit']) {
            $this->logApprovalAttempt('expense', 'approve', 'denied',
                "Amount ${amount} exceeds user approval limit of $${limits['expense_limit']}", $expense);

            abort(403, "Expense amount exceeds your approval limit of $${limits['expense_limit']}. ".
                      'This expense requires approval from a higher authority.');
        }

        // Check if expense requires multiple approvals
        if ($amount > $limits['multi_approval_threshold'] && ! $this->hasRequiredApprovals($expense)) {
            abort(403, 'This expense requires multiple approvals before processing');
        }
    }

    /**
     * Check approval hierarchy for expenses.
     */
    protected function checkApprovalHierarchy($expense, $user): bool
    {
        $amount = $expense->amount ?? 0;
        $userLevel = $user->getRoleLevel();

        // Define approval hierarchy
        $approvalHierarchy = [
            1 => 1000,    // Accountant: up to $1,000
            2 => 5000,    // Technician: up to $5,000
            3 => 50000,   // Admin: up to $50,000
        ];

        $maxAmount = $approvalHierarchy[$userLevel] ?? 0;

        return $amount <= $maxAmount;
    }

    /**
     * Check payment approval requirements.
     */
    protected function checkPaymentApprovalRequirements($payment, $user): void
    {
        $amount = $payment->amount ?? 0;
        $limits = $this->getApprovalLimits($user);

        if ($amount > $limits['payment_limit']) {
            abort(403, "Payment amount exceeds your approval limit of $${limits['payment_limit']}");
        }

        // Check payment method restrictions
        if (in_array($payment->method, ['wire_transfer', 'check']) && ! $user->hasPermission('financial.manage')) {
            abort(403, 'Elevated permissions required for wire transfer and check payments');
        }

        // Check for suspicious payment patterns
        if ($this->detectSuspiciousPaymentPattern($payment, $user)) {
            abort(403, 'Payment flagged for review. Please contact a financial administrator.');
        }
    }

    /**
     * Check invoice approval requirements.
     */
    protected function checkInvoiceApprovalRequirements($invoice, $user): void
    {
        $amount = $invoice->total ?? 0;
        $limits = $this->getApprovalLimits($user);

        if ($amount > $limits['invoice_limit']) {
            abort(403, "Invoice amount exceeds your approval limit of $${limits['invoice_limit']}");
        }

        // Check invoice aging
        if ($invoice->created_at && $invoice->created_at->diffInDays(now()) > 30) {
            if (! $user->hasPermission('financial.manage')) {
                abort(403, 'Elevated permissions required to approve aged invoices');
            }
        }
    }

    /**
     * Get approval limits based on user role and permissions.
     */
    protected function getApprovalLimits($user): array
    {
        $baseLimit = 500; // Default base limit

        if ($user->isAdmin()) {
            return [
                'expense_limit' => 50000,
                'payment_limit' => 25000,
                'invoice_limit' => 100000,
                'multi_approval_threshold' => 10000,
            ];
        }

        if ($user->hasPermission('financial.manage')) {
            return [
                'expense_limit' => 10000,
                'payment_limit' => 5000,
                'invoice_limit' => 25000,
                'multi_approval_threshold' => 5000,
            ];
        }

        if ($user->hasPermission('financial.expenses.approve')) {
            return [
                'expense_limit' => 2500,
                'payment_limit' => 1000,
                'invoice_limit' => 5000,
                'multi_approval_threshold' => 2000,
            ];
        }

        return [
            'expense_limit' => $baseLimit,
            'payment_limit' => $baseLimit,
            'invoice_limit' => $baseLimit * 2,
            'multi_approval_threshold' => $baseLimit * 2,
        ];
    }

    /**
     * Check if user is a senior financial manager.
     */
    protected function isSeniorFinancialManager($user): bool
    {
        return $user->hasPermission('financial.manage') &&
               $user->getRoleLevel() >= 2 && // At least technician level
               $user->hasAnyPermission(['financial.expenses.approve', 'reports.financial']);
    }

    /**
     * Check if expense has required approvals for high amounts.
     */
    protected function hasRequiredApprovals($expense): bool
    {
        // This would check if expense has been pre-approved by required personnel
        // Implementation would depend on your approval tracking system
        if (method_exists($expense, 'approvals')) {
            $requiredApprovals = $this->getRequiredApprovalCount($expense->amount);

            return $expense->approvals()->count() >= $requiredApprovals;
        }

        return false;
    }

    /**
     * Get required approval count based on amount.
     */
    protected function getRequiredApprovalCount($amount): int
    {
        if ($amount > 10000) {
            return 2;
        }
        if ($amount > 5000) {
            return 1;
        }

        return 0;
    }

    /**
     * Detect suspicious payment patterns.
     */
    protected function detectSuspiciousPaymentPattern($payment, $user): bool
    {
        // Check for rapid successive payments
        $recentPayments = $this->getRecentPaymentsByUser($user->id, 1); // Last 1 hour

        if ($recentPayments > 5) {
            return true;
        }

        // Check for unusual amounts
        $roundAmount = ($payment->amount % 100 === 0) && $payment->amount > 10000;

        // Check for payments outside business hours
        $outsideBusinessHours = ! $this->isBusinessHours();

        return $roundAmount && $outsideBusinessHours;
    }

    /**
     * Get recent payments count by user.
     */
    protected function getRecentPaymentsByUser(int $userId, int $hours): int
    {
        // This would query your payments table
        // Placeholder implementation
        return cache()->get("recent_payments_user_{$userId}", 0);
    }

    /**
     * Check if current time is within business hours.
     */
    protected function isBusinessHours(): bool
    {
        $hour = now()->hour;

        return $hour >= 9 && $hour <= 17; // 9 AM to 5 PM
    }

    /**
     * Apply approval workflow rules.
     */
    protected function applyWorkflowRules(string $workflowType, $model, array $rules = []): void
    {
        foreach ($rules as $rule) {
            $this->evaluateWorkflowRule($workflowType, $model, $rule);
        }
    }

    /**
     * Evaluate individual workflow rule.
     */
    protected function evaluateWorkflowRule(string $workflowType, $model, array $rule): void
    {
        $condition = $rule['condition'] ?? null;
        $action = $rule['action'] ?? null;
        $message = $rule['message'] ?? 'Workflow rule violation';

        if (! $condition || ! $action) {
            return;
        }

        // Evaluate condition (simplified example)
        if ($this->evaluateCondition($model, $condition) && ! $this->evaluateAction($model, $action)) {
            abort(403, $message);
        }
    }

    /**
     * Evaluate workflow condition.
     */
    protected function evaluateCondition($model, array $condition): bool
    {
        // Simplified condition evaluation
        // This would be more sophisticated in practice
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! $field || ! isset($model->$field)) {
            return false;
        }

        switch ($operator) {
            case '>':
                return $value < $model->$field;
            case '<':
                return $value > $model->$field;
            case '>=':
                return $value <= $model->$field;
            case '<=':
                return $value >= $model->$field;
            case '!=':
                return $value != $model->$field;
            default:
                return $value == $model->$field;
        }
    }

    /**
     * Evaluate workflow action.
     */
    protected function evaluateAction($model, array $action): bool
    {
        $type = $action['type'] ?? null;
        $permission = $action['permission'] ?? null;

        switch ($type) {
            case 'permission':
                return $permission ? auth()->user()->hasPermission($permission) : false;
            case 'role':
                $role = $action['role'] ?? null;

                return $role ? auth()->user()->hasRole($role) : false;
            case 'custom':
                $method = $action['method'] ?? null;

                return $method && method_exists($this, $method) ? $this->$method($model) : false;
        }

        return true;
    }

    /**
     * Log approval attempts for audit trail.
     */
    protected function logApprovalAttempt(string $type, string $action, string $status, string $message, $model = null): void
    {
        Log::info('Approval Workflow Event', [
            'user_id' => auth()->id(),
            'type' => $type,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'amount' => $model->amount ?? null,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send approval notifications.
     */
    protected function sendApprovalNotification(string $type, $model, string $status): void
    {
        // This would integrate with your notification system
        // Placeholder for notification logic
        Log::info('Approval Notification', [
            'type' => $type,
            'model_id' => $model?->id,
            'status' => $status,
            'user_id' => auth()->id(),
        ]);
    }
}
