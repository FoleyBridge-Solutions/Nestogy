<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeSchedule;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->shift = Shift::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Morning Shift',
            'start_time' => Carbon::parse('09:00:00'),
            'end_time' => Carbon::parse('17:00:00'),
            'break_minutes' => 30,
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
        ]);
    }

    public function test_shift_can_be_created(): void
    {
        $this->assertInstanceOf(Shift::class, $this->shift);
        $this->assertEquals('Morning Shift', $this->shift->name);
        $this->assertEquals($this->company->id, $this->shift->company_id);
    }

    public function test_shift_belongs_to_company(): void
    {
        $this->assertInstanceOf(Company::class, $this->shift->company);
        $this->assertEquals($this->company->id, $this->shift->company->id);
    }

    public function test_shift_has_many_schedules(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        
        EmployeeSchedule::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'shift_id' => $this->shift->id,
        ]);

        $this->assertCount(3, $this->shift->schedules);
        $this->assertInstanceOf(EmployeeSchedule::class, $this->shift->schedules->first());
    }

    public function test_shift_has_many_time_entries(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        
        EmployeeTimeEntry::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'shift_id' => $this->shift->id,
        ]);

        $this->assertCount(5, $this->shift->timeEntries);
        $this->assertInstanceOf(EmployeeTimeEntry::class, $this->shift->timeEntries->first());
    }

    public function test_shift_casts_start_time_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->shift->start_time);
    }

    public function test_shift_casts_end_time_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->shift->end_time);
    }

    public function test_shift_casts_break_minutes_to_integer(): void
    {
        $this->assertIsInt($this->shift->break_minutes);
    }

    public function test_shift_casts_days_of_week_to_array(): void
    {
        $this->assertIsArray($this->shift->days_of_week);
        $this->assertEquals([1, 2, 3, 4, 5], $this->shift->days_of_week);
    }

    public function test_shift_casts_is_active_to_boolean(): void
    {
        $this->assertIsBool($this->shift->is_active);
        $this->assertTrue($this->shift->is_active);
    }

    public function test_scope_active_returns_only_active_shifts(): void
    {
        Shift::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $activeShifts = Shift::active()->get();

        $this->assertCount(1, $activeShifts);
        $this->assertTrue($activeShifts->first()->is_active);
    }

    public function test_get_duration_minutes_calculates_correctly(): void
    {
        $duration = $this->shift->getDurationMinutes();

        $this->assertEquals(480, $duration);
    }

    public function test_get_working_minutes_subtracts_break_minutes(): void
    {
        $workingMinutes = $this->shift->getWorkingMinutes();

        $this->assertEquals(450, $workingMinutes);
    }

    public function test_get_working_minutes_with_no_break(): void
    {
        $this->shift->break_minutes = 0;
        $this->shift->save();

        $workingMinutes = $this->shift->getWorkingMinutes();

        $this->assertEquals(480, $workingMinutes);
    }

    public function test_is_scheduled_for_returns_true_for_included_day(): void
    {
        $this->assertTrue($this->shift->isScheduledFor(1));
        $this->assertTrue($this->shift->isScheduledFor(5));
    }

    public function test_is_scheduled_for_returns_false_for_excluded_day(): void
    {
        $this->assertFalse($this->shift->isScheduledFor(0));
        $this->assertFalse($this->shift->isScheduledFor(6));
    }

    public function test_is_scheduled_for_handles_empty_days_of_week(): void
    {
        $this->shift->days_of_week = [];
        $this->shift->save();

        $this->assertFalse($this->shift->isScheduledFor(1));
    }

    public function test_shift_can_have_color(): void
    {
        $this->shift->color = '#FF5733';
        $this->shift->save();

        $this->assertEquals('#FF5733', $this->shift->color);
    }

    public function test_shift_can_have_description(): void
    {
        $this->shift->description = 'Standard weekday morning shift';
        $this->shift->save();

        $this->assertEquals('Standard weekday morning shift', $this->shift->description);
    }

    public function test_long_shift_duration_calculates_correctly(): void
    {
        $longShift = Shift::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => Carbon::parse('06:00:00'),
            'end_time' => Carbon::parse('18:00:00'),
        ]);

        $duration = $longShift->getDurationMinutes();

        $this->assertEquals(720, $duration);
    }
}
