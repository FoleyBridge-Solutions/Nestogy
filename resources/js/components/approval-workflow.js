/**
 * Approval Workflow Component
 * Manages quote approval processes with configurable workflow steps
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('approvalWorkflow', (config = {}) => ({
        // Configuration
        autoSubmit: config.autoSubmit !== false,
        enableNotifications: config.enableNotifications !== false,
        workflowTimeout: config.workflowTimeout || 72, // hours
        
        // Workflow State
        currentQuote: null,
        workflow: {
            id: null,
            status: 'pending',
            steps: [],
            currentStep: 0,
            submittedBy: null,
            submittedAt: null,
            completedAt: null,
            rejectedAt: null,
            rejectionReason: ''
        },
        
        // Approval Steps
        approvalSteps: [
            {
                id: 1,
                name: 'Manager Review',
                role: 'manager',
                required: true,
                status: 'pending',
                approver: null,
                approvedAt: null,
                comments: '',
                threshold: { amount: 0, percentage: 0 }
            },
            {
                id: 2,
                name: 'Finance Review',
                role: 'finance',
                required: false,
                status: 'pending',
                approver: null,
                approvedAt: null,
                comments: '',
                threshold: { amount: 10000, percentage: 20 }
            },
            {
                id: 3,
                name: 'Executive Approval',
                role: 'executive',
                required: false,
                status: 'pending',
                approver: null,
                approvedAt: null,
                comments: '',
                threshold: { amount: 50000, percentage: 50 }
            }
        ],
        
        // UI State
        showWorkflow: false,
        showApprovalModal: false,
        showRejectionModal: false,
        loading: false,
        submitting: false,
        
        // Current user context
        currentUser: null,
        userRole: null,
        canApprove: false,
        canReject: false,
        canModify: false,
        
        // Form state
        approvalForm: {
            action: '', // 'approve', 'reject', 'request_changes'
            comments: '',
            conditions: [],
            sendNotification: true
        },
        
        // Available approvers by role
        approvers: {
            manager: [],
            finance: [],
            executive: []
        },
        
        // Workflow history
        history: [],
        
        // Notification settings
        notifications: {
            email: true,
            push: true,
            slack: false
        },

        // Initialize workflow
        init() {
            this.loadCurrentUser();
            this.setupEventListeners();
            this.loadApprovers();
        },

        // Setup event listeners
        setupEventListeners() {
            // Listen for quote changes
            document.addEventListener('quote-loaded', (e) => {
                this.setQuote(e.detail.quote);
            });

            // Listen for workflow triggers
            document.addEventListener('submit-for-approval', (e) => {
                this.submitForApproval(e.detail.quote);
            });

            // Listen for approval actions
            document.addEventListener('approve-quote', (e) => {
                this.approveStep(e.detail.stepId, e.detail.comments);
            });

            document.addEventListener('reject-quote', (e) => {
                this.rejectWorkflow(e.detail.reason, e.detail.comments);
            });

            // Auto-refresh workflow status
            if (this.currentQuote && this.workflow.id) {
                setInterval(() => {
                    this.refreshWorkflowStatus();
                }, 30000); // Every 30 seconds
            }
        },

        // Load current user context
        async loadCurrentUser() {
            try {
                const response = await fetch('/api/user/current');
                if (response.ok) {
                    const userData = await response.json();
                    this.currentUser = userData.user;
                    this.userRole = userData.role;
                    this.updateUserPermissions();
                }
            } catch (error) {
                console.error('Failed to load current user:', error);
            }
        },

        // Update user permissions
        updateUserPermissions() {
            if (!this.currentUser || !this.workflow.steps) return;

            const currentStep = this.getCurrentStep();
            if (currentStep) {
                this.canApprove = this.userCanApproveStep(currentStep);
                this.canReject = this.userCanRejectStep(currentStep);
                this.canModify = this.userCanModifyQuote();
            }
        },

        // Load available approvers
        async loadApprovers() {
            try {
                const response = await fetch('/api/approvers/by-role');
                if (response.ok) {
                    this.approvers = await response.json();
                }
            } catch (error) {
                console.error('Failed to load approvers:', error);
            }
        },

        // Set current quote and initialize workflow
        async setQuote(quote) {
            this.currentQuote = quote;
            
            if (quote.approval_workflow_id) {
                await this.loadWorkflow(quote.approval_workflow_id);
            } else {
                this.initializeNewWorkflow(quote);
            }
            
            this.updateUserPermissions();
        },

        // Load existing workflow
        async loadWorkflow(workflowId) {
            try {
                this.loading = true;
                const response = await fetch(`/api/approval-workflows/${workflowId}`);
                
                if (response.ok) {
                    const workflowData = await response.json();
                    this.workflow = workflowData.workflow;
                    this.history = workflowData.history || [];
                    this.updateStepStatuses();
                }
            } catch (error) {
                console.error('Failed to load workflow:', error);
            } finally {
                this.loading = false;
            }
        },

        // Initialize new workflow for quote
        initializeNewWorkflow(quote) {
            this.workflow = {
                id: null,
                status: 'draft',
                steps: this.determineRequiredSteps(quote),
                currentStep: 0,
                submittedBy: null,
                submittedAt: null,
                completedAt: null,
                rejectedAt: null,
                rejectionReason: ''
            };
        },

        // Determine required approval steps based on quote
        determineRequiredSteps(quote) {
            const steps = JSON.parse(JSON.stringify(this.approvalSteps)); // Deep clone
            const quoteAmount = quote.total_amount;
            
            // Calculate percentage if this is a modification
            let changePercentage = 0;
            if (quote.original_amount) {
                changePercentage = Math.abs((quoteAmount - quote.original_amount) / quote.original_amount) * 100;
            }

            // Determine which steps are required
            return steps.map(step => {
                step.required = step.threshold.amount <= quoteAmount || 
                               step.threshold.percentage <= changePercentage ||
                               step.required; // Always required steps
                
                return step;
            }).filter(step => step.required);
        },

        // Submit quote for approval
        async submitForApproval(quote = null) {
            if (quote) this.currentQuote = quote;
            if (!this.currentQuote) return;

            try {
                this.submitting = true;

                const workflowData = {
                    quote_id: this.currentQuote.id,
                    steps: this.workflow.steps,
                    submitted_by: this.currentUser.id,
                    auto_submit: this.autoSubmit,
                    notifications: this.notifications
                };

                const response = await fetch('/api/approval-workflows', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(workflowData)
                });

                if (response.ok) {
                    const result = await response.json();
                    this.workflow = result.workflow;
                    this.showWorkflow = true;

                    // Send notifications
                    if (this.enableNotifications) {
                        this.sendApprovalNotifications();
                    }

                    this.$dispatch('workflow-submitted', {
                        workflow: this.workflow,
                        quote: this.currentQuote
                    });

                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to submit for approval');
                }

            } catch (error) {
                console.error('Failed to submit for approval:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to submit quote for approval'
                });
            } finally {
                this.submitting = false;
            }
        },

        // Approve current step
        async approveStep(stepId = null, comments = '') {
            const step = stepId ? this.getStepById(stepId) : this.getCurrentStep();
            if (!step || !this.userCanApproveStep(step)) return;

            try {
                this.submitting = true;

                const approvalData = {
                    workflow_id: this.workflow.id,
                    step_id: step.id,
                    action: 'approve',
                    comments: comments || this.approvalForm.comments,
                    approver_id: this.currentUser.id,
                    conditions: this.approvalForm.conditions
                };

                const response = await fetch('/api/approval-workflows/approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(approvalData)
                });

                if (response.ok) {
                    const result = await response.json();
                    this.updateWorkflowFromResponse(result);
                    
                    this.closeApprovalModal();
                    
                    // Check if workflow is complete
                    if (this.isWorkflowComplete()) {
                        this.completeWorkflow();
                    } else {
                        this.advanceToNextStep();
                    }

                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to approve step');
                }

            } catch (error) {
                console.error('Failed to approve step:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to approve step'
                });
            } finally {
                this.submitting = false;
            }
        },

        // Reject workflow
        async rejectWorkflow(reason = '', comments = '') {
            if (!this.canReject) return;

            try {
                this.submitting = true;

                const rejectionData = {
                    workflow_id: this.workflow.id,
                    action: 'reject',
                    reason: reason || this.approvalForm.comments,
                    comments: comments,
                    rejector_id: this.currentUser.id
                };

                const response = await fetch('/api/approval-workflows/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(rejectionData)
                });

                if (response.ok) {
                    const result = await response.json();
                    this.updateWorkflowFromResponse(result);
                    this.closeRejectionModal();

                    this.$dispatch('workflow-rejected', {
                        workflow: this.workflow,
                        reason: reason
                    });

                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to reject workflow');
                }

            } catch (error) {
                console.error('Failed to reject workflow:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to reject workflow'
                });
            } finally {
                this.submitting = false;
            }
        },

        // Request changes
        async requestChanges(comments) {
            try {
                this.submitting = true;

                const requestData = {
                    workflow_id: this.workflow.id,
                    action: 'request_changes',
                    comments: comments,
                    requester_id: this.currentUser.id
                };

                const response = await fetch('/api/approval-workflows/request-changes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(requestData)
                });

                if (response.ok) {
                    const result = await response.json();
                    this.updateWorkflowFromResponse(result);

                    this.$dispatch('changes-requested', {
                        workflow: this.workflow,
                        comments: comments
                    });

                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to request changes');
                }

            } catch (error) {
                console.error('Failed to request changes:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to request changes'
                });
            } finally {
                this.submitting = false;
            }
        },

        // Complete workflow
        completeWorkflow() {
            this.workflow.status = 'approved';
            this.workflow.completedAt = new Date().toISOString();

            // Update quote status
            if (this.currentQuote) {
                this.currentQuote.status = 'approved';
            }

            // Send completion notifications
            this.sendCompletionNotifications();

            this.$dispatch('workflow-completed', {
                workflow: this.workflow,
                quote: this.currentQuote
            });
        },

        // Advance to next step
        advanceToNextStep() {
            const nextStep = this.getNextPendingStep();
            if (nextStep) {
                this.workflow.currentStep = nextStep.id;
                this.sendStepNotifications(nextStep);
            }
        },

        // Update workflow from server response
        updateWorkflowFromResponse(response) {
            this.workflow = response.workflow;
            this.history = response.history || this.history;
            this.updateStepStatuses();
            this.updateUserPermissions();
        },

        // Update step statuses
        updateStepStatuses() {
            this.workflow.steps.forEach(step => {
                const historyEntry = this.history.find(h => h.step_id === step.id);
                if (historyEntry) {
                    step.status = historyEntry.action;
                    step.approver = historyEntry.approver;
                    step.approvedAt = historyEntry.created_at;
                    step.comments = historyEntry.comments;
                }
            });
        },

        // Refresh workflow status
        async refreshWorkflowStatus() {
            if (!this.workflow.id) return;

            try {
                const response = await fetch(`/api/approval-workflows/${this.workflow.id}/status`);
                if (response.ok) {
                    const status = await response.json();
                    
                    if (status.workflow.status !== this.workflow.status) {
                        this.updateWorkflowFromResponse(status);
                    }
                }
            } catch (error) {
                console.error('Failed to refresh workflow status:', error);
            }
        },

        // Permission checks
        userCanApproveStep(step) {
            return step.role === this.userRole && 
                   step.status === 'pending' && 
                   (!step.approver || step.approver.id !== this.currentUser.id);
        },

        userCanRejectStep(step) {
            return this.userCanApproveStep(step);
        },

        userCanModifyQuote() {
            return this.workflow.status === 'draft' || 
                   this.workflow.status === 'changes_requested';
        },

        // Workflow navigation helpers
        getCurrentStep() {
            return this.workflow.steps.find(step => step.id === this.workflow.currentStep) ||
                   this.workflow.steps.find(step => step.status === 'pending');
        },

        getStepById(stepId) {
            return this.workflow.steps.find(step => step.id === stepId);
        },

        getNextPendingStep() {
            return this.workflow.steps.find(step => step.status === 'pending');
        },

        isWorkflowComplete() {
            return this.workflow.steps.every(step => !step.required || step.status === 'approved');
        },

        // Notifications
        async sendApprovalNotifications() {
            const currentStep = this.getCurrentStep();
            if (currentStep) {
                await this.sendStepNotifications(currentStep);
            }
        },

        async sendStepNotifications(step) {
            try {
                await fetch('/api/notifications/approval-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        workflow_id: this.workflow.id,
                        step_id: step.id,
                        quote_id: this.currentQuote.id,
                        notification_types: this.notifications
                    })
                });
            } catch (error) {
                console.error('Failed to send step notifications:', error);
            }
        },

        async sendCompletionNotifications() {
            try {
                await fetch('/api/notifications/approval-completed', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        workflow_id: this.workflow.id,
                        quote_id: this.currentQuote.id,
                        notification_types: this.notifications
                    })
                });
            } catch (error) {
                console.error('Failed to send completion notifications:', error);
            }
        },

        // Modal management
        openApprovalModal() {
            this.resetApprovalForm();
            this.showApprovalModal = true;
        },

        closeApprovalModal() {
            this.showApprovalModal = false;
            this.resetApprovalForm();
        },

        openRejectionModal() {
            this.resetApprovalForm();
            this.showRejectionModal = true;
        },

        closeRejectionModal() {
            this.showRejectionModal = false;
            this.resetApprovalForm();
        },

        resetApprovalForm() {
            this.approvalForm = {
                action: '',
                comments: '',
                conditions: [],
                sendNotification: true
            };
        },

        // Utility methods
        formatDateTime(dateString) {
            if (!dateString) return 'Pending';
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(dateString));
        },

        getStepStatusIcon(step) {
            const icons = {
                pending: '⏳',
                approved: '✅',
                rejected: '❌',
                changes_requested: '🔄'
            };
            return icons[step.status] || '⏳';
        },

        getStepStatusClass(step) {
            const classes = {
                pending: 'text-yellow-600 bg-yellow-100',
                approved: 'text-green-600 bg-green-100',
                rejected: 'text-red-600 bg-red-100',
                changes_requested: 'text-blue-600 bg-blue-100'
            };
            return classes[step.status] || 'text-gray-600 bg-gray-100';
        },

        // Computed properties
        get workflowProgress() {
            const completedSteps = this.workflow.steps.filter(step => step.status === 'approved').length;
            const totalSteps = this.workflow.steps.filter(step => step.required).length;
            return totalSteps > 0 ? (completedSteps / totalSteps) * 100 : 0;
        },

        get canSubmitForApproval() {
            return this.currentQuote && 
                   !this.workflow.id && 
                   this.currentQuote.status === 'draft';
        },

        get workflowStatusText() {
            const statusTexts = {
                draft: 'Draft',
                pending: 'Pending Approval',
                approved: 'Approved',
                rejected: 'Rejected',
                changes_requested: 'Changes Requested'
            };
            return statusTexts[this.workflow.status] || 'Unknown';
        },

        get timeRemaining() {
            if (!this.workflow.submittedAt) return null;
            
            const submitted = new Date(this.workflow.submittedAt);
            const deadline = new Date(submitted.getTime() + (this.workflowTimeout * 60 * 60 * 1000));
            const now = new Date();
            
            if (deadline <= now) return 'Overdue';
            
            const diff = deadline - now;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            
            if (hours < 24) {
                return `${hours} hours remaining`;
            } else {
                const days = Math.floor(hours / 24);
                return `${days} day(s) remaining`;
            }
        }
    }));
});