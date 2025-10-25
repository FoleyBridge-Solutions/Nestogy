<?php

namespace Tests\Unit\HR\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayPeriodTest extends TestCase
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

    public function test_can_create_pay_period(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $this->assertInstanceOf(PayPeriod::class, $payPeriod);
        $this->assertEquals($this->company->id, $payPeriod->company_id);
    }

    public function test_has_many_time_entries(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $payPeriod->id,
            'clock_in' => Carbon::parse('2025-01-02 09:00:00'),
            'clock_out' => Carbon::parse('2025-01-02 17:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $payPeriod->id,
            'clock_in' => Carbon::parse('2025-01-03 09:00:00'),
            'clock_out' => Carbon::parse('2025-01-03 17:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        $this->assertEquals(2, $payPeriod->timeEntries()->count());
    }

    public function test_belongs_to_approved_by_user(): void
    {
        $approver = User::factory()->create(['company_id' => $this->company->id]);

        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $payPeriod->approvedBy);
        $this->assertEquals($approver->id, $payPeriod->approvedBy->id);
    }

    public function test_is_open_status(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $this->assertTrue($payPeriod->isOpen());
        $this->assertFalse($payPeriod->isApproved());
        $this->assertFalse($payPeriod->isPaid());
    }

    public function test_is_approved_status(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $this->assertTrue($payPeriod->isApproved());
        $this->assertFalse($payPeriod->isOpen());
        $this->assertFalse($payPeriod->isPaid());
    }

    public function test_is_paid_status(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_PAID,
        ]);

        $this->assertTrue($payPeriod->isPaid());
        $this->assertFalse($payPeriod->isOpen());
        $this->assertFalse($payPeriod->isApproved());
    }

    public function test_get_total_minutes(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $payPeriod->id,
            'clock_in' => Carbon::parse('2025-01-02 09:00:00'),
            'clock_out' => Carbon::parse('2025-01-02 17:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $payPeriod->id,
            'clock_in' => Carbon::parse('2025-01-03 09:00:00'),
            'clock_out' => Carbon::parse('2025-01-03 17:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        $this->assertEquals(960, $payPeriod->getTotalMinutes());
    }

    public function test_get_total_hours(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $payPeriod->id,
            'clock_in' => Carbon::parse('2025-01-02 09:00:00'),
            'clock_out' => Carbon::parse('2025-01-02 17:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
            'total_minutes' => 480,
        ]);

        $this->assertEquals(8.0, $payPeriod->getTotalHours());
    }

    public function test_get_label(): void
    {
        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $this->assertEquals('Jan 01 - Jan 15, 2025', $payPeriod->getLabel());
    }

    public function test_scope_open(): void
    {
        PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-16'),
            'end_date' => Carbon::parse('2025-01-31'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_APPROVED,
        ]);

        $this->assertEquals(1, PayPeriod::open()->count());
    }

    public function test_scope_approved(): void
    {
        PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-01-15'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_APPROVED,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2025-01-16'),
            'end_date' => Carbon::parse('2025-01-31'),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $this->assertEquals(1, PayPeriod::approved()->count());
    }

    public function test_scope_current(): void
    {
        PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => today()->subDays(5),
            'end_date' => today()->addDays(5),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => today()->subDays(20),
            'end_date' => today()->subDays(10),
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_APPROVED,
        ]);

        $this->assertEquals(1, PayPeriod::current()->count());
    }

    public function test_casts_dates_correctly(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-15');

        $payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $this->assertInstanceOf(Carbon::class, $payPeriod->start_date);
        $this->assertInstanceOf(Carbon::class, $payPeriod->end_date);
    }

    public function test_frequency_constants(): void
    {
        $this->assertEquals('weekly', PayPeriod::FREQUENCY_WEEKLY);
        $this->assertEquals('biweekly', PayPeriod::FREQUENCY_BIWEEKLY);
        $this->assertEquals('semimonthly', PayPeriod::FREQUENCY_SEMIMONTHLY);
        $this->assertEquals('monthly', PayPeriod::FREQUENCY_MONTHLY);
    }

    public function test_status_constants(): void
    {
        $this->assertEquals('open', PayPeriod::STATUS_OPEN);
        $this->assertEquals('in_review', PayPeriod::STATUS_IN_REVIEW);
        $this->assertEquals('approved', PayPeriod::STATUS_APPROVED);
        $this->assertEquals('paid', PayPeriod::STATUS_PAID);
    }
}
