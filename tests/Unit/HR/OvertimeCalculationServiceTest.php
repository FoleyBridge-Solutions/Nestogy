<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\Settings\HRSettings;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Services\OvertimeCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OvertimeCalculationService $service;
    protected Company $company;
    protected User $user;
    protected Setting $setting;
    protected HRSettings $hrSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->setting = Setting::firstOrCreate(
            ['company_id' => $this->company->id],
            Setting::factory()->make()->toArray()
        );
        $this->hrSettings = new HRSettings($this->setting);
        $this->service = new OvertimeCalculationService();
    }

    public function test_calculate_overtime_returns_zero_without_clock_times(): void
    {
        $entry = EmployeeTimeEntry::factory()->make([
            'clock_in' => null,
            'clock_out' => null,
        ]);

        $result = $this->service->calculateOvertimeMinutes($entry, $this->hrSettings);

        $this->assertEquals(0, $result['total_minutes']);
        $this->assertEquals(0, $result['regular_minutes']);
        $this->assertEquals(0, $result['overtime_minutes']);
        $this->assertEquals(0, $result['break_minutes']);
    }

    public function test_calculate_overtime_calculates_total_minutes(): void
    {
        $entry = EmployeeTimeEntry::factory()->make([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-15 17:00:00'),
            'break_minutes' => 0,
        ]);

        $result = $this->service->calculateOvertimeMinutes($entry, $this->hrSettings);

        $this->assertEquals(480, $result['total_minutes']);
    }

    public function test_calculate_overtime_auto_deducts_breaks(): void
    {
        $this->hrSettings->setAutoDeductBreaks(true);
        $this->hrSettings->setBreakThresholdMinutes(360);
        $this->hrSettings->setRequiredBreakMinutes(30);
        $this->setting->save();

        $entry = EmployeeTimeEntry::factory()->make([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-15 17:00:00'),
        ]);

        $result = $this->service->calculateOvertimeMinutes($entry, $this->hrSettings);

        $this->assertEquals(30, $result['break_minutes']);
        $this->assertEquals(450, $result['total_minutes']);
    }

    public function test_calculate_overtime_does_not_deduct_breaks_below_threshold(): void
    {
        $this->hrSettings->setAutoDeductBreaks(true);
        $this->hrSettings->setBreakThresholdMinutes(360);
        $this->hrSettings->setRequiredBreakMinutes(30);
        $this->setting->save();

        $entry = EmployeeTimeEntry::factory()->make([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-15 13:00:00'),
        ]);

        $result = $this->service->calculateOvertimeMinutes($entry, $this->hrSettings);

        $this->assertEquals(0, $result['break_minutes']);
        $this->assertEquals(240, $result['total_minutes']);
    }

    public function test_calculate_overtime_uses_manual_break_minutes(): void
    {
        $this->hrSettings->setAutoDeductBreaks(false);
        $this->setting->save();

        $entry = EmployeeTimeEntry::factory()->make([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-15 17:00:00'),
            'break_minutes' => 45,
        ]);

        $result = $this->service->calculateOvertimeMinutes($entry, $this->hrSettings);

        $this->assertEquals(45, $result['break_minutes']);
        $this->assertEquals(435, $result['total_minutes']);
    }

    public function test_calculate_overtime_exempts_overtime_exempt_employees(): void
    {
        $this->user->is_overtime_exempt = true;
        $this->user->save();

        $entry = EmployeeTimeEntry::factory()->make([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'clock_in' => Carbon::parse('2024-01-15 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-15 22:00:00'),
            'break_minutes' => 0,
        ]);
        $entry->user = $this->user;

        $result = $this->service->calculateOvertimeMinutes($entry, $this->hrSettings);

        $this->assertEquals(780, $result['total_minutes']);
        $this->assertEquals(780, $result['regular_minutes']);
        $this->assertEquals(0, $result['overtime_minutes']);
    }

    public function test_calculate_weekly_overtime_federal_rules_under_40_hours(): void
    {
        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(1440, $result['regular_minutes']);
        $this->assertEquals(0, $result['overtime_minutes']);
        $this->assertEquals(0, $result['double_time_minutes']);
    }

    public function test_calculate_weekly_overtime_federal_rules_over_40_hours(): void
    {
        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 240]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(2400, $result['regular_minutes']);
        $this->assertEquals(240, $result['overtime_minutes']);
        $this->assertEquals(0, $result['double_time_minutes']);
    }

    public function test_calculate_weekly_overtime_with_double_time_threshold(): void
    {
        $this->hrSettings->setDoubleTimeThresholdMinutes(3000);
        $this->setting->save();

        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(2400, $result['regular_minutes']);
        $this->assertEquals(600, $result['overtime_minutes']);
        $this->assertEquals(600, $result['double_time_minutes']);
    }

    public function test_calculate_weekly_overtime_exempts_overtime_exempt_employees(): void
    {
        $this->user->is_overtime_exempt = true;
        $this->user->save();

        $entries = collect([
            EmployeeTimeEntry::factory()->make([
                'user_id' => $this->user->id,
                'total_minutes' => 600,
            ]),
            EmployeeTimeEntry::factory()->make([
                'user_id' => $this->user->id,
                'total_minutes' => 600,
            ]),
            EmployeeTimeEntry::factory()->make([
                'user_id' => $this->user->id,
                'total_minutes' => 600,
            ]),
        ]);
        $entries->each(fn($entry) => $entry->user = $this->user);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(1800, $result['regular_minutes']);
        $this->assertEquals(0, $result['overtime_minutes']);
        $this->assertEquals(0, $result['double_time_minutes']);
    }

    public function test_calculate_california_overtime_under_8_hours_daily(): void
    {
        $this->hrSettings->setStateOvertimeRules('california');
        $this->setting->save();

        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 420]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 420]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 420]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(1260, $result['regular_minutes']);
        $this->assertEquals(0, $result['overtime_minutes']);
        $this->assertEquals(0, $result['double_time_minutes']);
    }

    public function test_calculate_california_overtime_over_8_hours_daily(): void
    {
        $this->hrSettings->setStateOvertimeRules('california');
        $this->setting->save();

        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 600]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(480, $result['regular_minutes']);
        $this->assertEquals(120, $result['overtime_minutes']);
        $this->assertEquals(0, $result['double_time_minutes']);
    }

    public function test_calculate_california_overtime_over_12_hours_daily(): void
    {
        $this->hrSettings->setStateOvertimeRules('california');
        $this->setting->save();

        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 780]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(480, $result['regular_minutes']);
        $this->assertEquals(240, $result['overtime_minutes']);
        $this->assertEquals(60, $result['double_time_minutes']);
    }

    public function test_calculate_california_overtime_applies_weekly_threshold(): void
    {
        $this->hrSettings->setStateOvertimeRules('california');
        $this->setting->save();

        $entries = collect([
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 480]),
            EmployeeTimeEntry::factory()->make(['total_minutes' => 240]),
        ]);

        $result = $this->service->calculateWeeklyOvertime($entries, $this->hrSettings);

        $this->assertEquals(2400, $result['regular_minutes']);
        $this->assertEquals(240, $result['overtime_minutes']);
    }

    public function test_recalculate_week_entries_updates_all_entries(): void
    {
        $entries = collect([
            EmployeeTimeEntry::factory()->create([
                'user_id' => $this->user->id,
                'company_id' => $this->company->id,
                'total_minutes' => 480,
            ]),
            EmployeeTimeEntry::factory()->create([
                'user_id' => $this->user->id,
                'company_id' => $this->company->id,
                'total_minutes' => 480,
            ]),
            EmployeeTimeEntry::factory()->create([
                'user_id' => $this->user->id,
                'company_id' => $this->company->id,
                'total_minutes' => 600,
            ]),
        ]);

        $this->service->recalculateWeekEntries($entries, $this->hrSettings);

        $entries->each(function ($entry) {
            $entry->refresh();
            $this->assertGreaterThan(0, $entry->regular_minutes);
        });
    }

    public function test_round_time_does_not_round_when_zero(): void
    {
        $time = Carbon::parse('2024-01-15 09:07:00');
        
        $result = $this->service->roundTime($time, 0);

        $this->assertEquals('09:07:00', $result->format('H:i:s'));
    }

    public function test_round_time_rounds_to_15_minutes(): void
    {
        $time = Carbon::parse('2024-01-15 09:07:00');
        
        $result = $this->service->roundTime($time, 15);

        $this->assertEquals('09:00:00', $result->format('H:i:s'));
    }

    public function test_round_time_rounds_to_30_minutes(): void
    {
        $time = Carbon::parse('2024-01-15 09:20:00');
        
        $result = $this->service->roundTime($time, 30);

        $this->assertEquals('09:30:00', $result->format('H:i:s'));
    }

    public function test_calculate_break_minutes_returns_zero_when_disabled(): void
    {
        $this->hrSettings->setAutoDeductBreaks(false);
        $this->setting->save();

        $result = $this->service->calculateBreakMinutes(480, $this->hrSettings);

        $this->assertEquals(0, $result);
    }

    public function test_calculate_break_minutes_returns_zero_below_threshold(): void
    {
        $this->hrSettings->setAutoDeductBreaks(true);
        $this->hrSettings->setBreakThresholdMinutes(360);
        $this->hrSettings->setRequiredBreakMinutes(30);
        $this->setting->save();

        $result = $this->service->calculateBreakMinutes(300, $this->hrSettings);

        $this->assertEquals(0, $result);
    }

    public function test_calculate_break_minutes_returns_required_minutes_above_threshold(): void
    {
        $this->hrSettings->setAutoDeductBreaks(true);
        $this->hrSettings->setBreakThresholdMinutes(360);
        $this->hrSettings->setRequiredBreakMinutes(30);
        $this->setting->save();

        $result = $this->service->calculateBreakMinutes(480, $this->hrSettings);

        $this->assertEquals(30, $result);
    }
}
