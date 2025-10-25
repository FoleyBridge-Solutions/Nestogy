<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeSchedule;
use App\Domains\HR\Models\Shift;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Shift $shift;
    protected EmployeeSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->shift = Shift::factory()->create(['company_id' => $this->company->id]);
        
        $this->schedule = EmployeeSchedule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'scheduled_date' => Carbon::today(),
            'start_time' => Carbon::parse('09:00:00'),
            'end_time' => Carbon::parse('17:00:00'),
            'status' => EmployeeSchedule::STATUS_SCHEDULED,
        ]);
    }

    public function test_schedule_can_be_created(): void
    {
        $this->assertInstanceOf(EmployeeSchedule::class, $this->schedule);
        $this->assertEquals($this->company->id, $this->schedule->company_id);
        $this->assertEquals($this->user->id, $this->schedule->user_id);
        $this->assertEquals($this->shift->id, $this->schedule->shift_id);
    }

    public function test_schedule_belongs_to_company(): void
    {
        $this->assertInstanceOf(Company::class, $this->schedule->company);
        $this->assertEquals($this->company->id, $this->schedule->company->id);
    }

    public function test_schedule_belongs_to_user(): void
    {
        $this->assertInstanceOf(User::class, $this->schedule->user);
        $this->assertEquals($this->user->id, $this->schedule->user->id);
    }

    public function test_schedule_belongs_to_shift(): void
    {
        $this->assertInstanceOf(Shift::class, $this->schedule->shift);
        $this->assertEquals($this->shift->id, $this->schedule->shift->id);
    }

    public function test_schedule_casts_scheduled_date_to_date(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->schedule->scheduled_date);
    }

    public function test_schedule_casts_start_time_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->schedule->start_time);
    }

    public function test_schedule_casts_end_time_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->schedule->end_time);
    }

    public function test_schedule_has_status_constants(): void
    {
        $this->assertEquals('scheduled', EmployeeSchedule::STATUS_SCHEDULED);
        $this->assertEquals('confirmed', EmployeeSchedule::STATUS_CONFIRMED);
        $this->assertEquals('missed', EmployeeSchedule::STATUS_MISSED);
        $this->assertEquals('completed', EmployeeSchedule::STATUS_COMPLETED);
    }

    public function test_scope_upcoming_returns_future_scheduled_entries(): void
    {
        EmployeeSchedule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'scheduled_date' => Carbon::today()->addDays(3),
            'status' => EmployeeSchedule::STATUS_SCHEDULED,
        ]);

        EmployeeSchedule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'scheduled_date' => Carbon::today()->subDays(3),
            'status' => EmployeeSchedule::STATUS_SCHEDULED,
        ]);

        $upcoming = EmployeeSchedule::upcoming()->get();

        $this->assertGreaterThanOrEqual(1, $upcoming->count());
        $upcoming->each(function ($schedule) {
            $this->assertGreaterThanOrEqual(Carbon::today(), $schedule->scheduled_date);
            $this->assertEquals(EmployeeSchedule::STATUS_SCHEDULED, $schedule->status);
        });
    }

    public function test_scope_for_date_returns_schedules_for_specific_date(): void
    {
        $targetDate = Carbon::today()->addDays(5);
        
        EmployeeSchedule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'scheduled_date' => $targetDate,
        ]);

        EmployeeSchedule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'scheduled_date' => $targetDate->copy()->addDay(),
        ]);

        $schedules = EmployeeSchedule::forDate($targetDate)->get();

        $this->assertCount(1, $schedules);
        $this->assertTrue($schedules->first()->scheduled_date->isSameDay($targetDate));
    }

    public function test_scope_for_user_returns_schedules_for_specific_user(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        
        EmployeeSchedule::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'user_id' => $otherUser->id,
            'shift_id' => $this->shift->id,
        ]);

        $schedules = EmployeeSchedule::forUser($this->user)->get();

        $this->assertGreaterThanOrEqual(1, $schedules->count());
        $schedules->each(function ($schedule) {
            $this->assertEquals($this->user->id, $schedule->user_id);
        });
    }

    public function test_get_duration_minutes_calculates_correctly(): void
    {
        $duration = $this->schedule->getDurationMinutes();

        $this->assertEquals(480, $duration);
    }

    public function test_schedule_can_have_notes(): void
    {
        $this->schedule->notes = 'Employee requested this shift';
        $this->schedule->save();

        $this->assertEquals('Employee requested this shift', $this->schedule->notes);
    }

    public function test_schedule_status_can_be_updated(): void
    {
        $this->schedule->status = EmployeeSchedule::STATUS_CONFIRMED;
        $this->schedule->save();

        $this->assertEquals(EmployeeSchedule::STATUS_CONFIRMED, $this->schedule->status);
    }

    public function test_schedule_can_be_marked_as_missed(): void
    {
        $this->schedule->status = EmployeeSchedule::STATUS_MISSED;
        $this->schedule->save();

        $this->assertEquals(EmployeeSchedule::STATUS_MISSED, $this->schedule->status);
    }

    public function test_schedule_can_be_marked_as_completed(): void
    {
        $this->schedule->status = EmployeeSchedule::STATUS_COMPLETED;
        $this->schedule->save();

        $this->assertEquals(EmployeeSchedule::STATUS_COMPLETED, $this->schedule->status);
    }

    public function test_long_schedule_duration_calculates_correctly(): void
    {
        $longSchedule = EmployeeSchedule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'start_time' => Carbon::parse('06:00:00'),
            'end_time' => Carbon::parse('18:00:00'),
        ]);

        $duration = $longSchedule->getDurationMinutes();

        $this->assertEquals(720, $duration);
    }
}
