<?php

namespace App\Domains\HR\Services;

use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\HR\Models\EmployeeTimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OvertimeCalculationService
{
    public function calculateOvertimeMinutes(
        EmployeeTimeEntry $entry,
        HRSettings $hrSettings
    ): array {
        if (! $entry->clock_in || ! $entry->clock_out) {
            return [
                'total_minutes' => 0,
                'regular_minutes' => 0,
                'overtime_minutes' => 0,
                'break_minutes' => 0,
            ];
        }

        // Check if employee is exempt from overtime
        if ($entry->user && $entry->user->is_overtime_exempt) {
            $totalMinutes = $entry->clock_in->diffInMinutes($entry->clock_out);
            $breakMinutes = $entry->break_minutes ?? 0;
            $workMinutes = $totalMinutes - $breakMinutes;

            return [
                'total_minutes' => $workMinutes,
                'regular_minutes' => $workMinutes,
                'overtime_minutes' => 0,
                'break_minutes' => $breakMinutes,
            ];
        }

        $totalMinutes = $entry->clock_in->diffInMinutes($entry->clock_out);

        $breakMinutes = 0;
        if ($hrSettings->autoDeductBreaks()) {
            $breakThreshold = $hrSettings->getBreakThresholdMinutes();
            if ($totalMinutes >= $breakThreshold) {
                $breakMinutes = $hrSettings->getRequiredBreakMinutes();
            }
        } else {
            $breakMinutes = $entry->break_minutes ?? 0;
        }

        $workMinutes = $totalMinutes - $breakMinutes;

        // Store total work minutes - overtime will be calculated at week level
        return [
            'total_minutes' => $workMinutes,
            'regular_minutes' => $workMinutes,
            'overtime_minutes' => 0,
            'break_minutes' => $breakMinutes,
        ];
    }

    public function calculateWeeklyOvertime(
        Collection $weekEntries,
        HRSettings $hrSettings
    ): array {
        // Check if employee is exempt from overtime
        $user = $weekEntries->first()?->user;
        if ($user && $user->is_overtime_exempt) {
            $totalMinutes = $weekEntries->sum('total_minutes');
            return [
                'regular_minutes' => $totalMinutes,
                'overtime_minutes' => 0,
                'double_time_minutes' => 0,
            ];
        }

        $totalMinutes = $weekEntries->sum('total_minutes');
        $weeklyThreshold = $hrSettings->getWeeklyOvertimeThresholdMinutes();
        $doubleTimeThreshold = $hrSettings->getDoubleTimeThresholdMinutes();
        $stateRules = $hrSettings->getStateOvertimeRules();

        // Handle state-specific rules
        if ($stateRules === 'california') {
            return $this->calculateCaliforniaOvertime($weekEntries, $hrSettings);
        }

        // Federal FLSA rules: 40 hours per week
        if ($totalMinutes <= $weeklyThreshold) {
            return [
                'regular_minutes' => $totalMinutes,
                'overtime_minutes' => 0,
                'double_time_minutes' => 0,
            ];
        }

        $regularMinutes = $weeklyThreshold;
        $overtimeMinutes = $totalMinutes - $weeklyThreshold;
        $doubleTimeMinutes = 0;

        // Optional double-time for excessive hours
        if ($doubleTimeThreshold && $totalMinutes > $doubleTimeThreshold) {
            $doubleTimeMinutes = $totalMinutes - $doubleTimeThreshold;
            $overtimeMinutes = $doubleTimeThreshold - $weeklyThreshold;
        }

        return [
            'regular_minutes' => $regularMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'double_time_minutes' => $doubleTimeMinutes,
        ];
    }

    protected function calculateCaliforniaOvertime(
        Collection $weekEntries,
        HRSettings $hrSettings
    ): array {
        $regularMinutes = 0;
        $overtimeMinutes = 0;
        $doubleTimeMinutes = 0;

        foreach ($weekEntries as $entry) {
            $dailyMinutes = $entry->total_minutes ?? 0;

            // CA Rule 1: Over 8 hours/day = 1.5x, Over 12 hours/day = 2x
            if ($dailyMinutes <= 480) { // 8 hours
                $regularMinutes += $dailyMinutes;
            } elseif ($dailyMinutes <= 720) { // 12 hours
                $regularMinutes += 480;
                $overtimeMinutes += ($dailyMinutes - 480);
            } else { // Over 12 hours
                $regularMinutes += 480;
                $overtimeMinutes += 240; // 8-12 hours = 4 hours OT
                $doubleTimeMinutes += ($dailyMinutes - 720);
            }
        }

        // CA Rule 2: Also apply weekly overtime (over 40 hours)
        $weeklyThreshold = $hrSettings->getWeeklyOvertimeThresholdMinutes();
        $totalMinutes = $regularMinutes + $overtimeMinutes + $doubleTimeMinutes;

        if ($regularMinutes > $weeklyThreshold) {
            $weeklyOvertimeMinutes = $regularMinutes - $weeklyThreshold;
            $regularMinutes = $weeklyThreshold;
            $overtimeMinutes += $weeklyOvertimeMinutes;
        }

        return [
            'regular_minutes' => $regularMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'double_time_minutes' => $doubleTimeMinutes,
        ];
    }

    public function recalculateWeekEntries(
        Collection $weekEntries,
        HRSettings $hrSettings
    ): void {
        $weeklyBreakdown = $this->calculateWeeklyOvertime($weekEntries, $hrSettings);

        $totalWeekMinutes = $weekEntries->sum('total_minutes');
        if ($totalWeekMinutes <= 0) {
            return;
        }

        foreach ($weekEntries as $entry) {
            $entryRatio = $entry->total_minutes / $totalWeekMinutes;

            $entry->regular_minutes = (int) round($weeklyBreakdown['regular_minutes'] * $entryRatio);
            $entry->overtime_minutes = (int) round($weeklyBreakdown['overtime_minutes'] * $entryRatio);
            $entry->double_time_minutes = (int) round($weeklyBreakdown['double_time_minutes'] * $entryRatio);

            $entry->save();
        }
    }

    public function roundTime(Carbon $time, int $roundMinutes): Carbon
    {
        if ($roundMinutes <= 0) {
            return $time;
        }

        $minutes = $time->minute;
        $roundedMinutes = round($minutes / $roundMinutes) * $roundMinutes;

        return $time->copy()->minute($roundedMinutes)->second(0);
    }

    public function calculateBreakMinutes(int $totalMinutes, HRSettings $hrSettings): int
    {
        if (! $hrSettings->autoDeductBreaks()) {
            return 0;
        }

        $breakThreshold = $hrSettings->getBreakThresholdMinutes();

        if ($totalMinutes >= $breakThreshold) {
            return $hrSettings->getRequiredBreakMinutes();
        }

        return 0;
    }
}
