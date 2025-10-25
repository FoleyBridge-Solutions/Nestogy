<div class="space-y-6">
    <div>
        <flux:heading size="xl">HR & Time Tracking Settings</flux:heading>
        <flux:subheading>Configure break policies, time clock settings, and overtime rules</flux:subheading>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model="activeTab">
            <flux:tab name="breaks" icon="pause">Break Policies</flux:tab>
            <flux:tab name="timeclock" icon="clock">Time Clock</flux:tab>
            <flux:tab name="overtime" icon="chart-bar">Overtime</flux:tab>
            <flux:tab name="approvals" icon="check-circle">Approvals</flux:tab>
            <flux:tab name="role-overrides" icon="user-group">Role Overrides</flux:tab>
            <flux:tab name="employee-overrides" icon="user">Employee Overrides</flux:tab>
        </flux:tabs>

        {{-- Break Policies Tab --}}
        <flux:tab.panel name="breaks">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Available Break Durations</flux:heading>
                    <flux:subheading>Choose which break durations employees can select when taking a break</flux:subheading>
                </div>

                {{-- Current Break Durations --}}
                @if($availableBreakDurations && count($availableBreakDurations) > 0)
                    <div>
                        <flux:label>Current Break Options</flux:label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($availableBreakDurations as $duration)
                                <flux:badge color="blue" size="lg">
                                    {{ $duration }} minutes
                                    <button 
                                        wire:click="removeBreakDuration({{ $duration }})" 
                                        type="button"
                                        class="ml-2 hover:text-red-600">
                                        <flux:icon.x-mark variant="mini" />
                                    </button>
                                </flux:badge>
                            @endforeach
                        </div>
                    </div>
                @else
                    <flux:callout icon="information-circle" variant="warning">
                        No break durations configured. Add at least one duration below.
                    </flux:callout>
                @endif

                <flux:separator />

                {{-- Add New Duration --}}
                <div>
                    <flux:label>Add Custom Duration</flux:label>
                    <div class="flex gap-2 mt-2">
                        <flux:input 
                            type="number" 
                            placeholder="Enter minutes (e.g., 20)" 
                            wire:model="newBreakDuration"
                            min="1"
                            max="480" />
                        <flux:button 
                            wire:click="addBreakDuration" 
                            icon="plus"
                            variant="primary">
                            Add
                        </flux:button>
                    </div>
                </div>

                {{-- Quick Presets --}}
                <div>
                    <flux:label>Quick Presets</flux:label>
                    <flux:subheading>Add common break durations with one click</flux:subheading>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <flux:button variant="ghost" size="sm" wire:click="addPresetDuration(5)" icon="clock">5 min</flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="addPresetDuration(10)" icon="clock">10 min</flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="addPresetDuration(15)" icon="clock">15 min</flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="addPresetDuration(30)" icon="clock">30 min</flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="addPresetDuration(45)" icon="clock">45 min</flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="addPresetDuration(60)" icon="clock">1 hour</flux:button>
                    </div>
                </div>

                <flux:separator />

                {{-- Break Limits --}}
                <div>
                    <flux:heading>Break Limits</flux:heading>
                    <flux:subheading>Set maximum limits for employee breaks (leave empty for unlimited)</flux:subheading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <flux:input 
                            type="number" 
                            label="Max Breaks Per Day"
                            wire:model="maxBreaksPerDay" 
                            placeholder="Unlimited"
                            min="0" />
                        
                        <flux:input 
                            type="number" 
                            label="Max Break Minutes Per Day"
                            wire:model="maxBreakMinutesPerDay" 
                            placeholder="Unlimited"
                            min="0" />
                    </div>
                </div>

                <flux:separator />

                {{-- Break Settings --}}
                <div class="space-y-4">
                    <flux:heading>Break Options</flux:heading>
                    
                    <div class="flex items-start gap-3">
                        <flux:switch wire:model="allowCustomBreakDuration" />
                        <div class="flex-1">
                            <flux:label>Allow Custom Break Duration</flux:label>
                            <flux:subheading>Employees can enter any break duration instead of selecting from preset options</flux:subheading>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <flux:switch wire:model="autoApproveBreaks" />
                        <div class="flex-1">
                            <flux:label>Auto-Approve Breaks</flux:label>
                            <flux:subheading>Break periods don't require manager approval and are automatically approved</flux:subheading>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <flux:button 
                        variant="primary" 
                        wire:click="saveBreakSettings"
                        icon="check">
                        Save Break Settings
                    </flux:button>
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Time Clock Tab --}}
        <flux:tab.panel name="timeclock">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Time Clock Settings</flux:heading>
                    <flux:subheading>Configure how employees clock in and out</flux:subheading>
                </div>

                {{-- GPS Tracking --}}
                <div class="flex items-start gap-3">
                    <flux:switch wire:model="requireGPS" />
                    <div class="flex-1">
                        <flux:label>Require GPS Location</flux:label>
                        <flux:subheading>Employees must share their GPS location when clocking in/out</flux:subheading>
                    </div>
                </div>

                <flux:separator />

                {{-- IP Whitelisting --}}
                <div>
                    <flux:label>Allowed IP Addresses (Optional)</flux:label>
                    <flux:subheading>Enter one IP address or CIDR range per line (e.g., 192.168.1.0/24). Leave empty to allow any IP.</flux:subheading>
                    <flux:textarea 
                        wire:model="allowedIPsString"
                        rows="4"
                        placeholder="192.168.1.100&#10;10.0.0.0/8"
                        class="mt-2 font-mono text-sm" />
                </div>

                <flux:separator />

                {{-- Time Rounding --}}
                <div>
                    <flux:label>Round Clock Times</flux:label>
                    <flux:subheading>Round clock in/out times to the nearest interval</flux:subheading>
                    <flux:select wire:model="roundToMinutes" class="mt-2">
                        <option value="0">No rounding (exact time)</option>
                        <option value="5">5 minutes</option>
                        <option value="10">10 minutes</option>
                        <option value="15">15 minutes</option>
                        <option value="30">30 minutes</option>
                    </flux:select>
                </div>

                {{-- Auto Clock Out --}}
                <div>
                    <flux:label>Auto Clock Out</flux:label>
                    <flux:subheading>Automatically clock out employees if they forget (hours since clock in)</flux:subheading>
                    <flux:input 
                        type="number" 
                        wire:model="autoClockOutHours"
                        min="1"
                        max="24"
                        class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <flux:button 
                        variant="primary" 
                        wire:click="saveTimeClockSettings"
                        icon="check">
                        Save Time Clock Settings
                    </flux:button>
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Overtime Tab --}}
        <flux:tab.panel name="overtime">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Overtime Calculation</flux:heading>
                    <flux:subheading>Configure when non-exempt employees earn overtime pay</flux:subheading>
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                    <strong>Note:</strong> Overtime rules only apply to non-exempt (hourly) employees. Exempt employees (salary) do not receive overtime pay regardless of hours worked.
                </div>

                {{-- Basic Settings --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:field>
                            <flux:label>Weekly Hours Threshold</flux:label>
                            <flux:input 
                                type="number" 
                                wire:model="weeklyOvertimeThreshold"
                                step="0.5"
                                min="0" />
                            <flux:description>Federal standard is 40 hours per week</flux:description>
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Overtime Pay Rate</flux:label>
                            <flux:input 
                                type="number" 
                                wire:model="overtimeMultiplier"
                                step="0.1"
                                min="1" />
                            <flux:description>Typically 1.5x (time and a half)</flux:description>
                        </flux:field>
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                        <strong>How it works:</strong> Employees earn {{ $overtimeMultiplier }}x pay for hours worked over {{ $weeklyOvertimeThreshold }} in a workweek
                    </div>
                </div>

                <flux:separator />

                {{-- Advanced: State Rules --}}
                <details class="group">
                    <summary class="cursor-pointer flex items-center gap-2">
                        <flux:heading size="sm">Advanced: State-Specific Rules</flux:heading>
                        <flux:icon.chevron-down class="w-4 h-4 transition-transform group-open:rotate-180" />
                    </summary>
                    
                    <div class="mt-4 space-y-4">
                        <flux:field>
                            <flux:label>Overtime Rules</flux:label>
                            <flux:select wire:model="stateOvertimeRules">
                                <option value="federal">Federal (FLSA) - Standard rules</option>
                                <option value="california">California - Includes daily overtime</option>
                            </flux:select>
                            <flux:description>
                                @if($stateOvertimeRules === 'california')
                                    California requires overtime after 8 hours/day and double-time after 12 hours/day
                                @else
                                    Federal law requires overtime only after 40 hours/week
                                @endif
                            </flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Double-Time Threshold (optional)</flux:label>
                            <flux:input 
                                type="number" 
                                wire:model="doubleTimeThreshold"
                                step="0.5"
                                min="0"
                                placeholder="Leave empty to disable" />
                            <flux:description>Hours per week before double-time pay applies (e.g., 60 hours)</flux:description>
                        </flux:field>

                        @if($doubleTimeThreshold)
                            <flux:field>
                                <flux:label>Double-Time Pay Rate</flux:label>
                                <flux:input 
                                    type="number" 
                                    wire:model="doubleTimeMultiplier"
                                    step="0.1"
                                    min="1" />
                                <flux:description>Typically 2x regular pay</flux:description>
                            </flux:field>
                        @endif
                    </div>
                </details>

                <div class="flex justify-end">
                    <flux:button 
                        variant="primary" 
                        wire:click="saveOvertimeSettings"
                        icon="check">
                        Save Settings
                    </flux:button>
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Approvals Tab --}}
        <flux:tab.panel name="approvals">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Approval Workflow</flux:heading>
                    <flux:subheading>Configure which time entries require manager approval</flux:subheading>
                </div>

                {{-- Require Approval --}}
                <div class="flex items-start gap-3">
                    <flux:switch wire:model="requireApproval" />
                    <div class="flex-1">
                        <flux:label>Require Approval for Time Entries</flux:label>
                        <flux:subheading>Time entries must be approved by a manager before they can be exported to payroll</flux:subheading>
                    </div>
                </div>

                @if($requireApproval)
                    <flux:separator />

                    {{-- Approval Threshold --}}
                    <div>
                        <flux:label>Auto-Approve Threshold (Optional)</flux:label>
                        <flux:subheading>Automatically approve time entries under this many hours per day</flux:subheading>
                        <flux:input 
                            type="number" 
                            wire:model="approvalThresholdHours"
                            step="0.5"
                            min="0"
                            placeholder="e.g., 8"
                            class="mt-2" />
                        <flux:subheading class="mt-2">
                            Time entries under {{ $approvalThresholdHours }} hours will be auto-approved
                        </flux:subheading>
                    </div>

                    <flux:callout icon="information-circle" variant="info">
                        Break periods follow the "Auto-Approve Breaks" setting in the Break Policies tab.
                    </flux:callout>
                @endif

                <div class="flex justify-end">
                    <flux:button 
                        variant="primary" 
                        wire:click="saveApprovalSettings"
                        icon="check">
                        Save Approval Settings
                    </flux:button>
                </div>
            </flux:card>
        </flux:tab.panel>

        {{-- Role Overrides Tab --}}
        <flux:tab.panel name="role-overrides">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Role-Based Setting Overrides</flux:heading>
                    <flux:subheading>Configure different HR settings for specific roles</flux:subheading>
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                    Role overrides apply to all employees with this role. Individual employee overrides take precedence.
                </div>

                <flux:separator />

                <div>
                    <flux:label>Select Role</flux:label>
                    <flux:select wire:model.live="selectedRole" class="mt-2">
                        <option value="">Choose a role...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->title }}</option>
                        @endforeach
                    </flux:select>
                </div>

                @if($selectedRole)
                    @php
                        $selectedRoleModel = $roles->firstWhere('id', $selectedRole);
                    @endphp

                    <flux:separator />

                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                        <div>
                            <flux:heading size="lg">{{ $selectedRoleModel->title }}</flux:heading>
                            <flux:subheading class="mt-1">Configure overrides for this role</flux:subheading>
                        </div>
                        <flux:button 
                            variant="ghost" 
                            wire:click="$set('selectedRole', null)"
                            icon="x-mark">
                            Clear
                        </flux:button>
                    </div>

                    <flux:separator />

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {{-- Break Durations --}}
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm">Break Durations</flux:heading>
                                @if(isset($roleOverrides['available_break_durations']))
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </div>
                            
                            @if(isset($roleOverrides['available_break_durations']))
                                <div class="flex flex-wrap gap-1 mb-3">
                                    @foreach($roleOverrides['available_break_durations'] as $duration)
                                        <flux:badge size="sm">{{ $duration }}m</flux:badge>
                                    @endforeach
                                </div>
                                <flux:button 
                                    variant="danger" 
                                    size="xs"
                                    wire:click="removeRoleOverride('available_break_durations')"
                                    icon="x-mark">
                                    Remove
                                </flux:button>
                            @else
                                <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                <flux:button 
                                    variant="primary" 
                                    size="xs"
                                    wire:click="setRoleOverride('available_break_durations')"
                                    icon="plus">
                                    Set Override
                                </flux:button>
                            @endif
                        </div>

                        {{-- Max Breaks Per Day --}}
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm">Max Breaks/Day</flux:heading>
                                @if(isset($roleOverrides['max_breaks_per_day']))
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </div>
                            
                            @if(isset($roleOverrides['max_breaks_per_day']))
                                <p class="text-sm font-medium mb-3">{{ $roleOverrides['max_breaks_per_day'] ?? 'Unlimited' }}</p>
                                <flux:button 
                                    variant="danger" 
                                    size="xs"
                                    wire:click="removeRoleOverride('max_breaks_per_day')"
                                    icon="x-mark">
                                    Remove
                                </flux:button>
                            @else
                                <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                <flux:button 
                                    variant="primary" 
                                    size="xs"
                                    wire:click="setRoleOverride('max_breaks_per_day')"
                                    icon="plus">
                                    Set Override
                                </flux:button>
                            @endif
                        </div>

                        {{-- Require GPS --}}
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm">Require GPS</flux:heading>
                                @if(isset($roleOverrides['require_gps']))
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </div>
                            
                            @if(isset($roleOverrides['require_gps']))
                                <p class="text-sm font-medium mb-3">{{ $roleOverrides['require_gps'] ? 'Required' : 'Not Required' }}</p>
                                <flux:button 
                                    variant="danger" 
                                    size="xs"
                                    wire:click="removeRoleOverride('require_gps')"
                                    icon="x-mark">
                                    Remove
                                </flux:button>
                            @else
                                <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                <flux:button 
                                    variant="primary" 
                                    size="xs"
                                    wire:click="setRoleOverride('require_gps')"
                                    icon="plus">
                                    Set Override
                                </flux:button>
                            @endif
                        </div>

                        {{-- Weekly OT Threshold --}}
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm">Weekly OT Threshold</flux:heading>
                                @if(isset($roleOverrides['weekly_overtime_threshold_minutes']))
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </div>
                            
                            @if(isset($roleOverrides['weekly_overtime_threshold_minutes']))
                                <p class="text-sm font-medium mb-3">{{ $roleOverrides['weekly_overtime_threshold_minutes'] / 60 }} hrs</p>
                                <flux:button 
                                    variant="danger" 
                                    size="xs"
                                    wire:click="removeRoleOverride('weekly_overtime_threshold_minutes')"
                                    icon="x-mark">
                                    Remove
                                </flux:button>
                            @else
                                <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                <flux:button 
                                    variant="primary" 
                                    size="xs"
                                    wire:click="setRoleOverride('weekly_overtime_threshold_minutes')"
                                    icon="plus">
                                    Set Override
                                </flux:button>
                            @endif
                        </div>

                        {{-- State OT Rules --}}
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm">State OT Rules</flux:heading>
                                @if(isset($roleOverrides['state_overtime_rules']))
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </div>
                            
                            @if(isset($roleOverrides['state_overtime_rules']))
                                <p class="text-sm font-medium mb-3">{{ ucfirst($roleOverrides['state_overtime_rules']) }}</p>
                                <flux:button 
                                    variant="danger" 
                                    size="xs"
                                    wire:click="removeRoleOverride('state_overtime_rules')"
                                    icon="x-mark">
                                    Remove
                                </flux:button>
                            @else
                                <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                <flux:button 
                                    variant="primary" 
                                    size="xs"
                                    wire:click="setRoleOverride('state_overtime_rules')"
                                    icon="plus">
                                    Set Override
                                </flux:button>
                            @endif
                        </div>

                        {{-- Auto-Approve Breaks --}}
                        <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm">Auto-Approve Breaks</flux:heading>
                                @if(isset($roleOverrides['auto_approve_breaks']))
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </div>
                            
                            @if(isset($roleOverrides['auto_approve_breaks']))
                                <p class="text-sm font-medium mb-3">{{ $roleOverrides['auto_approve_breaks'] ? 'Enabled' : 'Disabled' }}</p>
                                <flux:button 
                                    variant="danger" 
                                    size="xs"
                                    wire:click="removeRoleOverride('auto_approve_breaks')"
                                    icon="x-mark">
                                    Remove
                                </flux:button>
                            @else
                                <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                <flux:button 
                                    variant="primary" 
                                    size="xs"
                                    wire:click="setRoleOverride('auto_approve_breaks')"
                                    icon="plus">
                                    Set Override
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <flux:icon.user-group class="w-16 h-16 mx-auto mb-4 opacity-50" />
                        <p>Select a role above to configure overrides</p>
                    </div>
                @endif
            </flux:card>
        </flux:tab.panel>

        {{-- Employee Overrides Tab --}}
        <flux:tab.panel name="employee-overrides">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">Employee-Specific Overrides</flux:heading>
                    <flux:subheading>Configure personal HR overrides for individual employees</flux:subheading>
                </div>

                <flux:separator />

                <div>
                    <flux:label>Select Employee</flux:label>
                    <flux:input 
                        type="search"
                        wire:model.live.debounce.300ms="employeeSearch"
                        placeholder="Search employees..."
                        icon="magnifying-glass"
                        class="mt-2"
                    />
                </div>

                <div class="space-y-2 max-h-[400px] overflow-y-auto">
                    @foreach($this->filteredEmployees as $employee)
                        <div 
                            class="border rounded-lg p-3 cursor-pointer transition {{ $selectedUser == $employee->id ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30 border-blue-500' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                            wire:click="selectEmployee({{ $employee->id }})"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm truncate">{{ $employee->name }}</div>
                                    <div class="text-xs text-gray-500 truncate">{{ $employee->email }}</div>
                                    @if($employee->roles->count() > 0)
                                        <div class="text-xs text-gray-400 mt-1 truncate">
                                            {{ $employee->roles->pluck('title')->join(', ') }}
                                        </div>
                                    @endif
                                </div>
                                @if($this->hasEmployeeOverrides($employee->id))
                                    <flux:badge color="green" size="sm" class="ml-2 flex-shrink-0">{{ count($this->userOverrides) }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($selectedUser)
                    @php
                        $selectedEmployee = $this->filteredEmployees->firstWhere('id', $selectedUser);
                    @endphp
                    
                    @if($selectedEmployee)
                        <flux:separator />

                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div>
                                <flux:heading size="lg">{{ $selectedEmployee->name }}</flux:heading>
                                <flux:subheading class="mt-1">{{ $selectedEmployee->email }}</flux:subheading>
                                @if($selectedEmployee->roles->count() > 0)
                                    <div class="flex gap-2 mt-2">
                                        @foreach($selectedEmployee->roles as $role)
                                            <flux:badge color="blue">{{ $role->title }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <flux:button 
                                variant="ghost" 
                                wire:click="$set('selectedUser', null)"
                                icon="x-mark">
                                Clear
                            </flux:button>
                        </div>

                        <flux:separator />

                        <div>
                            <flux:heading>Override Settings</flux:heading>
                            <flux:subheading>These settings override company defaults and role-based settings</flux:subheading>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                    {{-- Break Duration Override --}}
                                    <div class="border rounded-lg p-3 hover:border-blue-300 transition min-h-[140px] flex flex-col">
                                        <div class="flex items-center justify-between mb-2">
                                            <flux:heading size="sm" class="text-xs">Break Durations</flux:heading>
                                            @if(isset($userOverrides['available_break_durations']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['available_break_durations']))
                                            <div class="flex flex-wrap gap-1 mb-3">
                                                @foreach($userOverrides['available_break_durations'] as $duration)
                                                    <flux:badge size="sm">{{ $duration }}m</flux:badge>
                                                @endforeach
                                            </div>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('available_break_durations')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('available_break_durations')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Max Breaks Per Day --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Max Breaks/Day</flux:heading>
                                            @if(isset($userOverrides['max_breaks_per_day']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['max_breaks_per_day']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['max_breaks_per_day'] ?? 'Unlimited' }}</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('max_breaks_per_day')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('max_breaks_per_day')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Max Break Minutes Per Day --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Max Break Minutes</flux:heading>
                                            @if(isset($userOverrides['max_break_minutes_per_day']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['max_break_minutes_per_day']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['max_break_minutes_per_day'] }} min</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('max_break_minutes_per_day')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('max_break_minutes_per_day')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Allow Custom Break Duration --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Custom Breaks</flux:heading>
                                            @if(isset($userOverrides['allow_custom_break_duration']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['allow_custom_break_duration']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['allow_custom_break_duration'] ? 'Allowed' : 'Not Allowed' }}</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('allow_custom_break_duration')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('allow_custom_break_duration')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Auto Approve Breaks --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Auto-Approve Breaks</flux:heading>
                                            @if(isset($userOverrides['auto_approve_breaks']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['auto_approve_breaks']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['auto_approve_breaks'] ? 'Enabled' : 'Disabled' }}</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('auto_approve_breaks')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('auto_approve_breaks')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Require GPS --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Require GPS</flux:heading>
                                            @if(isset($userOverrides['require_gps']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['require_gps']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['require_gps'] ? 'Required' : 'Not Required' }}</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('require_gps')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('require_gps')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Round To Minutes --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Time Rounding</flux:heading>
                                            @if(isset($userOverrides['round_to_minutes']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['round_to_minutes']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['round_to_minutes'] }} min</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('round_to_minutes')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('round_to_minutes')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Weekly Overtime Threshold --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Weekly OT Threshold</flux:heading>
                                            @if(isset($userOverrides['weekly_overtime_threshold_minutes']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['weekly_overtime_threshold_minutes']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['weekly_overtime_threshold_minutes'] / 60 }} hrs</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('weekly_overtime_threshold_minutes')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('weekly_overtime_threshold_minutes')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Overtime Exempt Status --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Overtime Status</flux:heading>
                                            @if($selectedEmployee && $selectedEmployee->is_overtime_exempt)
                                                <flux:badge color="yellow" size="sm">Exempt</flux:badge>
                                            @else
                                                <flux:badge color="blue" size="sm">Non-Exempt</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if($selectedEmployee && $selectedEmployee->is_overtime_exempt)
                                            <p class="text-sm font-medium mb-3">Exempt from overtime</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('is_overtime_exempt')"
                                                icon="pencil">
                                                Change Status
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Eligible for overtime pay</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('is_overtime_exempt')"
                                                icon="pencil">
                                                Mark as Exempt
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- State Overtime Rules --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">State OT Rules</flux:heading>
                                            @if(isset($userOverrides['state_overtime_rules']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['state_overtime_rules']))
                                            <p class="text-sm font-medium mb-3">{{ ucfirst($userOverrides['state_overtime_rules']) }}</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('state_overtime_rules')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('state_overtime_rules')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Double Time Threshold --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Double Time Threshold</flux:heading>
                                            @if(isset($userOverrides['double_time_threshold_minutes']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['double_time_threshold_minutes']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['double_time_threshold_minutes'] / 60 }} hrs</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('double_time_threshold_minutes')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('double_time_threshold_minutes')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Require Approval --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Require Approval</flux:heading>
                                            @if(isset($userOverrides['require_approval']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['require_approval']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['require_approval'] ? 'Required' : 'Not Required' }}</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('require_approval')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('require_approval')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>

                                    {{-- Approval Threshold Hours --}}
                                    <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <flux:heading size="sm">Approval Threshold</flux:heading>
                                            @if(isset($userOverrides['approval_threshold_hours']))
                                                <flux:badge color="green" size="sm">Active</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if(isset($userOverrides['approval_threshold_hours']))
                                            <p class="text-sm font-medium mb-3">{{ $userOverrides['approval_threshold_hours'] }} hrs</p>
                                            <flux:button 
                                                variant="danger" 
                                                size="xs"
                                                wire:click="removeUserOverride('approval_threshold_hours')"
                                                icon="x-mark">
                                                Remove
                                            </flux:button>
                                        @else
                                            <p class="text-xs text-gray-500 mb-3">Using defaults</p>
                                            <flux:button 
                                                variant="primary" 
                                                size="xs"
                                                wire:click="setUserOverride('approval_threshold_hours')"
                                                icon="plus">
                                                Set Override
                                            </flux:button>
                                        @endif
                                    </div>
                        </div>
                    @endif
                @else
                    <flux:callout icon="information-circle" variant="warning">
                        Please select an employee from the list above to configure their personal overrides
                    </flux:callout>
                @endif
            </flux:card>
        </flux:tab.panel>
    </flux:tab.group>

    {{-- Override Configuration Modal --}}
    <flux:modal name="override-modal" wire:model="showOverrideModal" class="space-y-6">
        <form wire:submit="saveUserOverride">
            <div>
                <flux:heading size="lg">Configure Override</flux:heading>
                <flux:subheading class="mt-2">{{ $overrideSettingLabel }}</flux:subheading>
            </div>

            <flux:separator />

            <div class="space-y-4">
                @if($overrideSettingKey === 'available_break_durations')
                    <flux:field>
                        <flux:label>Select Break Durations (comma-separated minutes)</flux:label>
                        <flux:input wire:model="overrideValue" placeholder="5,10,15,30,60" />
                        <flux:description>Enter minutes separated by commas (e.g., 5,10,15,30)</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'max_breaks_per_day')
                    <flux:field>
                        <flux:label>Maximum Breaks Per Day</flux:label>
                        <flux:input type="number" wire:model="overrideValue" min="0" placeholder="Unlimited" />
                        <flux:description>Leave empty for unlimited breaks</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'max_break_minutes_per_day')
                    <flux:field>
                        <flux:label>Maximum Break Minutes Per Day</flux:label>
                        <flux:input type="number" wire:model="overrideValue" min="0" />
                        <flux:description>Total minutes of break time allowed per day</flux:description>
                    </flux:field>
                @elseif(in_array($overrideSettingKey, ['allow_custom_break_duration', 'auto_approve_breaks', 'require_gps', 'require_approval', 'is_overtime_exempt']))
                    <flux:field>
                        <div class="flex items-center gap-3">
                            <flux:switch wire:model="overrideValue" />
                            <flux:label>{{ $overrideSettingLabel }}</flux:label>
                        </div>
                        @if($overrideSettingKey === 'is_overtime_exempt')
                            <flux:description>Exempt employees (salary) do not receive overtime pay</flux:description>
                        @endif
                    </flux:field>
                @elseif($overrideSettingKey === 'round_to_minutes')
                    <flux:field>
                        <flux:label>Round Clock Times</flux:label>
                        <flux:select wire:model="overrideValue">
                            <option value="0">No rounding (exact time)</option>
                            <option value="5">5 minutes</option>
                            <option value="10">10 minutes</option>
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                        </flux:select>
                    </flux:field>
                @elseif($overrideSettingKey === 'weekly_overtime_threshold_minutes')
                    <flux:field>
                        <flux:label>Weekly Overtime Threshold (hours)</flux:label>
                        <flux:input type="number" wire:model="overrideValue" step="0.5" min="0" />
                        <flux:description>Hours per week before overtime applies</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'overtime_multiplier')
                    <flux:field>
                        <flux:label>Overtime Pay Multiplier</flux:label>
                        <flux:input type="number" wire:model="overrideValue" step="0.1" min="1" />
                        <flux:description>Pay multiplier (e.g., 1.5 for time-and-a-half)</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'state_overtime_rules')
                    <flux:field>
                        <flux:label>State Overtime Rules</flux:label>
                        <flux:select wire:model="overrideValue">
                            <option value="federal">Federal (FLSA)</option>
                            <option value="california">California</option>
                        </flux:select>
                        <flux:description>Choose applicable overtime rules</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'double_time_threshold_minutes')
                    <flux:field>
                        <flux:label>Double Time Threshold (hours per week)</flux:label>
                        <flux:input type="number" wire:model="overrideValue" step="0.5" min="0" placeholder="Leave empty to disable" />
                        <flux:description>Hours per week before double-time applies</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'double_time_multiplier')
                    <flux:field>
                        <flux:label>Double Time Pay Multiplier</flux:label>
                        <flux:input type="number" wire:model="overrideValue" step="0.1" min="1" />
                        <flux:description>Pay multiplier (typically 2.0)</flux:description>
                    </flux:field>
                @elseif($overrideSettingKey === 'approval_threshold_hours')
                    <flux:field>
                        <flux:label>Auto-Approve Threshold (hours)</flux:label>
                        <flux:input type="number" wire:model="overrideValue" step="0.5" min="0" />
                        <flux:description>Time entries under this many hours will be auto-approved</flux:description>
                    </flux:field>
                @endif
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <flux:button type="button" variant="ghost" wire:click="cancelOverride">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save Override</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
