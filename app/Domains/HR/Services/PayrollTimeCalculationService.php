<?php

namespace App\Domains\HR\Services;

use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollTimeCalculationService
{
    public function calculatePayPeriodHours(
        PayPeriod $payPeriod,
        ?User $user = null
    ): Collection {
        $query = EmployeeTimeEntry::where('company_id', $payPeriod->company_id)
            ->whereBetween('clock_in', [$payPeriod->start_date, $payPeriod->end_date])
            ->whereIn('status', [
                EmployeeTimeEntry::STATUS_APPROVED,
                EmployeeTimeEntry::STATUS_PAID,
            ]);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $entries = $query->with('user')->get();

        return $entries->groupBy('user_id')->map(function ($userEntries) {
            $user = $userEntries->first()->user;

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'entry_count' => $userEntries->count(),
                'total_hours' => round($userEntries->sum('total_minutes') / 60, 2),
                'regular_hours' => round($userEntries->sum('regular_minutes') / 60, 2),
                'overtime_hours' => round($userEntries->sum('overtime_minutes') / 60, 2),
                'double_time_hours' => round($userEntries->sum('double_time_minutes') / 60, 2),
                'entries' => $userEntries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'date' => $entry->clock_in->format('Y-m-d'),
                        'clock_in' => $entry->clock_in->format('Y-m-d H:i:s'),
                        'clock_out' => $entry->clock_out?->format('Y-m-d H:i:s'),
                        'total_hours' => $entry->getTotalHours(),
                        'regular_hours' => $entry->getRegularHours(),
                        'overtime_hours' => $entry->getOvertimeHours(),
                        'double_time_hours' => $entry->getDoubleTimeHours(),
                        'notes' => $entry->notes,
                    ];
                }),
            ];
        });
    }

    public function approvePayPeriod(PayPeriod $payPeriod, User $approver): PayPeriod
    {
        DB::beginTransaction();

        try {
            EmployeeTimeEntry::where('company_id', $payPeriod->company_id)
                ->whereBetween('clock_in', [$payPeriod->start_date, $payPeriod->end_date])
                ->where('status', EmployeeTimeEntry::STATUS_COMPLETED)
                ->update([
                    'status' => EmployeeTimeEntry::STATUS_APPROVED,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                ]);

            $payPeriod->status = PayPeriod::STATUS_APPROVED;
            $payPeriod->approved_by = $approver->id;
            $payPeriod->approved_at = now();
            $payPeriod->save();

            DB::commit();

            return $payPeriod;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markAsExported(PayPeriod $payPeriod, string $batchId): int
    {
        return EmployeeTimeEntry::where('company_id', $payPeriod->company_id)
            ->whereBetween('clock_in', [$payPeriod->start_date, $payPeriod->end_date])
            ->where('status', EmployeeTimeEntry::STATUS_APPROVED)
            ->update([
                'exported_to_payroll' => true,
                'exported_at' => now(),
                'payroll_batch_id' => $batchId,
                'status' => EmployeeTimeEntry::STATUS_PAID,
            ]);
    }

    public function generatePayPeriods(
        int $companyId,
        Carbon $startDate,
        Carbon $endDate,
        string $frequency = PayPeriod::FREQUENCY_BIWEEKLY
    ): Collection {
        $settings = Setting::where('company_id', $companyId)->first();
        $hrSettings = new HRSettings($settings);

        $periods = collect();
        $current = $startDate->copy();

        while ($current->lessThan($endDate)) {
            $periodEnd = match ($frequency) {
                PayPeriod::FREQUENCY_WEEKLY => $current->copy()->addWeek()->subDay(),
                PayPeriod::FREQUENCY_BIWEEKLY => $current->copy()->addWeeks(2)->subDay(),
                PayPeriod::FREQUENCY_SEMIMONTHLY => $current->day <= 15 ? $current->copy()->day(15) : $current->copy()->endOfMonth(),
                PayPeriod::FREQUENCY_MONTHLY => $current->copy()->endOfMonth(),
                default => $current->copy()->addWeeks(2)->subDay(),
            };

            if ($periodEnd->greaterThan($endDate)) {
                $periodEnd = $endDate->copy();
            }

            $period = PayPeriod::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'start_date' => $current->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                ],
                [
                    'frequency' => $frequency,
                    'status' => PayPeriod::STATUS_OPEN,
                ]
            );

            $periods->push($period);

            if ($frequency === PayPeriod::FREQUENCY_SEMIMONTHLY && $current->day <= 15) {
                $current = $current->copy()->day(16);
            } else {
                $current = $periodEnd->copy()->addDay();
            }
        }

        return $periods;
    }

    public function getSummaryStatistics(PayPeriod $payPeriod): array
    {
        $entries = EmployeeTimeEntry::where('company_id', $payPeriod->company_id)
            ->whereBetween('clock_in', [$payPeriod->start_date, $payPeriod->end_date])
            ->get();

        $approved = $entries->where('status', EmployeeTimeEntry::STATUS_APPROVED);
        $pending = $entries->whereIn('status', [
            EmployeeTimeEntry::STATUS_IN_PROGRESS,
            EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        return [
            'total_entries' => $entries->count(),
            'approved_entries' => $approved->count(),
            'pending_entries' => $pending->count(),
            'total_hours' => round($entries->sum('total_minutes') / 60, 2),
            'regular_hours' => round($entries->sum('regular_minutes') / 60, 2),
            'overtime_hours' => round($entries->sum('overtime_minutes') / 60, 2),
            'double_time_hours' => round($entries->sum('double_time_minutes') / 60, 2),
            'unique_employees' => $entries->pluck('user_id')->unique()->count(),
            'exported_entries' => $entries->where('exported_to_payroll', true)->count(),
            'not_exported_entries' => $entries->where('exported_to_payroll', false)->count(),
        ];
    }
}
