<?php

namespace App\Domains\Core\Models\Settings;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\HRSettingsOverride;
use Silber\Bouncer\Database\Role;

class HRSettings extends SettingCategory
{
    public const OVERRIDABLE_SETTINGS = [
        'available_break_durations',
        'default_break_duration',
        'allow_custom_break_duration',
        'max_breaks_per_day',
        'max_break_minutes_per_day',
        'auto_approve_breaks',
        'require_gps',
        'round_to_minutes',
        'weekly_overtime_threshold_minutes',
        'overtime_multiplier',
        'double_time_threshold_minutes',
        'double_time_multiplier',
        'require_approval',
        'approval_threshold_hours',
        'state_overtime_rules',
        'is_overtime_exempt',
    ];

    public function getCategory(): string
    {
        return 'hr';
    }

    public function getAttributes(): array
    {
        return [
            'hr_settings',
        ];
    }

    public function isTimeClockEnabled(): bool
    {
        return $this->getHrSetting('time_clock_enabled', true);
    }

    public function setTimeClockEnabled(bool $enabled): self
    {
        return $this->setHrSetting('time_clock_enabled', $enabled);
    }

    public function requireGPS(): bool
    {
        return $this->getHrSetting('require_gps', false);
    }

    public function setRequireGPS(bool $require): self
    {
        return $this->setHrSetting('require_gps', $require);
    }

    public function getAllowedIPs(): ?array
    {
        return $this->getHrSetting('allowed_ips');
    }

    public function setAllowedIPs(?array $ips): self
    {
        return $this->setHrSetting('allowed_ips', $ips);
    }

    public function getRoundToMinutes(): int
    {
        return $this->getHrSetting('round_to_minutes', 15);
    }

    public function setRoundToMinutes(int $minutes): self
    {
        return $this->setHrSetting('round_to_minutes', $minutes);
    }

    public function getAutoClockOutHours(): int
    {
        return $this->getHrSetting('auto_clock_out_hours', 12);
    }

    public function setAutoClockOutHours(int $hours): self
    {
        return $this->setHrSetting('auto_clock_out_hours', $hours);
    }

    public function getWeeklyOvertimeThresholdMinutes(): int
    {
        return $this->getHrSetting('weekly_overtime_threshold_minutes', 2400);
    }

    public function setWeeklyOvertimeThresholdMinutes(int $minutes): self
    {
        return $this->setHrSetting('weekly_overtime_threshold_minutes', $minutes);
    }

    public function getOvertimeMultiplier(): float
    {
        return $this->getHrSetting('overtime_multiplier', 1.5);
    }

    public function setOvertimeMultiplier(float $multiplier): self
    {
        return $this->setHrSetting('overtime_multiplier', $multiplier);
    }

    public function getDoubleTimeMultiplier(): float
    {
        return $this->getHrSetting('double_time_multiplier', 2.0);
    }

    public function setDoubleTimeMultiplier(float $multiplier): self
    {
        return $this->setHrSetting('double_time_multiplier', $multiplier);
    }

    public function getDoubleTimeThresholdMinutes(): ?int
    {
        return $this->getHrSetting('double_time_threshold_minutes');
    }

    public function setDoubleTimeThresholdMinutes(?int $minutes): self
    {
        return $this->setHrSetting('double_time_threshold_minutes', $minutes);
    }

    public function getRequiredBreakMinutes(): int
    {
        return $this->getHrSetting('required_break_minutes', 30);
    }

    public function setRequiredBreakMinutes(int $minutes): self
    {
        return $this->setHrSetting('required_break_minutes', $minutes);
    }

    public function getBreakThresholdMinutes(): int
    {
        return $this->getHrSetting('break_threshold_minutes', 360);
    }

    public function setBreakThresholdMinutes(int $minutes): self
    {
        return $this->setHrSetting('break_threshold_minutes', $minutes);
    }

    public function autoDeductBreaks(): bool
    {
        return $this->getHrSetting('auto_deduct_breaks', false);
    }

    public function setAutoDeductBreaks(bool $autoDeduct): self
    {
        return $this->setHrSetting('auto_deduct_breaks', $autoDeduct);
    }

    public function getPayPeriodFrequency(): string
    {
        return $this->getHrSetting('pay_period_frequency', 'biweekly');
    }

    public function setPayPeriodFrequency(string $frequency): self
    {
        return $this->setHrSetting('pay_period_frequency', $frequency);
    }

    public function requireApproval(): bool
    {
        return $this->getHrSetting('require_approval', true);
    }

    public function setRequireApproval(bool $require): self
    {
        return $this->setHrSetting('require_approval', $require);
    }

    public function getApprovalThresholdHours(): ?float
    {
        return $this->getHrSetting('approval_threshold_hours', 8.0);
    }

    public function setApprovalThresholdHours(?float $hours): self
    {
        return $this->setHrSetting('approval_threshold_hours', $hours);
    }

    public function getPtoTypes(): array
    {
        return $this->getHrSetting('pto_types', [
            'vacation' => ['name' => 'Vacation', 'requires_approval' => true],
            'sick' => ['name' => 'Sick Leave', 'requires_approval' => false],
            'personal' => ['name' => 'Personal Day', 'requires_approval' => true],
        ]);
    }

    public function setPtoTypes(array $types): self
    {
        return $this->setHrSetting('pto_types', $types);
    }

    public function getMinimumPtoNoticeHours(): int
    {
        return $this->getHrSetting('minimum_pto_notice_hours', 48);
    }

    public function setMinimumPtoNoticeHours(int $hours): self
    {
        return $this->setHrSetting('minimum_pto_notice_hours', $hours);
    }

    public function getDefaultExportFormat(): string
    {
        return $this->getHrSetting('default_export_format', 'csv');
    }

    public function setDefaultExportFormat(string $format): self
    {
        return $this->setHrSetting('default_export_format', $format);
    }

    public function includePtoInExport(): bool
    {
        return $this->getHrSetting('include_pto_in_export', true);
    }

    public function setIncludePtoInExport(bool $include): self
    {
        return $this->setHrSetting('include_pto_in_export', $include);
    }

    public function getPayrollIntegration(): ?string
    {
        return $this->getHrSetting('payroll_integration');
    }

    public function setPayrollIntegration(?string $integration): self
    {
        return $this->setHrSetting('payroll_integration', $integration);
    }

    public function notifyOnMissedClockOut(): bool
    {
        return $this->getHrSetting('notify_missed_clock_out', true);
    }

    public function setNotifyOnMissedClockOut(bool $notify): self
    {
        return $this->setHrSetting('notify_missed_clock_out', $notify);
    }

    public function notifyOnOvertime(): bool
    {
        return $this->getHrSetting('notify_on_overtime', true);
    }

    public function setNotifyOnOvertime(bool $notify): self
    {
        return $this->setHrSetting('notify_on_overtime', $notify);
    }

    public function getOvertimeNotificationEmail(): ?string
    {
        return $this->getHrSetting('overtime_notification_email');
    }

    public function setOvertimeNotificationEmail(?string $email): self
    {
        return $this->setHrSetting('overtime_notification_email', $email);
    }

    public function getAvailableBreakDurations(): array
    {
        return $this->getHrSetting('available_break_durations', [15, 30, 45, 60]);
    }

    public function setAvailableBreakDurations(array $durations): self
    {
        return $this->setHrSetting('available_break_durations', $durations);
    }

    public function getDefaultBreakDuration(): int
    {
        return $this->getHrSetting('default_break_duration', 30);
    }

    public function setDefaultBreakDuration(int $duration): self
    {
        return $this->setHrSetting('default_break_duration', $duration);
    }

    public function allowCustomBreakDuration(): bool
    {
        return $this->getHrSetting('allow_custom_break_duration', false);
    }

    public function setAllowCustomBreakDuration(bool $allow): self
    {
        return $this->setHrSetting('allow_custom_break_duration', $allow);
    }

    public function getMaxBreaksPerDay(): ?int
    {
        return $this->getHrSetting('max_breaks_per_day');
    }

    public function setMaxBreaksPerDay(?int $max): self
    {
        return $this->setHrSetting('max_breaks_per_day', $max);
    }

    public function getMaxBreakMinutesPerDay(): ?int
    {
        return $this->getHrSetting('max_break_minutes_per_day');
    }

    public function setMaxBreakMinutesPerDay(?int $minutes): self
    {
        return $this->setHrSetting('max_break_minutes_per_day', $minutes);
    }

    public function autoApproveBreaks(): bool
    {
        return $this->getHrSetting('auto_approve_breaks', false);
    }

    public function setAutoApproveBreaks(bool $auto): self
    {
        return $this->setHrSetting('auto_approve_breaks', $auto);
    }

    public function getRoleBreakPolicies(): array
    {
        return $this->getHrSetting('role_break_policies', []);
    }

    public function setRoleBreakPolicies(array $policies): self
    {
        return $this->setHrSetting('role_break_policies', $policies);
    }

    protected function getHrSetting(string $key, mixed $default = null): mixed
    {
        $hrSettings = $this->get('hr_settings', []);

        return $hrSettings[$key] ?? $default;
    }

    protected function setHrSetting(string $key, mixed $value): self
    {
        $hrSettings = $this->get('hr_settings', []);
        $hrSettings[$key] = $value;
        $this->set('hr_settings', $hrSettings);

        return $this;
    }

    public function getTimeClockSettings(): array
    {
        return [
            'enabled' => $this->isTimeClockEnabled(),
            'require_gps' => $this->requireGPS(),
            'allowed_ips' => $this->getAllowedIPs(),
            'round_to_minutes' => $this->getRoundToMinutes(),
            'auto_clock_out_hours' => $this->getAutoClockOutHours(),
        ];
    }

    public function getStateOvertimeRules(): string
    {
        return $this->getHrSetting('state_overtime_rules', 'federal');
    }

    public function setStateOvertimeRules(string $rules): self
    {
        return $this->setHrSetting('state_overtime_rules', $rules);
    }

    public function getOvertimeSettings(): array
    {
        return [
            'weekly_threshold_minutes' => $this->getWeeklyOvertimeThresholdMinutes(),
            'overtime_multiplier' => $this->getOvertimeMultiplier(),
            'double_time_multiplier' => $this->getDoubleTimeMultiplier(),
            'double_time_threshold_minutes' => $this->getDoubleTimeThresholdMinutes(),
            'state_overtime_rules' => $this->getStateOvertimeRules(),
        ];
    }

    public function getAllSettings(): array
    {
        $settings = $this->get('hr_settings', []);
        
        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }
        
        return $settings;
    }

    public function setAllSettings(array $settings): self
    {
        $this->set('hr_settings', $settings);

        return $this;
    }

    public function resolveForUser(User $user): array
    {
        $resolved = $this->getAllSettings();

        foreach ($user->roles as $role) {
            $roleOverrides = $this->getRoleOverrides($role->id);
            $resolved = array_merge($resolved, $roleOverrides);
        }

        $userOverrides = $this->getUserOverrides($user->id);
        $resolved = array_merge($resolved, $userOverrides);

        return $resolved;
    }

    public function getRoleOverrides(int $roleId): array
    {
        return HRSettingsOverride::getForRole($this->model->company_id, $roleId);
    }

    public function getUserOverrides(int $userId): array
    {
        return HRSettingsOverride::getForUser($this->model->company_id, $userId);
    }

    public function setRoleOverride(int $roleId, string $key, mixed $value): void
    {
        if (!in_array($key, self::OVERRIDABLE_SETTINGS)) {
            throw new \InvalidArgumentException("Setting '{$key}' cannot be overridden");
        }

        HRSettingsOverride::setOverride(
            $this->model->company_id,
            Role::class,
            $roleId,
            $key,
            $value
        );
    }

    public function setUserOverride(int $userId, string $key, mixed $value): void
    {
        // Special handling for is_overtime_exempt - save directly to user model
        if ($key === 'is_overtime_exempt') {
            $user = User::find($userId);
            if ($user) {
                $user->is_overtime_exempt = (bool) $value;
                $user->save();
            }
            return;
        }

        if (!in_array($key, self::OVERRIDABLE_SETTINGS)) {
            throw new \InvalidArgumentException("Setting '{$key}' cannot be overridden");
        }

        HRSettingsOverride::setOverride(
            $this->model->company_id,
            User::class,
            $userId,
            $key,
            $value
        );
    }

    public function removeRoleOverride(int $roleId, ?string $key = null): void
    {
        HRSettingsOverride::removeOverride($this->model->company_id, Role::class, $roleId, $key);
    }

    public function removeUserOverride(int $userId, ?string $key = null): void
    {
        HRSettingsOverride::removeOverride($this->model->company_id, User::class, $userId, $key);
    }

    public function hasRoleOverrides(int $roleId): bool
    {
        return HRSettingsOverride::hasOverrides($this->model->company_id, Role::class, $roleId);
    }

    public function hasUserOverrides(int $userId): bool
    {
        return HRSettingsOverride::hasOverrides($this->model->company_id, User::class, $userId);
    }

    public function getAllRoleOverrides(int $roleId): array
    {
        return $this->getRoleOverrides($roleId);
    }

    public function getAllUserOverrides(int $userId): array
    {
        return $this->getUserOverrides($userId);
    }
}
