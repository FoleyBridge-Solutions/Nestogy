<?php

namespace Tests\Unit\Models\Project;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectTask;
use App\Domains\Project\Models\ProjectTimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    public function test_can_create_project(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $project->company);
        $this->assertEquals($this->company->id, $project->company->id);
    }

    public function test_belongs_to_client(): void
    {
        $project = Project::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Client::class, $project->client);
        $this->assertEquals($this->client->id, $project->client->id);
    }

    public function test_has_many_tasks(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ProjectTask::factory()->count(5)->create([
            'project_id' => $project->id,
        ]);

        $this->assertCount(5, $project->tasks);
    }

    public function test_has_many_time_entries(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ProjectTimeEntry::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $this->assertCount(3, $project->timeEntries);
    }

    public function test_casts_dates_properly(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $project->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $project->end_date);
    }
}
