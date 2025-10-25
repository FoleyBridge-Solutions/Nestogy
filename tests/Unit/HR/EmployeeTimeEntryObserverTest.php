<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTimeEntryObserverTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected PayPeriod $payPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->payPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-14'),
        ]);
    }

    public function test_creating_entry_assigns_pay_period(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertNotNull($entry->pay_period_id);
        $this->assertEquals($this->payPeriod->id, $entry->pay_period_id);
    }

    public function test_creating_entry_outside_pay_period_does_not_assign_pay_period(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2025-12-31 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_MANUAL,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
            'total_minutes' => 0,
            'regular_minutes' => 0,
        ]);

        $this->assertNull($entry->pay_period_id);
    }

    public function test_creating_entry_with_existing_pay_period_id_does_not_overwrite(): void
    {
        $otherPayPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-15'),
            'end_date' => Carbon::parse('2024-01-28'),
        ]);

        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'pay_period_id' => $otherPayPeriod->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertEquals($otherPayPeriod->id, $entry->pay_period_id);
    }

    public function test_updating_clock_in_reassigns_pay_period(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'pay_period_id' => $this->payPeriod->id,
        ]);

        $newPayPeriod = PayPeriod::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => Carbon::parse('2024-01-15'),
            'end_date' => Carbon::parse('2024-01-28'),
        ]);

        $entry->clock_in = Carbon::parse('2024-01-20 09:00:00');
        $entry->save();

        $this->assertEquals($newPayPeriod->id, $entry->pay_period_id);
    }

    public function test_updating_non_clock_in_field_does_not_reassign_pay_period(): void
    {
        $entry = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'pay_period_id' => $this->payPeriod->id,
        ]);

        $originalPayPeriodId = $entry->pay_period_id;

        $entry->notes = 'Updated notes';
        $entry->save();

        $this->assertEquals($originalPayPeriodId, $entry->pay_period_id);
    }

    public function test_assigns_null_when_no_matching_pay_period_exists(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2025-12-31 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertNull($entry->pay_period_id);
    }

    public function test_assigns_pay_period_at_start_boundary(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-01 00:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertEquals($this->payPeriod->id, $entry->pay_period_id);
    }

    public function test_assigns_pay_period_at_end_boundary(): void
    {
        $entry = EmployeeTimeEntry::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'clock_in' => Carbon::parse('2024-01-14 23:59:59'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertEquals($this->payPeriod->id, $entry->pay_period_id);
    }

    public function test_does_not_assign_pay_period_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        
        PayPeriod::factory()->create([
            'company_id' => $otherCompany->id,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-14'),
        ]);

        $entry = EmployeeTimeEntry::create([
            'company_id' => $otherCompany->id,
            'user_id' => $otherUser->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'entry_type' => EmployeeTimeEntry::TYPE_CLOCK,
            'status' => EmployeeTimeEntry::STATUS_IN_PROGRESS,
        ]);

        $this->assertNotNull($entry->pay_period_id);
        $this->assertNotEquals($this->payPeriod->id, $entry->pay_period_id);
    }
}
