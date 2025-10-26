<?php

namespace App\Domains\HR\Services;

use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Support\ValidationResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeClockService
{
    protected OvertimeCalculationService $overtimeService;

    public function __construct(OvertimeCalculationService $overtimeService)
    {
        $this->overtimeService = $overtimeService;
    }

    public function clockIn(User $user, array $options = []): EmployeeTimeEntry
    {
        $settings = Setting::where('company_id', $user->company_id)->first();
        $hrSettings = new HRSettings($settings);

        $validation = $this->validateClockIn($user, $hrSettings, $options);
        if (! $validation->isValid()) {
            throw new \Exception($validation->getErrors()[0] ?? 'Unable to clock in');
        }

        $clockInTime = now();
        if ($hrSettings->getRoundToMinutes() > 0) {
            $clockInTime = $this->roundTime($clockInTime, $hrSettings->getRoundToMinutes());
        }

        $entry = EmployeeTimeEntry::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'shift_id' => $options['shift_id'] ?? null,
            'clock_in' => $clockInTime,
            'clock_in_ip' => $options['ip'] ?? request()->ip(),
            'clock_in_latitude' => $options['latitude'] ?? null,
            'clock_in_longitude' => $options['longitude'] ?? null,
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'metadata' => [
                'device' => $options['device'] ?? request()->userAgent(),
                'clock_in_method' => $options['method'] ?? 'web',
            ],
        ]);

        Log::info('Employee clocked in', [
            'entry_id' => $entry->id,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'clock_in' => $clockInTime->toDateTimeString(),
        ]);

        return $entry;
    }

    public function clockOut(EmployeeTimeEntry $entry, array $options = []): EmployeeTimeEntry
    {
        if ($entry->clock_out) {
            throw new \Exception('Already clocked out');
        }

        $settings = Setting::where('company_id', $entry->company_id)->first();
        $hrSettings = new HRSettings($settings);

        $clockOutTime = now();
        if ($hrSettings->getRoundToMinutes() > 0) {
            $clockOutTime = $this->roundTime($clockOutTime, $hrSettings->getRoundToMinutes());
        }

        $entry->clock_out = $clockOutTime;
        $entry->clock_out_ip = $options['ip'] ?? request()->ip();
        $entry->clock_out_latitude = $options['latitude'] ?? null;
        $entry->clock_out_longitude = $options['longitude'] ?? null;
        $entry->notes = $options['notes'] ?? $entry->notes;

        if (isset($options['break_minutes'])) {
            $entry->break_minutes = (int) $options['break_minutes'];
        }

        if (isset($options['is_break']) && $options['is_break']) {
            $metadata = $entry->metadata ?? [];
            $metadata['is_break'] = true;
            $metadata['break_duration'] = $options['break_duration'] ?? 0;
            $entry->metadata = $metadata;
        }

        $breakdown = $this->overtimeService->calculateOvertimeMinutes($entry, $hrSettings);

         $entry->total_minutes = $breakdown['total_minutes'];
         $entry->regular_minutes = $breakdown['regular_minutes'];
         $entry->overtime_minutes = $breakdown['overtime_minutes'];
         $entry->break_minutes = (int) $breakdown['break_minutes'];

        if ($hrSettings->requireApproval() && $entry->total_minutes > ($hrSettings->getApprovalThresholdHours() * 60)) {
            $entry->status = EmployeeTimeEntry::STATUS_COMPLETED;
        } else {
            $entry->status = EmployeeTimeEntry::STATUS_APPROVED;
            $entry->approved_at = now();
            $entry->approved_by = $entry->user_id;
        }

        $entry->save();

        Log::info('Employee clocked out', [
            'entry_id' => $entry->id,
            'user_id' => $entry->user_id,
            'clock_out' => $clockOutTime->toDateTimeString(),
            'total_minutes' => $entry->total_minutes,
            'status' => $entry->status,
        ]);

        return $entry;
    }

    public function getActiveEntry(User $user): ?EmployeeTimeEntry
    {
        return EmployeeTimeEntry::where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('status', EmployeeTimeEntry::STATUS_IN_PROGRESS)
            ->whereNull('clock_out')
            ->first();
    }

    public function hasActiveEntry(User $user): bool
    {
        return $this->getActiveEntry($user) !== null;
    }

    public function validateClockIn(User $user, HRSettings $hrSettings, array $options = []): ValidationResult
    {
        $errors = [];

        if ($this->hasActiveEntry($user)) {
            $errors[] = 'You already have an active time entry. Please clock out first.';
        }

        if ($hrSettings->requireGPS()) {
            if (! isset($options['latitude']) || ! isset($options['longitude'])) {
                $errors[] = 'GPS location is required to clock in.';
            }
        }

        if ($allowedIps = $hrSettings->getAllowedIPs()) {
            $ip = $options['ip'] ?? request()->ip();
            $isAllowed = false;

            foreach ($allowedIps as $allowedIp) {
                if ($this->ipInRange($ip, $allowedIp)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (! $isAllowed) {
                $errors[] = 'Clock in is not allowed from this IP address.';
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    public function autoClockOutStaleEntries(int $companyId): array
    {
        $settings = Setting::where('company_id', $companyId)->first();
        $hrSettings = new HRSettings($settings);

        $threshold = now()->subHours($hrSettings->getAutoClockOutHours());

        $staleEntries = EmployeeTimeEntry::where('company_id', $companyId)
            ->where('status', EmployeeTimeEntry::STATUS_IN_PROGRESS)
            ->whereNull('clock_out')
            ->where('clock_in', '<', $threshold)
            ->get();

        $results = [];

        foreach ($staleEntries as $entry) {
            try {
                $this->clockOut($entry, [
                    'notes' => 'Auto-clocked out after ' . $hrSettings->getAutoClockOutHours() . ' hours',
                ]);

                $results[] = [
                    'entry_id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'status' => 'success',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'entry_id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to auto clock out', [
                    'entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    protected function roundTime(Carbon $time, int $roundToMinutes): Carbon
    {
        $totalMinutes = $time->hour * 60 + $time->minute;
        $roundedTotalMinutes = round($totalMinutes / $roundToMinutes) * $roundToMinutes;
        
        return $time->copy()
            ->startOfDay()
            ->addMinutes($roundedTotalMinutes);
    }

    protected function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $mask] = explode('/', $range);
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - (int) $mask);

            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        }

        return $ip === $range;
    }
}
