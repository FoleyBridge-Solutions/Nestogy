<?php

// HR module routes

use App\Domains\HR\Controllers\EmployeeTimeEntryController;
use App\Domains\HR\Controllers\TimeClockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // HR Dashboard
    Route::get('/hr', \App\Livewire\HR\HRDashboard::class)->name('hr.dashboard');
    
    Route::prefix('hr/time-clock')->name('hr.time-clock.')->group(function () {
        Route::get('/', [TimeClockController::class, 'index'])->name('index');
        Route::post('/clock-in', [TimeClockController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [TimeClockController::class, 'clockOut'])->name('clock-out');
        Route::get('/history', [TimeClockController::class, 'history'])->name('history');
        Route::get('/status', [TimeClockController::class, 'status'])->name('status');
        Route::get('/schedule', [TimeClockController::class, 'schedule'])->name('schedule');
    });

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/time-entries', \App\Livewire\HR\EmployeeTimeEntryIndex::class)->name('time-entries.index')->middleware('can:manage-hr');
        Route::get('/time-entries/{entry}', [EmployeeTimeEntryController::class, 'show'])->name('time-entries.show')->middleware('can:manage-hr');
        Route::get('/time-entries/create', [EmployeeTimeEntryController::class, 'create'])->name('time-entries.create')->middleware('can:manage-hr');
        Route::post('/time-entries', [EmployeeTimeEntryController::class, 'store'])->name('time-entries.store')->middleware('can:manage-hr');
        Route::get('/time-entries/{entry}/edit', [EmployeeTimeEntryController::class, 'edit'])->name('time-entries.edit')->middleware('can:manage-hr');
        Route::put('/time-entries/{entry}', [EmployeeTimeEntryController::class, 'update'])->name('time-entries.update')->middleware('can:manage-hr');
        Route::delete('/time-entries/{entry}', [EmployeeTimeEntryController::class, 'destroy'])->name('time-entries.destroy')->middleware('can:manage-hr');

        Route::post('/time-entries/{entry}/approve', [EmployeeTimeEntryController::class, 'approve'])->name('time-entries.approve')->middleware('can:manage-hr');
        Route::post('/time-entries/{entry}/reject', [EmployeeTimeEntryController::class, 'reject'])->name('time-entries.reject')->middleware('can:manage-hr');
        Route::post('/time-entries/bulk-approve', [EmployeeTimeEntryController::class, 'bulkApprove'])->name('time-entries.bulk-approve')->middleware('can:manage-hr');
        Route::post('/time-entries/bulk-export', [EmployeeTimeEntryController::class, 'bulkExport'])->name('time-entries.bulk-export')->middleware('can:manage-hr');
        Route::get('/payroll/export/{payPeriod}', [EmployeeTimeEntryController::class, 'payrollExport'])->name('payroll.export')->middleware('can:manage-hr');

        Route::get('/schedules', \App\Livewire\HR\Schedules::class)->name('schedules.index')->middleware('can:manage-hr');
        
        Route::get('/pay-periods', \App\Livewire\HR\PayPeriods::class)->name('pay-periods.index')->middleware('can:manage-hr');
        
        Route::get('/time-off', \App\Livewire\HR\TimeOff::class)->name('time-off.index');
        Route::get('/time-off/approvals', \App\Livewire\HR\TimeOffApprovals::class)->name('time-off.approvals')->middleware('can:manage-hr');
        
        Route::prefix('reports')->name('reports.')->middleware('can:manage-hr')->group(function () {
            Route::get('/timesheets', \App\Livewire\HR\Reports\Timesheets::class)->name('timesheets');
            Route::get('/overtime', \App\Livewire\HR\Reports\Overtime::class)->name('overtime');
            Route::get('/attendance', \App\Livewire\HR\Reports\Attendance::class)->name('attendance');
        });
    });
});
