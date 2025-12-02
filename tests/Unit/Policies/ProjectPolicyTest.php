<?php

namespace Tests\Unit\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Project\Models\Project;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectPolicy $policy;
    protected Company $company;
    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProjectPolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_projects_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Project::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_project_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->project);

        $this->assertTrue($this->policy->view($this->user, $this->project));
    }

    public function test_user_cannot_view_project_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherProject = Project::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherProject));
    }

    public function test_user_can_create_project_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Project::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_project_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->project);

        $this->assertTrue($this->policy->update($this->user, $this->project));
    }

    public function test_user_can_delete_project_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->project);

        $this->assertTrue($this->policy->delete($this->user, $this->project));
    }
}
