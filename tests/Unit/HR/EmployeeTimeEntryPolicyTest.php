<?php

namespace Tests\Unit\HR;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeTimeEntry;
use App\Policies\EmployeeTimeEntryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class EmployeeTimeEntryPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected EmployeeTimeEntryPolicy $policy;
    protected Company $company;
    protected User $admin;
    protected User $manager;
    protected User $employee;
    protected EmployeeTimeEntry $timeEntry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new EmployeeTimeEntryPolicy();
        $this->company = Company::factory()->create();
        
        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        Bouncer::allow($this->admin)->everything();
        
        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        Bouncer::allow($this->manager)->to('hr.time-entries.view');
        Bouncer::allow($this->manager)->to('hr.time-entries.edit');
        Bouncer::allow($this->manager)->to('hr.time-entries.approve');
        
        $this->employee = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->timeEntry = EmployeeTimeEntry::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->employee->id,
        ]);
    }

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_manager_with_permission_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->manager));
    }

    public function test_employee_without_permission_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny($this->employee));
    }

    public function test_admin_can_view_entry(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->timeEntry));
    }

    public function test_manager_with_permission_can_view_entry(): void
    {
        $this->assertTrue($this->policy->view($this->manager, $this->timeEntry));
    }

    public function test_employee_can_view_own_entry(): void
    {
        $this->assertTrue($this->policy->view($this->employee, $this->timeEntry));
    }

    public function test_employee_cannot_view_others_entry(): void
    {
        $otherEmployee = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertFalse($this->policy->view($otherEmployee, $this->timeEntry));
    }

    public function test_cannot_view_entry_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        Bouncer::allow($otherUser)->to('hr.time-entries.view');
        
        $this->assertFalse($this->policy->view($otherUser, $this->timeEntry));
    }

    public function test_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_manager_with_permission_can_create(): void
    {
        Bouncer::allow($this->manager)->to('hr.time-entries.create');
        
        $this->assertTrue($this->policy->create($this->manager));
    }

    public function test_employee_without_permission_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->employee));
    }

    public function test_admin_can_update_entry(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->timeEntry));
    }

    public function test_manager_with_permission_can_update_entry(): void
    {
        $this->assertTrue($this->policy->update($this->manager, $this->timeEntry));
    }

    public function test_employee_can_update_own_entry(): void
    {
        $this->assertTrue($this->policy->update($this->employee, $this->timeEntry));
    }

    public function test_employee_cannot_update_others_entry(): void
    {
        $otherEmployee = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->assertFalse($this->policy->update($otherEmployee, $this->timeEntry));
    }

    public function test_cannot_update_entry_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        Bouncer::allow($otherUser)->to('hr.time-entries.edit');
        
        $this->assertFalse($this->policy->update($otherUser, $this->timeEntry));
    }

    public function test_cannot_update_exported_entry(): void
    {
        $this->timeEntry->exported_to_payroll = true;
        $this->timeEntry->save();
        
        $this->assertFalse($this->policy->update($this->admin, $this->timeEntry));
    }

    public function test_admin_can_delete_entry(): void
    {
        Bouncer::allow($this->admin)->to('hr.time-entries.delete');
        
        $this->assertTrue($this->policy->delete($this->admin, $this->timeEntry));
    }

    public function test_manager_with_permission_can_delete_entry(): void
    {
        Bouncer::allow($this->manager)->to('hr.time-entries.delete');
        
        $this->assertTrue($this->policy->delete($this->manager, $this->timeEntry));
    }

    public function test_employee_cannot_delete_entry(): void
    {
        $this->assertFalse($this->policy->delete($this->employee, $this->timeEntry));
    }

    public function test_cannot_delete_entry_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        Bouncer::allow($otherUser)->everything();
        
        $this->assertFalse($this->policy->delete($otherUser, $this->timeEntry));
    }

    public function test_cannot_delete_exported_entry(): void
    {
        $this->timeEntry->exported_to_payroll = true;
        $this->timeEntry->save();
        
        Bouncer::allow($this->admin)->to('hr.time-entries.delete');
        $this->assertFalse($this->policy->delete($this->admin, $this->timeEntry));
    }

    public function test_admin_can_approve_entry(): void
    {
        $this->assertTrue($this->policy->approve($this->admin, $this->timeEntry));
    }

    public function test_manager_with_permission_can_approve_entry(): void
    {
        $this->assertTrue($this->policy->approve($this->manager, $this->timeEntry));
    }

    public function test_employee_cannot_approve_entry(): void
    {
        $this->assertFalse($this->policy->approve($this->employee, $this->timeEntry));
    }

    public function test_cannot_approve_entry_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        Bouncer::allow($otherUser)->to('hr.time-entries.approve');
        
        $this->assertFalse($this->policy->approve($otherUser, $this->timeEntry));
    }

    public function test_admin_can_restore_entry(): void
    {
        Bouncer::allow($this->admin)->to('hr.time-entries.manage');
        
        $this->assertTrue($this->policy->restore($this->admin, $this->timeEntry));
    }

    public function test_manager_without_manage_permission_cannot_restore(): void
    {
        $this->assertFalse($this->policy->restore($this->manager, $this->timeEntry));
    }

    public function test_admin_can_force_delete_entry(): void
    {
        Bouncer::allow($this->admin)->to('hr.time-entries.manage');
        
        $this->assertTrue($this->policy->forceDelete($this->admin, $this->timeEntry));
    }

    public function test_manager_without_manage_permission_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->manager, $this->timeEntry));
    }
}
