<?php

namespace Tests\Unit\HR\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use App\Domains\HR\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTimeEntryTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_can_create_employee_time_entry(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertInstanceOf(EmployeeTimeEntry::class, $entry);
        $this->assertEquals($this->company->id, $entry->company_id);
        $this->assertEquals($this->user->id, $entry->user_id);
    }

    public function test_belongs_to_user(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertInstanceOf(User::class, $entry->user);
        $this->assertEquals($this->user->id, $entry->user->id);
    }

    public function test_belongs_to_pay_period(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'frequency' => PayPeriod::FREQUENCY_MONTHLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $payPeriod->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertInstanceOf(PayPeriod::class, $entry->payPeriod);
        $this->assertEquals($payPeriod->id, $entry->payPeriod->id);
    }

    public function test_belongs_to_shift(): void
    {
        $shift = Shift::create([
            'company_id' => $this->company->id,
            'name' => 'Morning Shift',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'days_of_week' => [1, 2, 3, 4, 5], // Monday-Friday
        ]);

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $shift->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertInstanceOf(Shift::class, $entry->shift);
        $this->assertEquals($shift->id, $entry->shift->id);
    }

    public function test_is_in_progress_status(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertTrue($entry->isInProgress());
        $this->assertFalse($entry->isCompleted());
        $this->assertFalse($entry->isApproved());
        $this->assertFalse($entry->isRejected());
        $this->assertFalse($entry->isPaid());
    }

    public function test_is_completed_status(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        $this->assertTrue($entry->isCompleted());
        $this->assertFalse($entry->isInProgress());
        $this->assertFalse($entry->isApproved());
    }

    public function test_is_approved_status(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $this->assertTrue($entry->isApproved());
        $this->assertFalse($entry->isCompleted());
        $this->assertFalse($entry->isRejected());
    }

    public function test_is_rejected_status(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_REJECTED,
            'total_minutes' => 480,
            'rejected_by' => $this->user->id,
            'rejected_at' => now(),
            'rejection_reason' => 'Invalid hours',
        ]);

        $this->assertTrue($entry->isRejected());
        $this->assertFalse($entry->isApproved());
    }

    public function test_is_paid_status(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_PAID,
            'total_minutes' => 480,
            'exported_to_payroll' => true,
            'exported_at' => now(),
        ]);

        $this->assertTrue($entry->isPaid());
        $this->assertFalse($entry->isCompleted());
    }

    public function test_get_elapsed_minutes_with_clock_out(): void
    {
        $clockIn = Carbon::parse('2025-01-01 09:00:00');
        $clockOut = Carbon::parse('2025-01-01 17:00:00');

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $this->assertEquals(480, $entry->getElapsedMinutes());
    }

    public function test_get_elapsed_minutes_without_clock_out(): void
    {
        $clockIn = now()->subMinutes(120);

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => $clockIn,
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $elapsed = $entry->getElapsedMinutes();
        $this->assertGreaterThanOrEqual(120, $elapsed);
        $this->assertLessThanOrEqual(121, $elapsed);
    }

    public function test_get_elapsed_hours(): void
    {
        $clockIn = Carbon::parse('2025-01-01 09:00:00');
        $clockOut = Carbon::parse('2025-01-01 17:30:00');

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $this->assertEquals(8.5, $entry->getElapsedHours());
    }

    public function test_get_formatted_duration(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8)->subMinutes(30),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 510,
        ]);

        $this->assertEquals('8:30', $entry->getFormattedDuration());
    }

    public function test_get_total_hours(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        $this->assertEquals(8.0, $entry->getTotalHours());
    }

    public function test_get_regular_hours(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 600,
            'regular_minutes' => 480,
            'overtime_minutes' => 120,
        ]);

        $this->assertEquals(8.0, $entry->getRegularHours());
    }

    public function test_get_overtime_hours(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(10),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 600,
            'regular_minutes' => 480,
            'overtime_minutes' => 120,
        ]);

        $this->assertEquals(2.0, $entry->getOvertimeHours());
    }

    public function test_get_double_time_hours(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(12),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 720,
            'regular_minutes' => 480,
            'overtime_minutes' => 120,
            'double_time_minutes' => 120,
        ]);

        $this->assertEquals(2.0, $entry->getDoubleTimeHours());
    }

    public function test_scope_in_progress(): void
    {
        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        $inProgress = EmployeeTimeEntry::inProgress()->count();
        $this->assertEquals(1, $inProgress);
    }

    public function test_scope_completed(): void
    {
        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $completed = EmployeeTimeEntry::completed()->count();
        $this->assertEquals(1, $completed);
    }

    public function test_scope_approved(): void
    {
        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $approved = EmployeeTimeEntry::approved()->count();
        $this->assertEquals(1, $approved);
    }

    public function test_scope_pending(): void
    {
        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
        ]);

        $pending = EmployeeTimeEntry::pending()->count();
        $this->assertEquals(2, $pending);
    }

    public function test_scope_not_exported(): void
    {
        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
            'exported_to_payroll' => false,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_PAID,
            'total_minutes' => 480,
            'exported_to_payroll' => true,
        ]);

        $notExported = EmployeeTimeEntry::notExported()->count();
        $this->assertEquals(1, $notExported);
    }

    public function test_scope_for_user(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $otherUser->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $userEntries = EmployeeTimeEntry::forUser($this->user)->count();
        $this->assertEquals(1, $userEntries);
    }

    public function test_scope_date_range(): void
    {
        $start = Carbon::parse('2025-01-01');
        $end = Carbon::parse('2025-01-31');

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2025-01-15 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2025-02-15 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $entriesInRange = EmployeeTimeEntry::dateRange($start, $end)->count();
        $this->assertEquals(1, $entriesInRange);
    }

    public function test_soft_deletes(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $entry->delete();

        $this->assertSoftDeleted($entry);
        $this->assertEquals(0, EmployeeTimeEntry::count());
        $this->assertEquals(1, EmployeeTimeEntry::withTrashed()->count());
    }

    public function test_casts_dates_correctly(): void
    {
        $clockIn = Carbon::parse('2025-01-01 09:00:00');
        $clockOut = Carbon::parse('2025-01-01 17:00:00');

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $this->assertInstanceOf(Carbon::class, $entry->clock_in);
        $this->assertInstanceOf(Carbon::class, $entry->clock_out);
    }

    public function test_casts_metadata_as_array(): void
    {
        $metadata = ['device' => 'mobile', 'location' => 'office'];

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => now(),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($entry->metadata);
        $this->assertEquals('mobile', $entry->metadata['device']);
        $this->assertEquals('office', $entry->metadata['location']);
    }
}
