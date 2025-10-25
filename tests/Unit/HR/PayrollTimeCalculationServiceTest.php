<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\Setting;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use App\Domains\HR\Services\PayrollTimeCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTimeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PayrollTimeCalculationService $service;
    protected Company $company;
    protected User $user;
    protected Setting $setting;
    protected PayPeriod $payPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->setting = Setting::firstOrCreate(
            ['company_id' => $this->company->id],
            Setting::factory()->make()->toArray()
        );
        $this->service = new PayrollTimeCalculationService();
        
        $this->payPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-14'),
        ]);
    }

    public function test_calculate_pay_period_hours_returns_collection(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
            'regular_minutes' => 480,
        ]);

        $result = $this->service->calculatePayPeriodHours($this->payPeriod);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function test_calculate_pay_period_hours_includes_user_details(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
            'regular_minutes' => 480,
        ]);

        $result = $this->service->calculatePayPeriodHours($this->payPeriod);
        $userSummary = $result->first();

        $this->assertEquals($this->user->id, $userSummary['user_id']);
        $this->assertEquals($this->user->name, $userSummary['user_name']);
        $this->assertEquals($this->user->email, $userSummary['user_email']);
    }

    public function test_calculate_pay_period_hours_calculates_totals(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
            'regular_minutes' => 420,
            'overtime_minutes' => 60,
        ]);

        $result = $this->service->calculatePayPeriodHours($this->payPeriod);
        $userSummary = $result->first();

        $this->assertEquals(1, $userSummary['entry_count']);
        $this->assertEquals(8.0, $userSummary['total_hours']);
        $this->assertEquals(7.0, $userSummary['regular_hours']);
        $this->assertEquals(1.0, $userSummary['overtime_hours']);
    }

    public function test_calculate_pay_period_hours_filters_by_user(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
        ]);

        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $otherUser->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
        ]);

        $result = $this->service->calculatePayPeriodHours($this->payPeriod, $this->user);

        $this->assertCount(1, $result);
        $this->assertEquals($this->user->id, $result->first()['user_id']);
    }

    public function test_calculate_pay_period_hours_only_includes_approved_and_paid(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-06 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-06 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
        ]);

        $result = $this->service->calculatePayPeriodHours($this->payPeriod);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()['entry_count']);
    }

    public function test_approve_pay_period_updates_entries_and_pay_period(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $approver = User::factory()->create(['company_id' => $this->company->id]);

        $result = $this->service->approvePayPeriod($this->payPeriod, $approver);

        $this->assertEquals(PayPeriod::STATUS_APPROVED, $result->status);
        $this->assertEquals($approver->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);

        $entry = EmployeeTimeEntry::first();
        $this->assertEquals(EmployeeTimeEntry::STATUS_APPROVED, $entry->status);
        $this->assertEquals($approver->id, $entry->approved_by);
    }

    public function test_mark_as_exported_updates_entries(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
        ]);

        $count = $this->service->markAsExported($this->payPeriod, 'BATCH-123');

        $this->assertEquals(1, $count);

        $entry = EmployeeTimeEntry::first();
        $this->assertTrue($entry->exported_to_payroll);
        $this->assertNotNull($entry->exported_at);
        $this->assertEquals('BATCH-123', $entry->payroll_batch_id);
        $this->assertEquals(EmployeeTimeEntry::STATUS_PAID, $entry->status);
    }

    public function test_generate_pay_periods_creates_biweekly_periods(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-03-01');

        $periods = $this->service->generatePayPeriods(
            $this->company->id,
            $startDate,
            $endDate,
            PayPeriod::FREQUENCY_BIWEEKLY
        );

        $this->assertGreaterThan(0, $periods->count());
        $periods->each(function ($period) {
            $this->assertEquals(PayPeriod::FREQUENCY_BIWEEKLY, $period->frequency);
            $this->assertEquals(PayPeriod::STATUS_OPEN, $period->status);
        });
    }

    public function test_generate_pay_periods_creates_weekly_periods(): void
    {
        $startDate = Carbon::parse('2025-04-01');
        $endDate = Carbon::parse('2025-05-01');

        $periods = $this->service->generatePayPeriods(
            $this->company->id,
            $startDate,
            $endDate,
            PayPeriod::FREQUENCY_WEEKLY
        );

        $this->assertGreaterThan(0, $periods->count());
        $periods->each(function ($period) {
            $this->assertEquals(PayPeriod::FREQUENCY_WEEKLY, $period->frequency);
        });
    }

    public function test_generate_pay_periods_creates_monthly_periods(): void
    {
        $startDate = Carbon::parse('2025-06-01');
        $endDate = Carbon::parse('2025-09-01');

        $periods = $this->service->generatePayPeriods(
            $this->company->id,
            $startDate,
            $endDate,
            PayPeriod::FREQUENCY_MONTHLY
        );

        $this->assertGreaterThan(0, $periods->count());
        $periods->each(function ($period) {
            $this->assertEquals(PayPeriod::FREQUENCY_MONTHLY, $period->frequency);
        });
    }

    public function test_generate_pay_periods_does_not_create_duplicates(): void
    {
        $startDate = Carbon::parse('2025-10-01');
        $endDate = Carbon::parse('2025-11-01');

        $firstRun = $this->service->generatePayPeriods(
            $this->company->id,
            $startDate,
            $endDate,
            PayPeriod::FREQUENCY_BIWEEKLY
        );

        $secondRun = $this->service->generatePayPeriods(
            $this->company->id,
            $startDate,
            $endDate,
            PayPeriod::FREQUENCY_BIWEEKLY
        );

        $this->assertEquals($firstRun->count(), $secondRun->count());
    }

    public function test_get_summary_statistics_returns_correct_data(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'total_minutes' => 480,
            'regular_minutes' => 420,
            'overtime_minutes' => 60,
        ]);

        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-06 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-06 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
            'regular_minutes' => 480,
        ]);

        $stats = $this->service->getSummaryStatistics($this->payPeriod);

        $this->assertEquals(2, $stats['total_entries']);
        $this->assertEquals(1, $stats['approved_entries']);
        $this->assertEquals(1, $stats['pending_entries']);
        $this->assertEquals(16.0, $stats['total_hours']);
        $this->assertEquals(15.0, $stats['regular_hours']);
        $this->assertEquals(1.0, $stats['overtime_hours']);
        $this->assertEquals(1, $stats['unique_employees']);
    }

    public function test_get_summary_statistics_counts_exported_entries(): void
    {
        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'exported_to_payroll' => true,
        ]);

        EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-06 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-06 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_APPROVED,
            'exported_to_payroll' => false,
        ]);

        $stats = $this->service->getSummaryStatistics($this->payPeriod);

        $this->assertEquals(1, $stats['exported_entries']);
        $this->assertEquals(1, $stats['not_exported_entries']);
    }
}
