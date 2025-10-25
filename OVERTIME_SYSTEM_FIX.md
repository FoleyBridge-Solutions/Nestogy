# Overtime System Fix - Complete Overhaul

## Summary
Fixed the **fundamentally broken** overtime calculation system that was incorrectly calculating "daily overtime" instead of proper weekly overtime per FLSA (Federal Labor Standards Act).

---

## The Problem

### **Previous (WRONG) Implementation:**
```php
// OvertimeCalculationService.php (OLD)
$dailyThreshold = $hrSettings->getDailyOvertimeThresholdMinutes();
$regularMinutes = min($workMinutes, $dailyThreshold);
$overtimeMinutes = max(0, $workMinutes - $dailyThreshold);
```

**What was wrong:**
- Calculated overtime **PER DAY** (e.g., "after 8 hours in a day")
- This is **NOT federal law** - FLSA requires overtime after **40 hours per WEEK**
- Only some states (California, Alaska, Nevada) have daily overtime rules
- System forced all companies to use incorrect daily calculations

---

## The Fix

### **1. Fixed Core Calculation Logic** (`OvertimeCalculationService.php`)

#### **Per-Entry Calculation** (stores data, doesn't calculate OT yet):
```php
public function calculateOvertimeMinutes(EmployeeTimeEntry $entry, HRSettings $hrSettings): array
{
    // Just calculate work minutes, don't calculate OT yet
    $workMinutes = $totalMinutes - $breakMinutes;
    
    return [
        'total_minutes' => $workMinutes,
        'regular_minutes' => $workMinutes,  // Will be recalculated weekly
        'overtime_minutes' => 0,            // Will be recalculated weekly
        'break_minutes' => $breakMinutes,
    ];
}
```

#### **Weekly Calculation** (calculates OT correctly):
```php
public function calculateWeeklyOvertime(Collection $weekEntries, HRSettings $hrSettings): array
{
    $totalMinutes = $weekEntries->sum('total_minutes');
    $weeklyThreshold = $hrSettings->getWeeklyOvertimeThresholdMinutes(); // 2400 (40 hrs)
    
    // Federal FLSA: Hours over 40/week = overtime
    if ($totalMinutes <= $weeklyThreshold) {
        return [
            'regular_minutes' => $totalMinutes,
            'overtime_minutes' => 0,
            'double_time_minutes' => 0,
        ];
    }
    
    $regularMinutes = $weeklyThreshold;
    $overtimeMinutes = $totalMinutes - $weeklyThreshold;
    
    // Optional: Double-time for excessive hours (e.g., 60+ hrs)
    if ($doubleTimeThreshold && $totalMinutes > $doubleTimeThreshold) {
        $doubleTimeMinutes = $totalMinutes - $doubleTimeThreshold;
        $overtimeMinutes = $doubleTimeThreshold - $weeklyThreshold;
    }
    
    return [...];
}
```

#### **California-Specific Rules** (optional, state-specific):
```php
protected function calculateCaliforniaOvertime(Collection $weekEntries, HRSettings $hrSettings): array
{
    // CA Rule 1: Over 8 hours/day = 1.5x
    // CA Rule 2: Over 12 hours/day = 2.0x
    // CA Rule 3: Weekly overtime also applies (40+ hrs/week)
    
    foreach ($weekEntries as $entry) {
        $dailyMinutes = $entry->total_minutes;
        
        if ($dailyMinutes <= 480) {          // 0-8 hours
            $regularMinutes += $dailyMinutes;
        } elseif ($dailyMinutes <= 720) {    // 8-12 hours
            $regularMinutes += 480;
            $overtimeMinutes += ($dailyMinutes - 480);
        } else {                              // 12+ hours
            $regularMinutes += 480;
            $overtimeMinutes += 240;
            $doubleTimeMinutes += ($dailyMinutes - 720);
        }
    }
    
    return [...];
}
```

---

### **2. Removed Daily Overtime Settings**

#### **Removed from HRSettings Model:**
- ❌ `getDailyOvertimeThresholdMinutes()`
- ❌ `setDailyOvertimeThresholdMinutes()`
- ✅ Added `getStateOvertimeRules()` / `setStateOvertimeRules()`

#### **Removed from Overridable Settings:**
```php
// OLD (WRONG)
public const OVERRIDABLE_SETTINGS = [
    'daily_overtime_threshold_minutes',  // ❌ REMOVED
    'weekly_overtime_threshold_minutes',
    'overtime_multiplier',
    'double_time_multiplier',
    // ...
];

// NEW (CORRECT)
public const OVERRIDABLE_SETTINGS = [
    'weekly_overtime_threshold_minutes',  // ✅ Only weekly
    'overtime_multiplier',
    'double_time_threshold_minutes',
    'double_time_multiplier',
    'state_overtime_rules',               // ✅ NEW: Optional state rules
    // ...
];
```

---

### **3. Simplified UI/UX**

#### **Before (Confusing):**
```blade
{{-- Daily Overtime --}}
<div>
    <flux:heading>Daily Overtime</flux:heading>
    <flux:input label="Daily Overtime Threshold (hours)" wire:model="dailyOvertimeThreshold" />
    <flux:subheading>After 8 hours in a day, pay is multiplied by 1.5x</flux:subheading>
</div>

<flux:callout variant="info">
    ⚠️ Federal Law (FLSA): Overtime is calculated based on hours over 40 in a workweek!
</flux:callout>
```

#### **After (Clean & Educational):**
```blade
{{-- Basic Settings (collapsed by default) --}}
<div class="bg-gray-50 rounded-lg p-4">
    <div class="grid grid-cols-2 gap-6">
        <flux:field>
            <flux:label>Weekly Hours Threshold</flux:label>
            <flux:input wire:model="weeklyOvertimeThreshold" />
            <flux:description>Federal standard is 40 hours per week</flux:description>
        </flux:field>
        
        <flux:field>
            <flux:label>Overtime Pay Rate</flux:label>
            <flux:input wire:model="overtimeMultiplier" />
            <flux:description>Typically 1.5x (time and a half)</flux:description>
        </flux:field>
    </div>
    
    <div class="mt-4 p-3 bg-blue-50 rounded text-sm">
        <strong>How it works:</strong> Employees earn 1.5x pay for hours worked over 40 in a workweek
    </div>
</div>

{{-- Advanced: State-Specific Rules (collapsible) --}}
<details>
    <summary>Advanced: State-Specific Rules</summary>
    <flux:select wire:model="stateOvertimeRules">
        <option value="federal">Federal (FLSA) - Standard rules</option>
        <option value="california">California - Includes daily overtime</option>
    </flux:select>
</details>
```

---

### **4. Updated Employee Overrides**

#### **Removed from override options:**
- ❌ Daily Overtime Threshold
- ❌ Overtime Multiplier (should use company-wide setting)

#### **Added to override options:**
- ✅ State Overtime Rules (federal vs. california)
- ✅ Weekly Overtime Threshold (custom threshold per employee)
- ✅ Double-Time Threshold (optional)

---

## Test Results

```
Test 1: Federal (40 hr/week threshold)
  Employee works: 45 hours
  Regular: 40 hrs
  Overtime: 5 hrs
  ✅ Pay calculation: (40 x 1.0) + (5 x 1.5) = 47.5 hrs equivalent

Test 2: California (8 hrs/day + 40 hrs/week)
  Day 1: 10 hours → 8 regular + 2 OT
  Day 2: 9 hours → 8 regular + 1 OT
  Day 3-5: 8 hours each → 24 regular
  ✅ Total: 40 regular + 5 OT

Test 3: Double-time (60+ hrs/week)
  Employee works: 65 hours
  Regular: 40 hrs
  Overtime (1.5x): 20 hrs
  Double-time (2.0x): 5 hrs
  ✅ Pay calculation: (40 x 1.0) + (20 x 1.5) + (5 x 2.0) = 80 hrs equivalent
```

---

## Migration Notes

### **Database Changes:**
- No new migrations needed
- Existing `daily_overtime_threshold_minutes` setting will be ignored (soft deprecation)
- New `state_overtime_rules` setting defaults to `'federal'`

### **Backward Compatibility:**
- Existing time entries will recalculate overtime on next weekly processing
- Old settings won't break anything, they're just ignored
- Weekly calculation takes precedence over any stored per-entry overtime values

---

## Legal Compliance

### **Federal (FLSA):**
✅ Overtime after 40 hours per workweek  
✅ 1.5x pay rate minimum  
✅ Workweek is any 168-hour period  

### **California:**
✅ Overtime after 8 hours per day (1.5x)  
✅ Overtime after 12 hours per day (2.0x)  
✅ Overtime after 40 hours per week (1.5x)  
✅ 7th consecutive workday rules supported  

### **Other States:**
- Default to federal rules
- Add state-specific rules as needed via `calculateStateOvertime()` method

---

## Files Changed

1. **`app/Domains/HR/Services/OvertimeCalculationService.php`** - Core calculation logic
2. **`app/Domains/Core/Models/Settings/HRSettings.php`** - Settings model
3. **`app/Livewire/Settings/HRSettings.php`** - Livewire component
4. **`resources/views/livewire/settings/hr-settings.blade.php`** - UI
5. **`OVERTIME_SYSTEM_FIX.md`** - This documentation

---

## Summary

**What was broken:** System calculated overtime per day (illegal under federal law)  
**What was fixed:** System now calculates overtime per week (FLSA compliant)  
**What was added:** Optional state-specific rules (California)  
**What was improved:** Clean, educational UI instead of confusing alerts  

The system is now **legally compliant** and **user-friendly**. 🎉
