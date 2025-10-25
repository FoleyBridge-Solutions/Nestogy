<?php

namespace Tests\Feature\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Domains\HR\Models\PayPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class EmployeeTimeEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $admin;
    protected User $manager;
    protected User $employee;
    protected EmployeeTimeEntry $timeEntry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        
        Bouncer::scope()->to($this->company->id);
        
        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        Bouncer::allow($this->admin)->everything();
        
        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        Bouncer::allow($this->manager)->to('manage-hr');
        Bouncer::allow($this->manager)->to('hr.time-entries.view');
        Bouncer::allow($this->manager)->to('hr.time-entries.edit');
        Bouncer::allow($this->manager)->to('hr.time-entries.approve');
        Bouncer::allow($this->manager)->to('hr.time-entries.delete');
        Bouncer::refreshFor($this->manager);
        
        $this->employee = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->timeEntry = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->employee->id,
            'clock_in' => Carbon::parse('2024-01-05 09:00:00'),
            'clock_out' => Carbon::parse('2024-01-05 17:00:00'),
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);
    }

    public function test_show_displays_time_entry(): void
    {
        $this->assertTrue($this->manager->can('view', $this->timeEntry));
    }

    public function test_employee_can_view_own_entry(): void
    {
        $this->assertTrue($this->employee->can('view', $this->timeEntry));
    }

    public function test_employee_cannot_view_others_entry(): void
    {
        $otherEmployee = User::factory()->create(['company_id' => $this->company->id]);

        $this->assertFalse($otherEmployee->can('view', $this->timeEntry));
    }

    public function test_edit_displays_form(): void
    {
        $this->assertTrue($this->manager->can('update', $this->timeEntry));
    }

    public function test_cannot_edit_exported_entry(): void
    {
        $this->timeEntry->exported_to_payroll = true;
        $this->timeEntry->save();

        $this->assertFalse($this->manager->can('update', $this->timeEntry));
    }

    public function test_update_modifies_time_entry(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('hr.time-entries.update', $this->timeEntry), [
                'clock_in' => '2024-01-05 08:00:00',
                'clock_out' => '2024-01-05 16:00:00',
                'break_minutes' => 30,
                'notes' => 'Updated notes',
            ]);

        $response->assertRedirect(route('hr.time-entries.show', $this->timeEntry));
        $response->assertSessionHas('success');

        $this->timeEntry->refresh();
        $this->assertEquals('2024-01-05 08:00:00', $this->timeEntry->clock_in->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-05 16:00:00', $this->timeEntry->clock_out->format('Y-m-d H:i:s'));
        $this->assertEquals(30, $this->timeEntry->break_minutes);
        $this->assertEquals('Updated notes', $this->timeEntry->notes);
        $this->assertEquals(EmployeeTimeEntry::TYPE_ADJUSTED, $this->timeEntry->entry_type);
    }

    public function test_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('hr.time-entries.update', $this->timeEntry), [
                'clock_in' => '',
                'clock_out' => '',
            ]);

        $response->assertSessionHasErrors(['clock_in', 'clock_out']);
    }

    public function test_update_validates_clock_out_after_clock_in(): void
    {
        $response = $this->actingAs($this->manager)
            ->put(route('hr.time-entries.update', $this->timeEntry), [
                'clock_in' => '2024-01-05 17:00:00',
                'clock_out' => '2024-01-05 09:00:00',
                'break_minutes' => 0,
            ]);

        $response->assertSessionHasErrors(['clock_out']);
    }

    public function test_cannot_update_exported_entry(): void
    {
        $this->timeEntry->exported_to_payroll = true;
        $this->timeEntry->save();

        $response = $this->actingAs($this->manager)
            ->put(route('hr.time-entries.update', $this->timeEntry), [
                'clock_in' => '2024-01-05 08:00:00',
                'clock_out' => '2024-01-05 16:00:00',
                'break_minutes' => 30,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_destroy_deletes_time_entry(): void
    {
        $entryId = $this->timeEntry->id;

        $response = $this->actingAs($this->manager)
            ->delete(route('hr.time-entries.destroy', $this->timeEntry));

        $response->assertRedirect(route('hr.time-entries.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('employee_time_entries', ['id' => $entryId]);
    }

    public function test_cannot_delete_exported_entry(): void
    {
        $this->timeEntry->exported_to_payroll = true;
        $this->timeEntry->save();

        $response = $this->actingAs($this->manager)
            ->delete(route('hr.time-entries.destroy', $this->timeEntry));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('employee_time_entries', [
            'id' => $this->timeEntry->id,
        ]);
    }

    public function test_employee_cannot_delete_entry(): void
    {
        $response = $this->actingAs($this->employee)
            ->delete(route('hr.time-entries.destroy', $this->timeEntry));

        $response->assertForbidden();
    }

    public function test_approve_updates_entry_status(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.approve', $this->timeEntry));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->timeEntry->refresh();
        $this->assertEquals(EmployeeTimeEntry::STATUS_APPROVED, $this->timeEntry->status);
        $this->assertEquals($this->manager->id, $this->timeEntry->approved_by);
        $this->assertNotNull($this->timeEntry->approved_at);
    }

    public function test_approve_returns_json_when_requested(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson(route('hr.time-entries.approve', $this->timeEntry));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'entry',
        ]);
    }

    public function test_employee_cannot_approve_entry(): void
    {
        $response = $this->actingAs($this->employee)
            ->post(route('hr.time-entries.approve', $this->timeEntry));

        $response->assertForbidden();
    }

    public function test_reject_updates_entry_status(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.reject', $this->timeEntry), [
                'rejection_reason' => 'Invalid hours',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->timeEntry->refresh();
        $this->assertEquals(EmployeeTimeEntry::STATUS_REJECTED, $this->timeEntry->status);
        $this->assertEquals($this->manager->id, $this->timeEntry->rejected_by);
        $this->assertNotNull($this->timeEntry->rejected_at);
        $this->assertEquals('Invalid hours', $this->timeEntry->rejection_reason);
    }

    public function test_reject_requires_reason(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.reject', $this->timeEntry));

        $response->assertSessionHasErrors(['rejection_reason']);
    }

    public function test_reject_returns_json_when_requested(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson(route('hr.time-entries.reject', $this->timeEntry), [
                'rejection_reason' => 'Invalid hours',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'entry',
        ]);
    }

    public function test_bulk_approve_approves_multiple_entries(): void
    {
        Bouncer::allow($this->manager)->to('manage-hr');

        $entry1 = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $entry2 = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.bulk-approve'), [
                'entry_ids' => [$entry1->id, $entry2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $entry1->refresh();
        $entry2->refresh();

        $this->assertEquals(EmployeeTimeEntry::STATUS_APPROVED, $entry1->status);
        $this->assertEquals(EmployeeTimeEntry::STATUS_APPROVED, $entry2->status);
    }

    public function test_bulk_approve_requires_manage_hr_permission(): void
    {
        $entry1 = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->employee)
            ->post(route('hr.time-entries.bulk-approve'), [
                'entry_ids' => [$entry1->id],
            ]);

        $response->assertForbidden();
    }

    public function test_bulk_approve_validates_entry_ids(): void
    {
        Bouncer::allow($this->manager)->to('manage-hr');

        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.bulk-approve'), [
                'entry_ids' => [99999],
            ]);

        $response->assertSessionHasErrors(['entry_ids.0']);
    }

    public function test_bulk_approve_only_affects_own_company(): void
    {
        Bouncer::allow($this->manager)->to('manage-hr');

        $otherCompany = Company::factory()->create();
        $otherEntry = EmployeeTimeEntry::factory()->create([
            'company_id' => $otherCompany->id,
            'status' => EmployeeTimeEntry::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.bulk-approve'), [
                'entry_ids' => [$otherEntry->id],
            ]);

        $response->assertRedirect();

        $otherEntry->refresh();
        $this->assertEquals(EmployeeTimeEntry::STATUS_COMPLETED, $otherEntry->status);
    }

    public function test_bulk_export_redirects_to_payroll_export(): void
    {
        Bouncer::allow($this->manager)->to('manage-hr');

        $payPeriod = PayPeriod::factory()->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.bulk-export'), [
                'pay_period_id' => $payPeriod->id,
            ]);

        $response->assertRedirect(route('hr.payroll.export', $payPeriod));
    }

    public function test_bulk_export_validates_pay_period(): void
    {
        Bouncer::allow($this->manager)->to('manage-hr');

        $response = $this->actingAs($this->manager)
            ->post(route('hr.time-entries.bulk-export'), [
                'pay_period_id' => 99999,
            ]);

        $response->assertSessionHasErrors(['pay_period_id']);
    }

    public function test_guest_cannot_access_time_entries(): void
    {
        $response = $this->get(route('hr.time-entries.show', $this->timeEntry));
        $response->assertRedirect(route('login'));
    }
}
