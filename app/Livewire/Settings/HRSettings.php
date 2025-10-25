<?php

namespace App\Livewire\Settings;

use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings as HRSettingsModel;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\HRSettingsOverride;
use Livewire\Component;
use Silber\Bouncer\Database\Role;

class HRSettings extends Component
{
    public $activeTab = 'breaks';

    public $availableBreakDurations = [];

    public $newBreakDuration = null;

    public $defaultBreakDuration = 30;

    public $allowCustomBreakDuration = false;

    public $maxBreaksPerDay = null;

    public $maxBreakMinutesPerDay = null;

    public $autoApproveBreaks = false;

    public $roleBreakPolicies = [];

    public $requireGPS = false;

    public $allowedIPsString = '';

    public $roundToMinutes = 15;

    public $autoClockOutHours = 12;

    public $weeklyOvertimeThreshold = 40;

    public $overtimeMultiplier = 1.5;

    public $doubleTimeThreshold = 12;

    public $doubleTimeMultiplier = 2.0;

    public $stateOvertimeRules = 'federal';

    public $requireApproval = true;

    public $approvalThresholdHours = 8;

    public $selectedRole = null;

    public $roleOverrides = [];

    public $selectedUser = null;

    public $userOverrides = [];

    public $employeeSearch = '';

    public $showOverrideModal = false;
    
    public $overrideSettingKey = null;
    
    public $overrideSettingLabel = '';
    
    public $overrideValue = null;

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        $this->availableBreakDurations = $hrSettings->getAvailableBreakDurations();
        $this->defaultBreakDuration = $hrSettings->getDefaultBreakDuration();
        $this->allowCustomBreakDuration = $hrSettings->allowCustomBreakDuration();
        $this->maxBreaksPerDay = $hrSettings->getMaxBreaksPerDay();
        $this->maxBreakMinutesPerDay = $hrSettings->getMaxBreakMinutesPerDay();
        $this->autoApproveBreaks = $hrSettings->autoApproveBreaks();
        $this->roleBreakPolicies = $hrSettings->getRoleBreakPolicies();

        $this->requireGPS = $hrSettings->requireGPS();
        $this->allowedIPsString = $hrSettings->getAllowedIPs() ? implode("\n", $hrSettings->getAllowedIPs()) : '';
        $this->roundToMinutes = $hrSettings->getRoundToMinutes();
        $this->autoClockOutHours = $hrSettings->getAutoClockOutHours();

        $this->weeklyOvertimeThreshold = $hrSettings->getWeeklyOvertimeThresholdMinutes() / 60;
        $this->overtimeMultiplier = $hrSettings->getOvertimeMultiplier();
        $this->doubleTimeThreshold = $hrSettings->getDoubleTimeThresholdMinutes() ? $hrSettings->getDoubleTimeThresholdMinutes() / 60 : 60;
        $this->doubleTimeMultiplier = $hrSettings->getDoubleTimeMultiplier();
        $this->stateOvertimeRules = $hrSettings->getStateOvertimeRules();

        $this->requireApproval = $hrSettings->requireApproval();
        $this->approvalThresholdHours = $hrSettings->getApprovalThresholdHours();
    }

    public function addBreakDuration()
    {
        if (! $this->newBreakDuration || $this->newBreakDuration <= 0) {
            $this->dispatch('error', message: 'Please enter a valid break duration');

            return;
        }

        if (in_array($this->newBreakDuration, $this->availableBreakDurations)) {
            $this->dispatch('error', message: 'This break duration already exists');

            return;
        }

        $this->availableBreakDurations[] = (int) $this->newBreakDuration;
        sort($this->availableBreakDurations);
        $this->newBreakDuration = null;

        $this->dispatch('success', message: 'Break duration added');
    }

    public function removeBreakDuration($duration)
    {
        $this->availableBreakDurations = array_values(
            array_filter($this->availableBreakDurations, fn ($d) => $d != $duration)
        );

        $this->dispatch('success', message: 'Break duration removed');
    }

    public function addPresetDuration($duration)
    {
        if (! in_array($duration, $this->availableBreakDurations)) {
            $this->availableBreakDurations[] = $duration;
            sort($this->availableBreakDurations);
            $this->dispatch('success', message: "{$duration} minute break added");
        }
    }

    public function saveBreakSettings()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        $hrSettings->setAvailableBreakDurations($this->availableBreakDurations);
        $hrSettings->setDefaultBreakDuration($this->defaultBreakDuration);
        $hrSettings->setAllowCustomBreakDuration($this->allowCustomBreakDuration);
        $hrSettings->setMaxBreaksPerDay($this->maxBreaksPerDay);
        $hrSettings->setMaxBreakMinutesPerDay($this->maxBreakMinutesPerDay);
        $hrSettings->setAutoApproveBreaks($this->autoApproveBreaks);
        $hrSettings->setRoleBreakPolicies($this->roleBreakPolicies);

        $settings->save();

        $this->dispatch('success', message: 'Break settings saved successfully');
    }

    public function saveTimeClockSettings()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        $hrSettings->setRequireGPS($this->requireGPS);

        $allowedIPs = array_filter(array_map('trim', explode("\n", $this->allowedIPsString)));
        $hrSettings->setAllowedIPs($allowedIPs ?: null);

        $hrSettings->setRoundToMinutes($this->roundToMinutes);
        $hrSettings->setAutoClockOutHours($this->autoClockOutHours);

        $settings->save();

        $this->dispatch('success', message: 'Time clock settings saved successfully');
    }

    public function saveOvertimeSettings()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        $hrSettings->setWeeklyOvertimeThresholdMinutes($this->weeklyOvertimeThreshold * 60);
        $hrSettings->setOvertimeMultiplier($this->overtimeMultiplier);
        $hrSettings->setDoubleTimeThresholdMinutes($this->doubleTimeThreshold ? $this->doubleTimeThreshold * 60 : null);
        $hrSettings->setDoubleTimeMultiplier($this->doubleTimeMultiplier);
        $hrSettings->setStateOvertimeRules($this->stateOvertimeRules);

        $settings->save();

        $this->dispatch('success', message: 'Overtime settings saved successfully');
    }

    public function saveApprovalSettings()
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        $hrSettings->setRequireApproval($this->requireApproval);
        $hrSettings->setApprovalThresholdHours($this->approvalThresholdHours);

        $settings->save();

        $this->dispatch('success', message: 'Approval settings saved successfully');
    }

    public function updatedSelectedRole($roleId)
    {
        if (!$roleId) {
            $this->roleOverrides = [];
            return;
        }

        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);
        $this->roleOverrides = $hrSettings->getAllRoleOverrides($roleId);
    }

    public function setRoleOverride($key)
    {
        if (!$this->selectedRole) {
            $this->dispatch('error', message: 'No role selected');
            return;
        }

        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        // Map camelCase to snake_case
        $snakeCaseKey = match($key) {
            'availableBreakDurations' => 'available_break_durations',
            'maxBreaksPerDay' => 'max_breaks_per_day',
            'requireGPS' => 'require_gps',
            'weeklyOvertimeThreshold' => 'weekly_overtime_threshold_minutes',
            'stateOvertimeRules' => 'state_overtime_rules',
            'autoApproveBreaks' => 'auto_approve_breaks',
            default => $key
        };

        $value = $this->getPropertyValueForOverride($snakeCaseKey);
        
        if ($value === null) {
            $this->dispatch('error', message: "Property {$snakeCaseKey} not found");
            return;
        }

        try {
            $hrSettings->setRoleOverride($this->selectedRole, $snakeCaseKey, $value);
            $this->updatedSelectedRole($this->selectedRole);
            $this->dispatch('success', message: "Role override set successfully");
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function removeRoleOverride($key)
    {
        if (!$this->selectedRole) {
            return;
        }

        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);
        $hrSettings->removeRoleOverride($this->selectedRole, $key);

        unset($this->roleOverrides[$key]);
        $this->dispatch('success', message: "Override removed for {$key}");
    }

    public function updatedSelectedUser($userId)
    {
        if (!$userId) {
            $this->userOverrides = [];
            return;
        }

        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);
        $this->userOverrides = $hrSettings->getAllUserOverrides($userId);
    }

    public function setUserOverride($key)
    {
        if (!$this->selectedUser) {
            $this->dispatch('error', message: 'No employee selected');
            return;
        }

        $this->overrideSettingKey = $key;
        $this->overrideSettingLabel = $this->getSettingLabel($key);
        
        // Get the current value and convert for display if needed
        $value = $this->getPropertyValueForOverride($key);
        
        // Convert values for display in modal
        if (in_array($key, ['weekly_overtime_threshold_minutes', 'double_time_threshold_minutes'])) {
            // Convert minutes to hours
            $this->overrideValue = $value ? $value / 60 : null;
        } elseif ($key === 'available_break_durations' && is_array($value)) {
            // Convert array to comma-separated string
            $this->overrideValue = implode(',', $value);
        } else {
            $this->overrideValue = $value;
        }
        
        $this->showOverrideModal = true;
    }

    public function saveUserOverride()
    {
        if (!$this->selectedUser || !$this->overrideSettingKey) {
            $this->dispatch('error', message: 'Invalid override configuration');
            return;
        }

        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);

        // Convert the value based on the setting type
        $value = $this->overrideValue;
        
        // Convert hours to minutes for storage
        if (in_array($this->overrideSettingKey, ['weekly_overtime_threshold_minutes', 'double_time_threshold_minutes'])) {
            $value = $value ? $value * 60 : null;
        }
        
        // Handle break durations (convert comma-separated string to array)
        if ($this->overrideSettingKey === 'available_break_durations' && is_string($value)) {
            $value = array_map('intval', array_filter(array_map('trim', explode(',', $value))));
        }

        try {
            $hrSettings->setUserOverride($this->selectedUser, $this->overrideSettingKey, $value);
            $this->updatedSelectedUser($this->selectedUser);
            $this->showOverrideModal = false;
            $this->dispatch('success', message: "Employee override set successfully");
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function cancelOverride()
    {
        $this->showOverrideModal = false;
        $this->overrideSettingKey = null;
        $this->overrideSettingLabel = '';
        $this->overrideValue = null;
    }

    protected function getSettingLabel($key)
    {
        $labels = [
            'available_break_durations' => 'Available Break Durations',
            'max_breaks_per_day' => 'Max Breaks Per Day',
            'max_break_minutes_per_day' => 'Max Break Minutes Per Day',
            'allow_custom_break_duration' => 'Allow Custom Break Duration',
            'auto_approve_breaks' => 'Auto-Approve Breaks',
            'require_gps' => 'Require GPS',
            'round_to_minutes' => 'Round To Minutes',
            'weekly_overtime_threshold_minutes' => 'Weekly Overtime Threshold',
            'overtime_multiplier' => 'Overtime Multiplier',
            'double_time_multiplier' => 'Double Time Multiplier',
            'double_time_threshold_minutes' => 'Double Time Threshold',
            'state_overtime_rules' => 'State Overtime Rules',
            'is_overtime_exempt' => 'Exempt from Overtime',
            'require_approval' => 'Require Approval',
            'approval_threshold_hours' => 'Approval Threshold Hours',
        ];

        return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
    
    protected function getPropertyValueForOverride($propertyName)
    {
        switch ($propertyName) {
            case 'available_break_durations':
                return $this->availableBreakDurations;
            case 'max_breaks_per_day':
                return $this->maxBreaksPerDay;
            case 'max_break_minutes_per_day':
                return $this->maxBreakMinutesPerDay;
            case 'allow_custom_break_duration':
                return $this->allowCustomBreakDuration;
            case 'auto_approve_breaks':
                return $this->autoApproveBreaks;
            case 'require_gps':
                return $this->requireGPS;
            case 'round_to_minutes':
                return $this->roundToMinutes;
            case 'weekly_overtime_threshold_minutes':
                return $this->weeklyOvertimeThreshold * 60;
            case 'overtime_multiplier':
                return $this->overtimeMultiplier;
            case 'double_time_multiplier':
                return $this->doubleTimeMultiplier;
            case 'double_time_threshold_minutes':
                return $this->doubleTimeThreshold ? $this->doubleTimeThreshold * 60 : null;
            case 'state_overtime_rules':
                return $this->stateOvertimeRules;
            case 'is_overtime_exempt':
                return $this->selectedUser ? User::find($this->selectedUser)?->is_overtime_exempt : false;
            case 'require_approval':
                return $this->requireApproval;
            case 'approval_threshold_hours':
                return $this->approvalThresholdHours;
            default:
                return property_exists($this, $propertyName) ? $this->$propertyName : null;
        }
    }

    public function removeUserOverride($key)
    {
        if (!$this->selectedUser) {
            return;
        }

        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);
        $hrSettings->removeUserOverride($this->selectedUser, $key);

        unset($this->userOverrides[$key]);
        $this->dispatch('success', message: "Override removed for {$key}");
    }

    public function getFilteredEmployeesProperty()
    {
        $query = User::where('company_id', auth()->user()->company_id);

        if ($this->employeeSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->employeeSearch . '%')
                    ->orWhere('email', 'like', '%' . $this->employeeSearch . '%');
            });
        }

        return $query->orderBy('name')->limit(20)->get();
    }

    public function hasEmployeeOverrides($userId)
    {
        $settings = Setting::where('company_id', auth()->user()->company_id)->first();
        $hrSettings = new HRSettingsModel($settings);
        return $hrSettings->hasUserOverrides($userId);
    }

    public function selectEmployee($employeeId)
    {
        $this->selectedUser = $employeeId;
        $this->updatedSelectedUser($employeeId);
    }

    public function render()
    {
        $companyId = auth()->user()->company_id;
        
        $roles = Role::where(function($q) use ($companyId) {
            $q->whereNull('scope')->orWhere('scope', $companyId);
        })->get();

        return view('livewire.settings.hr-settings', [
            'roles' => $roles,
        ])->layout('components.layouts.app', [
            'sidebarContext' => 'settings',
        ]);
    }
}
