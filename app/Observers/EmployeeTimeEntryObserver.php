<?php

namespace App\Observers;

use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;

class EmployeeTimeEntryObserver
{
    public function creating(EmployeeTimeEntry $employeeTimeEntry): void
    {
        if (! $employeeTimeEntry->pay_period_id && $employeeTimeEntry->clock_in) {
            $this->assignPayPeriod($employeeTimeEntry);
        }
    }

    public function updating(EmployeeTimeEntry $employeeTimeEntry): void
    {
        if ($employeeTimeEntry->isDirty('clock_in') && $employeeTimeEntry->clock_in) {
            $this->assignPayPeriod($employeeTimeEntry);
        }
    }

    protected function assignPayPeriod(EmployeeTimeEntry $employeeTimeEntry): void
    {
        $payPeriod = PayPeriod::where('company_id', $employeeTimeEntry->company_id)
            ->where('start_date', '<=', $employeeTimeEntry->clock_in)
            ->where('end_date', '>=', $employeeTimeEntry->clock_in)
            ->first();

        if ($payPeriod) {
            $employeeTimeEntry->pay_period_id = $payPeriod->id;
        }
    }
}
