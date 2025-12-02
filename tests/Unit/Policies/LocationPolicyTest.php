<?php

namespace Tests\Unit\Policies;

use App\Domains\Client\Models\Location;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Policies\LocationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected LocationPolicy $policy;

    protected Company $company;

    protected User $admin;

    protected User $manager;

    protected User $user;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);

        $this->admin->assign('admin');
        $this->manager->allow('clients.locations.manage');
        $this->user->allow('clients.locations.view');

        $this->location = Location::factory()->create(['company_id' => $this->company->id]);

        $this->policy = new LocationPolicy;
    }

    public function test_admin_can_view_any_locations(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_user_with_permission_can_view_any_locations(): void
    {
        $this->actingAs($this->user);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_admin_can_view_location_from_same_company(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->view($this->admin, $this->location));
    }

    public function test_admin_cannot_view_location_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherLocation = Location::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($this->admin);

        $this->assertFalse($this->policy->view($this->admin, $otherLocation));
    }

    public function test_admin_can_create_locations(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_manager_with_permission_can_create_locations(): void
    {
        $this->actingAs($this->manager);

        $this->assertTrue($this->policy->create($this->manager));
    }

    public function test_admin_can_update_location(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->update($this->admin, $this->location));
    }

    public function test_manager_can_update_location(): void
    {
        $this->actingAs($this->manager);

        $this->assertTrue($this->policy->update($this->manager, $this->location));
    }

    public function test_regular_user_cannot_update_location(): void
    {
        $this->actingAs($this->user);

        $this->assertFalse($this->policy->update($this->user, $this->location));
    }

    public function test_cannot_delete_primary_location(): void
    {
        $primaryLocation = Location::factory()->create([
            'company_id' => $this->company->id,
            'primary' => true,
        ]);

        $this->actingAs($this->admin);

        $this->assertFalse($this->policy->delete($this->admin, $primaryLocation));
    }

    public function test_admin_can_delete_non_primary_location(): void
    {
        $nonPrimaryLocation = Location::factory()->create([
            'company_id' => $this->company->id,
            'primary' => false,
        ]);

        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->delete($this->admin, $nonPrimaryLocation));
    }

    public function test_admin_can_restore_location(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->restore($this->admin, $this->location));
    }

    public function test_non_admin_cannot_restore_location(): void
    {
        $this->actingAs($this->user);

        $this->assertFalse($this->policy->restore($this->user, $this->location));
    }

    public function test_admin_can_force_delete_location(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->policy->forceDelete($this->admin, $this->location));
    }

    public function test_non_admin_cannot_force_delete_location(): void
    {
        $this->actingAs($this->manager);

        $this->assertFalse($this->policy->forceDelete($this->manager, $this->location));
    }
}
