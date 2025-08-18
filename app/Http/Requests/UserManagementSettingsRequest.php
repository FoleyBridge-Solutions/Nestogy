<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserManagementSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage_users');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $company = auth()->user()->company;
        $subscription = $company->subscription ?? null;
        $currentUserCount = $company->users()->count();
        
        // Company ID 1 has unlimited users, others have subscription limits
        $isUnlimitedCompany = $company->id === 1;
        $userLimit = $isUnlimitedCompany ? 999999 : ($subscription ? $subscription->user_limit : 5);

        $maxUsersRules = ['integer', 'min:1'];
        
        if (!$isUnlimitedCompany) {
            $maxUsersRules[] = 'max:' . $userLimit;
            $maxUsersRules[] = function ($attribute, $value, $fail) use ($currentUserCount, $userLimit) {
                if ($value > $userLimit) {
                    $fail("Your current subscription allows a maximum of {$userLimit} users. Please upgrade your subscription to add more users.");
                }
                if ($value < $currentUserCount) {
                    $fail("Cannot set limit below current user count of {$currentUserCount}. Please deactivate users first.");
                }
            };
        } else {
            // For unlimited company, only check against current user count
            $maxUsersRules[] = function ($attribute, $value, $fail) use ($currentUserCount) {
                if ($value < $currentUserCount) {
                    $fail("Cannot set limit below current user count of {$currentUserCount}. Please deactivate users first.");
                }
            };
        }

        return [
            // User Limits and Subscription Management
            'max_users' => $maxUsersRules,
            'user_invite_limit_per_month' => 'integer|min:0|max:100',
            'require_admin_approval_for_new_users' => 'boolean',
            'auto_deactivate_unused_accounts_days' => 'nullable|integer|min:30|max:365',
            
            // User Onboarding
            'user_onboarding_settings' => 'nullable|array',
            'user_onboarding_settings.enabled' => 'boolean',
            'user_onboarding_settings.welcome_email' => 'boolean',
            'user_onboarding_settings.setup_wizard' => 'boolean',
            'user_onboarding_settings.training_materials' => 'boolean',
            'user_onboarding_settings.mentor_assignment' => 'boolean',
            'user_onboarding_settings.probation_period_days' => 'integer|min:0|max:90',
            
            // User Profile Settings
            'user_profile_settings' => 'nullable|array',
            'user_profile_settings.allow_avatar_upload' => 'boolean',
            'user_profile_settings.require_profile_completion' => 'boolean',
            'user_profile_settings.allow_custom_fields' => 'boolean',
            'user_profile_settings.show_last_login' => 'boolean',
            'user_profile_settings.show_online_status' => 'boolean',
            'user_profile_settings.allow_signature' => 'boolean',
            'user_profile_settings.signature_max_length' => 'integer|min:50|max:500',
            
            // Authentication Settings
            'authentication_settings' => 'nullable|array',
            'authentication_settings.allow_local_login' => 'boolean',
            'authentication_settings.require_email_verification' => 'boolean',
            'authentication_settings.allow_social_login' => 'boolean',
            'authentication_settings.allowed_social_providers' => 'nullable|array',
            'authentication_settings.allowed_social_providers.*' => 'string|in:google,microsoft,github,linkedin',
            'authentication_settings.force_password_change_first_login' => 'boolean',
            'authentication_settings.remember_me_enabled' => 'boolean',
            'authentication_settings.remember_me_duration_days' => 'integer|min:1|max:90',
            
            // Role and Permission Settings
            'role_permission_settings' => 'nullable|array',
            'role_permission_settings.allow_custom_roles' => 'boolean',
            'role_permission_settings.max_custom_roles' => 'integer|min:0|max:20',
            'role_permission_settings.require_approval_for_role_changes' => 'boolean',
            'role_permission_settings.allow_role_inheritance' => 'boolean',
            'role_permission_settings.permission_inheritance_depth' => 'integer|min:1|max:5',
            
            // User Session Management
            'session_management_settings' => 'nullable|array',
            'session_management_settings.max_concurrent_sessions' => 'integer|min:1|max:10',
            'session_management_settings.idle_timeout_minutes' => 'integer|min:5|max:1440',
            'session_management_settings.absolute_timeout_hours' => 'integer|min:1|max:24',
            'session_management_settings.force_logout_on_password_change' => 'boolean',
            'session_management_settings.track_session_activity' => 'boolean',
            
            // User Activity and Monitoring
            'user_activity_settings' => 'nullable|array',
            'user_activity_settings.track_login_history' => 'boolean',
            'user_activity_settings.login_history_retention_days' => 'integer|min:30|max:365',
            'user_activity_settings.track_user_actions' => 'boolean',
            'user_activity_settings.activity_log_retention_days' => 'integer|min:30|max:365',
            'user_activity_settings.notify_on_suspicious_activity' => 'boolean',
            'user_activity_settings.failed_login_threshold' => 'integer|min:3|max:10',
            'user_activity_settings.unusual_location_detection' => 'boolean',
            
            // User Deactivation and Offboarding
            'user_offboarding_settings' => 'nullable|array',
            'user_offboarding_settings.require_exit_interview' => 'boolean',
            'user_offboarding_settings.data_retention_policy' => 'string|in:immediate_delete,30_days,90_days,1_year,permanent',
            'user_offboarding_settings.transfer_data_to_manager' => 'boolean',
            'user_offboarding_settings.revoke_api_access' => 'boolean',
            'user_offboarding_settings.disable_integrations' => 'boolean',
            'user_offboarding_settings.send_departure_notification' => 'boolean',
            
            // Department and Team Management
            'department_settings' => 'nullable|array',
            'department_settings.allow_multiple_departments' => 'boolean',
            'department_settings.require_department_assignment' => 'boolean',
            'department_settings.allow_custom_departments' => 'boolean',
            'department_settings.max_department_levels' => 'integer|min:1|max:5',
            'department_settings.department_budget_tracking' => 'boolean',
            
            // User Communication Settings
            'user_communication_settings' => 'nullable|array',
            'user_communication_settings.allow_internal_messaging' => 'boolean',
            'user_communication_settings.allow_file_sharing' => 'boolean',
            'user_communication_settings.max_file_size_mb' => 'integer|min:1|max:100',
            'user_communication_settings.allowed_file_types' => 'nullable|array',
            'user_communication_settings.allowed_file_types.*' => 'string|in:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,zip,rar',
            'user_communication_settings.message_retention_days' => 'integer|min:30|max:365',
            
            // Skill and Competency Management
            'skill_management_settings' => 'nullable|array',
            'skill_management_settings.enabled' => 'boolean',
            'skill_management_settings.allow_self_assessment' => 'boolean',
            'skill_management_settings.require_manager_validation' => 'boolean',
            'skill_management_settings.skill_based_task_assignment' => 'boolean',
            'skill_management_settings.track_skill_development' => 'boolean',
            'skill_management_settings.certification_tracking' => 'boolean',
            
            // Performance Management
            'performance_settings' => 'nullable|array',
            'performance_settings.enabled' => 'boolean',
            'performance_settings.review_frequency' => 'string|in:monthly,quarterly,semi_annual,annual',
            'performance_settings.self_review_enabled' => 'boolean',
            'performance_settings.peer_review_enabled' => 'boolean',
            'performance_settings.goal_setting_enabled' => 'boolean',
            'performance_settings.performance_improvement_plans' => 'boolean',
            
            // Time Tracking for Users
            'user_time_tracking_settings' => 'nullable|array',
            'user_time_tracking_settings.enabled' => 'boolean',
            'user_time_tracking_settings.require_time_approval' => 'boolean',
            'user_time_tracking_settings.allow_time_editing' => 'boolean',
            'user_time_tracking_settings.time_edit_deadline_hours' => 'integer|min:1|max:168',
            'user_time_tracking_settings.auto_break_detection' => 'boolean',
            'user_time_tracking_settings.overtime_threshold_hours' => 'integer|min:8|max:12',
            
            // Workspace and Resource Management
            'workspace_settings' => 'nullable|array',
            'workspace_settings.hot_desking_enabled' => 'boolean',
            'workspace_settings.resource_booking_enabled' => 'boolean',
            'workspace_settings.max_booking_days_advance' => 'integer|min:1|max:90',
            'workspace_settings.allow_recurring_bookings' => 'boolean',
            'workspace_settings.workspace_utilization_tracking' => 'boolean',
            
            // Emergency Contact and Safety
            'emergency_settings' => 'nullable|array',
            'emergency_settings.require_emergency_contacts' => 'boolean',
            'emergency_settings.min_emergency_contacts' => 'integer|min:1|max:5',
            'emergency_settings.emergency_notification_enabled' => 'boolean',
            'emergency_settings.wellness_check_enabled' => 'boolean',
            'emergency_settings.panic_button_enabled' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'max_users.max' => 'Your current subscription plan limits you to :max users. Please upgrade to add more users.',
            'user_invite_limit_per_month.max' => 'Monthly invite limit cannot exceed 100.',
            'role_permission_settings.max_custom_roles.max' => 'You cannot create more than 20 custom roles.',
            'session_management_settings.max_concurrent_sessions.max' => 'Maximum concurrent sessions cannot exceed 10.',
            'session_management_settings.idle_timeout_minutes.min' => 'Idle timeout must be at least 5 minutes.',
            'user_activity_settings.failed_login_threshold.min' => 'Failed login threshold must be at least 3 attempts.',
            'user_communication_settings.max_file_size_mb.max' => 'Maximum file size cannot exceed 100 MB.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'max_users' => 'maximum users',
            'user_invite_limit_per_month' => 'monthly invite limit',
            'require_admin_approval_for_new_users' => 'admin approval for new users',
            'auto_deactivate_unused_accounts_days' => 'auto-deactivate unused accounts',
            'session_management_settings.max_concurrent_sessions' => 'maximum concurrent sessions',
            'session_management_settings.idle_timeout_minutes' => 'idle timeout',
            'user_activity_settings.failed_login_threshold' => 'failed login threshold',
            'performance_settings.review_frequency' => 'performance review frequency',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $company = auth()->user()->company;
            $subscription = $company->subscription;
            $isUnlimitedCompany = $company->id === 1;
            
            // Company ID 1 bypasses all subscription restrictions
            if ($isUnlimitedCompany) {
                return;
            }
            
            // Check if user management features are available in the subscription
            if ($subscription && !$subscription->hasFeature('advanced_user_management')) {
                $advancedFields = [
                    'skill_management_settings',
                    'performance_settings',
                    'workspace_settings',
                    'emergency_settings'
                ];
                
                foreach ($advancedFields as $field) {
                    if ($this->filled($field)) {
                        $validator->errors()->add($field, 'Advanced user management features require a higher subscription plan.');
                    }
                }
            }
            
            // Validate department limits based on subscription
            if ($subscription && $this->filled('department_settings.max_department_levels')) {
                $maxLevels = $subscription->max_department_levels ?? 2;
                if ($this->input('department_settings.max_department_levels') > $maxLevels) {
                    $validator->errors()->add(
                        'department_settings.max_department_levels',
                        "Your subscription allows a maximum of {$maxLevels} department levels."
                    );
                }
            }
            
            // Validate custom roles limit
            if ($subscription && $this->filled('role_permission_settings.max_custom_roles')) {
                $maxRoles = $subscription->max_custom_roles ?? 5;
                if ($this->input('role_permission_settings.max_custom_roles') > $maxRoles) {
                    $validator->errors()->add(
                        'role_permission_settings.max_custom_roles',
                        "Your subscription allows a maximum of {$maxRoles} custom roles."
                    );
                }
            }
        });
    }
}