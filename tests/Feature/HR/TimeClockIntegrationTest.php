<?php

namespace Tests\Feature\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use App\Domains\HR\Services\TimeClockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeClockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Setting $setting;
    protected TimeClockService $timeClockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->setting = Setting::firstOrCreate(
            ['company_id' => $this->company->id],
            Setting::factory()->make()->toArray()
        );
        $this->timeClockService = app(TimeClockService::class);
    }

    public function test_complete_work_day_flow(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));

        $payPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-14'),
        ]);

        $entry = $this->timeClockService->clockIn($this->user);
        $this->assertNotNull($entry);
        $this->assertEquals(EmployeeTimeEntry::STATUS_IN_PROGRESS, $entry->status);

        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00'));

        $this->timeClockService->clockOut($entry);
        $entry->refresh();

        $this->assertNotNull($entry->clock_out);
        $this->assertEquals(480, $entry->total_minutes);
        $this->assertContains($entry->status, [EmployeeTimeEntry::STATUS_APPROVED, EmployeeTimeEntry::STATUS_COMPLETED]);

        Carbon::setTestNow();
    }

    public function test_week_long_overtime_calculation(): void
    {
        $payPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-14'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(Carbon::parse('2024-01-01')->addDays($i)->setTime(9, 0));
            $entry = $this->timeClockService->clockIn($this->user);
            
            Carbon::setTestNow(Carbon::parse('2024-01-01')->addDays($i)->setTime(19, 0));
            $this->timeClockService->clockOut($entry);
        }

        $entries = EmployeeTimeEntry::where('user_id', $this->user->id)->get();
        $totalMinutes = $entries->sum('total_minutes');

        $this->assertEquals(5, $entries->count());
        $this->assertEquals(3000, $totalMinutes);

        Carbon::setTestNow();
    }

    public function test_pay_period_auto_assignment(): void
    {
        $payPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-14'),
        ]);

        Carbon::setTestNow(Carbon::parse('2024-01-05 09:00:00'));
        $entry = $this->timeClockService->clockIn($this->user);

        $this->assertEquals($payPeriod->id, $entry->pay_period_id);

        Carbon::setTestNow();
    }

    public function test_auto_clock_out_stale_entries(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAutoClockOutHours(12);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->timeClockService->clockIn($this->user);

        Carbon::setTestNow(Carbon::parse('2024-01-16 09:00:00'));

        $results = $this->timeClockService->autoClockOutStaleEntries($this->company->id);

        $this->assertCount(1, $results);
        $this->assertEquals('success', $results[0]['status']);

        $entry->refresh();
        $this->assertNotNull($entry->clock_out);
        $this->assertStringContainsString('Auto-clocked out', $entry->notes);

        Carbon::setTestNow();
    }

    public function test_gps_validation_workflow(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRequireGPS(true);
        $this->setting->save();

        try {
            $this->timeClockService->clockIn($this->user);
            $this->fail('Expected exception for missing GPS');
        } catch (\Exception $e) {
            $this->assertStringContainsString('GPS location is required', $e->getMessage());
        }

        $entry = $this->timeClockService->clockIn($this->user, [
            'latitude' => 37.7749,
            'longitude' => -122.4194,
        ]);

        $this->assertNotNull($entry);
        $this->assertEquals(37.7749, $entry->clock_in_latitude);
        $this->assertEquals(-122.4194, $entry->clock_in_longitude);
    }

    public function test_ip_restriction_workflow(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAllowedIPs(['192.168.1.0/24']);
        $this->setting->save();

        try {
            $this->timeClockService->clockIn($this->user, ['ip' => '10.0.0.1']);
            $this->fail('Expected exception for disallowed IP');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Clock in is not allowed from this IP address', $e->getMessage());
        }

        $entry = $this->timeClockService->clockIn($this->user, ['ip' => '192.168.1.100']);

        $this->assertNotNull($entry);
        $this->assertEquals('192.168.1.100', $entry->clock_in_ip);
    }

    public function test_break_deduction_workflow(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setAutoDeductBreaks(true);
        $hrSettings->setBreakThresholdMinutes(360);
        $hrSettings->setRequiredBreakMinutes(30);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->timeClockService->clockIn($this->user);

        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00'));
        $this->timeClockService->clockOut($entry);

        $entry->refresh();
        $this->assertEquals(450, $entry->total_minutes);

        Carbon::setTestNow();
    }

    public function test_approval_threshold_workflow(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRequireApproval(true);
        $hrSettings->setApprovalThresholdHours(6);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->timeClockService->clockIn($this->user);

        Carbon::setTestNow(Carbon::parse('2024-01-15 17:00:00'));
        $this->timeClockService->clockOut($entry);

        $entry->refresh();
        $this->assertEquals(EmployeeTimeEntry::STATUS_COMPLETED, $entry->status);
        $this->assertNull($entry->approved_at);

        Carbon::setTestNow();
    }

    public function test_overtime_exempt_employee_workflow(): void
    {
        $this->user->is_overtime_exempt = true;
        $this->user->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        $entry = $this->timeClockService->clockIn($this->user);

        Carbon::setTestNow(Carbon::parse('2024-01-15 22:00:00'));
        $this->timeClockService->clockOut($entry);

        $entry->refresh();
        $this->assertEquals(780, $entry->total_minutes);
        $this->assertEquals(780, $entry->regular_minutes);
        $this->assertEquals(0, $entry->overtime_minutes);

        Carbon::setTestNow();
    }

    public function test_time_rounding_workflow(): void
    {
        $hrSettings = new HRSettings($this->setting);
        $hrSettings->setRoundToMinutes(15);
        $this->setting->save();

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:07:00'));
        $entry = $this->timeClockService->clockIn($this->user);

        $this->assertEquals('09:00:00', $entry->clock_in->format('H:i:s'));

        Carbon::setTestNow(Carbon::parse('2024-01-15 17:08:00'));
        $this->timeClockService->clockOut($entry);

        $entry->refresh();
        $this->assertEquals('17:15:00', $entry->clock_out->format('H:i:s'));

        Carbon::setTestNow();
    }

    public function test_multiple_users_same_company(): void
    {
        $user2 = User::factory()->create(['company_id' => $this->company->id]);
        $user3 = User::factory()->create(['company_id' => $this->company->id]);

        Carbon::setTestNow(Carbon::parse('2024-01-15 09:00:00'));
        
        $entry1 = $this->timeClockService->clockIn($this->user);
        $entry2 = $this->timeClockService->clockIn($user2);
        $entry3 = $this->timeClockService->clockIn($user3);

        $this->assertEquals($this->user->id, $entry1->user_id);
        $this->assertEquals($user2->id, $entry2->user_id);
        $this->assertEquals($user3->id, $entry3->user_id);

        $this->assertTrue($this->timeClockService->hasActiveEntry($this->user));
        $this->assertTrue($this->timeClockService->hasActiveEntry($user2));
        $this->assertTrue($this->timeClockService->hasActiveEntry($user3));

        Carbon::setTestNow();
    }
}
