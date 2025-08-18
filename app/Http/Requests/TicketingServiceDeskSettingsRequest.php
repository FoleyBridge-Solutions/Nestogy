<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketingServiceDeskSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage_ticketing_settings');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Ticket Settings
            'ticket_prefix' => 'nullable|string|max:10',
            'ticket_next_number' => 'nullable|integer|min:1',
            'ticket_from_name' => 'nullable|string|max:255',
            'ticket_from_email' => 'nullable|email|max:255',
            'ticket_email_parse' => 'boolean',
            'ticket_client_general_notifications' => 'boolean',
            'ticket_autoclose' => 'boolean',
            'ticket_autoclose_hours' => 'integer|min:1|max:8760',
            'ticket_new_ticket_notification_email' => 'nullable|email|max:255',
            
            // Ticket Categorization Rules
            'ticket_categorization_rules' => 'nullable|array',
            'ticket_categorization_rules.enabled' => 'boolean',
            'ticket_categorization_rules.auto_categorize' => 'boolean',
            'ticket_categorization_rules.require_category' => 'boolean',
            'ticket_categorization_rules.allow_multiple_categories' => 'boolean',
            'ticket_categorization_rules.default_category' => 'nullable|string|max:100',
            'ticket_categorization_rules.keywords' => 'nullable|array',
            'ticket_categorization_rules.keywords.*.keyword' => 'string|max:50',
            'ticket_categorization_rules.keywords.*.category' => 'string|max:100',
            'ticket_categorization_rules.keywords.*.priority' => 'string|in:low,medium,high,critical',
            
            // Ticket Priority Rules
            'ticket_priority_rules' => 'nullable|array',
            'ticket_priority_rules.enabled' => 'boolean',
            'ticket_priority_rules.auto_priority' => 'boolean',
            'ticket_priority_rules.allow_client_set_priority' => 'boolean',
            'ticket_priority_rules.default_priority' => 'string|in:low,medium,high,critical',
            'ticket_priority_rules.escalate_overdue' => 'boolean',
            'ticket_priority_rules.escalation_hours' => 'nullable|array',
            'ticket_priority_rules.escalation_hours.low' => 'integer|min:1|max:168',
            'ticket_priority_rules.escalation_hours.medium' => 'integer|min:1|max:72',
            'ticket_priority_rules.escalation_hours.high' => 'integer|min:1|max:24',
            'ticket_priority_rules.escalation_hours.critical' => 'integer|min:1|max:4',
            
            // SLA Definitions
            'sla_definitions' => 'nullable|array',
            'sla_definitions.enabled' => 'boolean',
            'sla_definitions.default_sla' => 'nullable|string|max:100',
            'sla_definitions.client_specific_slas' => 'boolean',
            'sla_definitions.business_hours_only' => 'boolean',
            'sla_definitions.exclude_weekends' => 'boolean',
            'sla_definitions.exclude_holidays' => 'boolean',
            'sla_definitions.response_times' => 'nullable|array',
            'sla_definitions.response_times.critical' => 'integer|min:5|max:1440',
            'sla_definitions.response_times.high' => 'integer|min:15|max:2880',
            'sla_definitions.response_times.medium' => 'integer|min:30|max:4320',
            'sla_definitions.response_times.low' => 'integer|min:60|max:10080',
            'sla_definitions.resolution_times' => 'nullable|array',
            'sla_definitions.resolution_times.critical' => 'integer|min:30|max:2880',
            'sla_definitions.resolution_times.high' => 'integer|min:120|max:4320',
            'sla_definitions.resolution_times.medium' => 'integer|min:480|max:10080',
            'sla_definitions.resolution_times.low' => 'integer|min:1440|max:20160',
            
            // SLA Escalation Policies
            'sla_escalation_policies' => 'nullable|array',
            'sla_escalation_policies.enabled' => 'boolean',
            'sla_escalation_policies.breach_warning_percentage' => 'integer|min:50|max:95',
            'sla_escalation_policies.auto_escalate_on_breach' => 'boolean',
            'sla_escalation_policies.escalation_levels' => 'nullable|array',
            'sla_escalation_policies.escalation_levels.*.percentage' => 'integer|min:25|max:100',
            'sla_escalation_policies.escalation_levels.*.action' => 'string|in:notify_assignee,notify_manager,notify_client,change_priority,reassign',
            'sla_escalation_policies.escalation_levels.*.notification_channels' => 'nullable|array',
            'sla_escalation_policies.escalation_levels.*.notification_channels.*' => 'string|in:email,sms,slack,teams,webhook',
            
            // Auto Assignment Rules
            'auto_assignment_rules' => 'nullable|array',
            'auto_assignment_rules.enabled' => 'boolean',
            'auto_assignment_rules.assignment_method' => 'string|in:round_robin,skill_based,workload_balanced,random,manual',
            'auto_assignment_rules.consider_availability' => 'boolean',
            'auto_assignment_rules.consider_skills' => 'boolean',
            'auto_assignment_rules.consider_workload' => 'boolean',
            'auto_assignment_rules.fallback_assignee' => 'nullable|integer|exists:users,id',
            'auto_assignment_rules.max_tickets_per_user' => 'integer|min:1|max:100',
            'auto_assignment_rules.skill_matching_threshold' => 'integer|min:1|max:100',
            'auto_assignment_rules.assignment_rules' => 'nullable|array',
            'auto_assignment_rules.assignment_rules.*.condition' => 'string|max:255',
            'auto_assignment_rules.assignment_rules.*.assignee_id' => 'integer|exists:users,id',
            'auto_assignment_rules.assignment_rules.*.priority' => 'integer|min:1|max:100',
            
            // Routing Logic
            'routing_logic' => 'nullable|array',
            'routing_logic.enabled' => 'boolean',
            'routing_logic.route_by_client' => 'boolean',
            'routing_logic.route_by_category' => 'boolean',
            'routing_logic.route_by_keywords' => 'boolean',
            'routing_logic.route_by_priority' => 'boolean',
            'routing_logic.route_by_source' => 'boolean',
            'routing_logic.default_queue' => 'nullable|string|max:100',
            'routing_logic.routing_rules' => 'nullable|array',
            'routing_logic.routing_rules.*.name' => 'string|max:100',
            'routing_logic.routing_rules.*.conditions' => 'array',
            'routing_logic.routing_rules.*.actions' => 'array',
            'routing_logic.routing_rules.*.priority' => 'integer|min:1|max:100',
            'routing_logic.routing_rules.*.enabled' => 'boolean',
            
            // Approval Workflows
            'approval_workflows' => 'nullable|array',
            'approval_workflows.enabled' => 'boolean',
            'approval_workflows.require_approval_for_closure' => 'boolean',
            'approval_workflows.require_approval_for_billing' => 'boolean',
            'approval_workflows.auto_approve_threshold_hours' => 'nullable|integer|min:1|max:168',
            'approval_workflows.approval_timeout_hours' => 'integer|min:1|max:168',
            'approval_workflows.escalate_on_timeout' => 'boolean',
            'approval_workflows.workflows' => 'nullable|array',
            'approval_workflows.workflows.*.name' => 'string|max:100',
            'approval_workflows.workflows.*.trigger_conditions' => 'array',
            'approval_workflows.workflows.*.approvers' => 'array',
            'approval_workflows.workflows.*.approvers.*' => 'integer|exists:users,id',
            'approval_workflows.workflows.*.require_all_approvers' => 'boolean',
            'approval_workflows.workflows.*.enabled' => 'boolean',
            
            // Time Tracking
            'time_tracking_enabled' => 'boolean',
            'time_tracking_settings' => 'nullable|array',
            'time_tracking_settings.require_time_entry' => 'boolean',
            'time_tracking_settings.allow_manual_time_entry' => 'boolean',
            'time_tracking_settings.auto_start_timer' => 'boolean',
            'time_tracking_settings.auto_pause_timer' => 'boolean',
            'time_tracking_settings.minimum_time_increment_minutes' => 'integer|min:1|max:60',
            'time_tracking_settings.round_to_nearest_minutes' => 'integer|in:1,5,10,15,30',
            'time_tracking_settings.require_time_approval' => 'boolean',
            'time_tracking_settings.billable_time_default' => 'boolean',
            'time_tracking_settings.show_timer_to_clients' => 'boolean',
            'time_tracking_settings.time_edit_deadline_hours' => 'integer|min:1|max:168',
            
            // Customer Satisfaction
            'customer_satisfaction_enabled' => 'boolean',
            'csat_settings' => 'nullable|array',
            'csat_settings.survey_trigger' => 'string|in:on_closure,on_resolution,manual',
            'csat_settings.survey_delay_hours' => 'integer|min:0|max:168',
            'csat_settings.survey_reminder_enabled' => 'boolean',
            'csat_settings.survey_reminder_days' => 'integer|min:1|max:14',
            'csat_settings.rating_scale' => 'string|in:5_star,10_point,3_point,thumbs',
            'csat_settings.require_comments' => 'boolean',
            'csat_settings.anonymous_surveys' => 'boolean',
            'csat_settings.public_feedback' => 'boolean',
            'csat_settings.low_rating_threshold' => 'integer|min:1|max:5',
            'csat_settings.escalate_low_ratings' => 'boolean',
            
            // Ticket Templates
            'ticket_templates' => 'nullable|array',
            'ticket_templates.enabled' => 'boolean',
            'ticket_templates.allow_custom_templates' => 'boolean',
            'ticket_templates.require_template_selection' => 'boolean',
            'ticket_templates.templates' => 'nullable|array',
            'ticket_templates.templates.*.name' => 'string|max:100',
            'ticket_templates.templates.*.category' => 'string|max:100',
            'ticket_templates.templates.*.priority' => 'string|in:low,medium,high,critical',
            'ticket_templates.templates.*.subject_template' => 'string|max:255',
            'ticket_templates.templates.*.description_template' => 'string|max:2000',
            'ticket_templates.templates.*.required_fields' => 'nullable|array',
            'ticket_templates.templates.*.auto_assign_to' => 'nullable|integer|exists:users,id',
            'ticket_templates.templates.*.enabled' => 'boolean',
            
            // Ticket Automation Rules
            'ticket_automation_rules' => 'nullable|array',
            'ticket_automation_rules.enabled' => 'boolean',
            'ticket_automation_rules.run_on_create' => 'boolean',
            'ticket_automation_rules.run_on_update' => 'boolean',
            'ticket_automation_rules.run_on_close' => 'boolean',
            'ticket_automation_rules.rules' => 'nullable|array',
            'ticket_automation_rules.rules.*.name' => 'string|max:100',
            'ticket_automation_rules.rules.*.trigger' => 'string|in:on_create,on_update,on_close,on_reopen,on_assign,time_based',
            'ticket_automation_rules.rules.*.conditions' => 'array',
            'ticket_automation_rules.rules.*.actions' => 'array',
            'ticket_automation_rules.rules.*.enabled' => 'boolean',
            'ticket_automation_rules.rules.*.priority' => 'integer|min:1|max:100',
            
            // Multi-channel Settings
            'multichannel_settings' => 'nullable|array',
            'multichannel_settings.email_enabled' => 'boolean',
            'multichannel_settings.portal_enabled' => 'boolean',
            'multichannel_settings.phone_enabled' => 'boolean',
            'multichannel_settings.chat_enabled' => 'boolean',
            'multichannel_settings.slack_enabled' => 'boolean',
            'multichannel_settings.teams_enabled' => 'boolean',
            'multichannel_settings.whatsapp_enabled' => 'boolean',
            'multichannel_settings.unified_inbox' => 'boolean',
            'multichannel_settings.channel_routing' => 'nullable|array',
            'multichannel_settings.response_templates' => 'nullable|array',
            'multichannel_settings.auto_acknowledge' => 'boolean',
            'multichannel_settings.acknowledgment_template' => 'nullable|string|max:500',
            
            // Queue Management
            'queue_management_settings' => 'nullable|array',
            'queue_management_settings.enabled' => 'boolean',
            'queue_management_settings.default_queue' => 'nullable|string|max:100',
            'queue_management_settings.auto_queue_assignment' => 'boolean',
            'queue_management_settings.queue_capacity_limits' => 'boolean',
            'queue_management_settings.queue_sla_inheritance' => 'boolean',
            'queue_management_settings.queues' => 'nullable|array',
            'queue_management_settings.queues.*.name' => 'string|max:100',
            'queue_management_settings.queues.*.description' => 'nullable|string|max:255',
            'queue_management_settings.queues.*.max_capacity' => 'nullable|integer|min:1|max:1000',
            'queue_management_settings.queues.*.default_assignee' => 'nullable|integer|exists:users,id',
            'queue_management_settings.queues.*.sla_id' => 'nullable|integer',
            'queue_management_settings.queues.*.enabled' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ticket_from_email.email' => 'Ticket from email must be a valid email address.',
            'ticket_new_ticket_notification_email.email' => 'Notification email must be a valid email address.',
            'ticket_autoclose_hours.max' => 'Auto-close time cannot exceed 365 days (8760 hours).',
            'sla_definitions.response_times.*.min' => 'Response time must be at least :min minutes.',
            'sla_definitions.resolution_times.*.min' => 'Resolution time must be at least :min minutes.',
            'sla_escalation_policies.breach_warning_percentage.min' => 'Warning percentage must be at least 50%.',
            'auto_assignment_rules.max_tickets_per_user.max' => 'Maximum tickets per user cannot exceed 100.',
            'time_tracking_settings.minimum_time_increment_minutes.max' => 'Time increment cannot exceed 60 minutes.',
            'csat_settings.survey_delay_hours.max' => 'Survey delay cannot exceed 7 days (168 hours).',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ticket_email_parse' => 'email to ticket parsing',
            'ticket_autoclose' => 'auto-close tickets',
            'time_tracking_enabled' => 'time tracking',
            'customer_satisfaction_enabled' => 'customer satisfaction surveys',
            'sla_definitions.response_times.critical' => 'critical priority response time',
            'sla_definitions.resolution_times.critical' => 'critical priority resolution time',
            'auto_assignment_rules.assignment_method' => 'assignment method',
            'multichannel_settings.unified_inbox' => 'unified inbox',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate SLA response vs resolution times
            $slaDef = $this->input('sla_definitions', []);
            if (isset($slaDef['response_times'], $slaDef['resolution_times'])) {
                $responseTimes = $slaDef['response_times'];
                $resolutionTimes = $slaDef['resolution_times'];
                
                foreach (['critical', 'high', 'medium', 'low'] as $priority) {
                    if (isset($responseTimes[$priority], $resolutionTimes[$priority])) {
                        if ($responseTimes[$priority] >= $resolutionTimes[$priority]) {
                            $validator->errors()->add(
                                "sla_definitions.resolution_times.{$priority}",
                                "Resolution time must be greater than response time for {$priority} priority."
                            );
                        }
                    }
                }
            }
            
            // Validate escalation hours sequence
            $priorityRules = $this->input('ticket_priority_rules', []);
            if (isset($priorityRules['escalation_hours'])) {
                $escalationHours = $priorityRules['escalation_hours'];
                
                if (isset($escalationHours['critical'], $escalationHours['high']) &&
                    $escalationHours['critical'] >= $escalationHours['high']) {
                    $validator->errors()->add(
                        'ticket_priority_rules.escalation_hours.high',
                        'High priority escalation time must be greater than critical priority.'
                    );
                }
                
                if (isset($escalationHours['high'], $escalationHours['medium']) &&
                    $escalationHours['high'] >= $escalationHours['medium']) {
                    $validator->errors()->add(
                        'ticket_priority_rules.escalation_hours.medium',
                        'Medium priority escalation time must be greater than high priority.'
                    );
                }
                
                if (isset($escalationHours['medium'], $escalationHours['low']) &&
                    $escalationHours['medium'] >= $escalationHours['low']) {
                    $validator->errors()->add(
                        'ticket_priority_rules.escalation_hours.low',
                        'Low priority escalation time must be greater than medium priority.'
                    );
                }
            }
            
            // Validate CSAT rating scale and threshold
            $csatSettings = $this->input('csat_settings', []);
            if (isset($csatSettings['rating_scale'], $csatSettings['low_rating_threshold'])) {
                $scale = $csatSettings['rating_scale'];
                $threshold = $csatSettings['low_rating_threshold'];
                
                $maxThreshold = match($scale) {
                    '3_point' => 2,
                    '5_star' => 3,
                    '10_point' => 5,
                    default => 3
                };
                
                if ($threshold > $maxThreshold) {
                    $validator->errors()->add(
                        'csat_settings.low_rating_threshold',
                        "Low rating threshold cannot exceed {$maxThreshold} for {$scale} scale."
                    );
                }
            }
        });
    }
}