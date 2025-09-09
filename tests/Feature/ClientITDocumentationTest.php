<?php

namespace Tests\Feature;

use App\Domains\Client\Models\ClientITDocumentation;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientITDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'tenant_id' => 1
        ]);
        
        $this->client = Client::factory()->create([
            'tenant_id' => 1
        ]);
        
        Storage::fake('local');
    }

    /** @test */
    public function it_can_display_it_documentation_index()
    {
        $documentation = ClientITDocumentation::factory()->count(3)->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.index'));

        $response->assertStatus(200);
        $response->assertViewHas('documentation');
        $response->assertSee($documentation->first()->name);
    }

    /** @test */
    public function it_can_create_new_it_documentation()
    {
        $data = [
            'client_id' => $this->client->id,
            'name' => 'Test Network Setup Guide',
            'description' => 'A comprehensive guide for network setup',
            'it_category' => 'runbook',
            'access_level' => 'confidential',
            'review_schedule' => 'quarterly',
            'tags' => 'network, setup, guide',
            'procedure_steps' => [
                [
                    'title' => 'Step 1',
                    'description' => 'Configure the router',
                    'order' => 1
                ],
                [
                    'title' => 'Step 2', 
                    'description' => 'Test connectivity',
                    'order' => 2
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('clients.it-documentation.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('client_it_documentation', [
            'name' => 'Test Network Setup Guide',
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'it_category' => 'runbook',
            'access_level' => 'confidential',
        ]);
    }

    /** @test */
    public function it_can_create_documentation_with_file_upload()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1024);

        $data = [
            'client_id' => $this->client->id,
            'name' => 'Test Documentation with File',
            'it_category' => 'architecture',
            'access_level' => 'public',
            'review_schedule' => 'annually',
            'file' => $file,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('clients.it-documentation.store'), $data);

        $response->assertRedirect();
        
        $documentation = ClientITDocumentation::where('name', 'Test Documentation with File')->first();
        $this->assertNotNull($documentation);
        $this->assertNotNull($documentation->file_path);
        $this->assertEquals('test-document.pdf', $documentation->original_filename);
        Storage::assertExists($documentation->file_path);
    }

    /** @test */
    public function it_can_display_specific_documentation()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.show', $documentation));

        $response->assertStatus(200);
        $response->assertViewHas('itDocumentation');
        $response->assertSee($documentation->name);
    }

    /** @test */
    public function it_can_update_existing_documentation()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'client_id' => $this->client->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'it_category' => 'troubleshooting',
            'access_level' => 'restricted',
            'review_schedule' => 'monthly',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('clients.it-documentation.update', $documentation), $updateData);

        $response->assertRedirect();
        $documentation->refresh();
        $this->assertEquals('Updated Name', $documentation->name);
        $this->assertEquals('troubleshooting', $documentation->it_category);
    }

    /** @test */
    public function it_can_delete_documentation()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('clients.it-documentation.destroy', $documentation));

        $response->assertRedirect();
        $this->assertSoftDeleted($documentation);
    }

    /** @test */
    public function it_can_download_attached_file()
    {
        Storage::put('clients/it-documentation/test-file.pdf', 'fake content');
        
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'file_path' => 'clients/it-documentation/test-file.pdf',
            'original_filename' => 'test-document.pdf',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.download', $documentation));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=test-document.pdf');
    }

    /** @test */
    public function it_can_filter_documentation_by_category()
    {
        $runbook = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'it_category' => 'runbook',
            'name' => 'Runbook Documentation'
        ]);

        $architecture = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'it_category' => 'architecture',
            'name' => 'Architecture Documentation'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.index', ['it_category' => 'runbook']));

        $response->assertStatus(200);
        $response->assertSee('Runbook Documentation');
        $response->assertDontSee('Architecture Documentation');
    }

    /** @test */
    public function it_can_search_documentation()
    {
        $searchable = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Network Configuration Guide',
        ]);

        $other = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Backup Procedures',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.index', ['search' => 'Network']));

        $response->assertStatus(200);
        $response->assertSee('Network Configuration Guide');
        $response->assertDontSee('Backup Procedures');
    }

    /** @test */
    public function it_respects_tenant_isolation()
    {
        $otherTenantUser = User::factory()->create(['tenant_id' => 2]);
        $otherTenantClient = Client::factory()->create(['tenant_id' => 2]);
        
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => 2,
            'client_id' => $otherTenantClient->id,
            'authored_by' => $otherTenantUser->id,
        ]);

        // User from tenant 1 should not see documentation from tenant 2
        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.index'));

        $response->assertStatus(200);
        $response->assertDontSee($documentation->name);

        // User from tenant 1 should not be able to view documentation from tenant 2
        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.show', $documentation));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_mark_documentation_as_reviewed()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'next_review_at' => now()->subDays(30), // Overdue review
        ]);

        $this->assertTrue($documentation->needsReview());

        $response = $this->actingAs($this->user)
            ->post(route('clients.it-documentation.complete-review', $documentation));

        $response->assertRedirect();
        
        $documentation->refresh();
        $this->assertNotNull($documentation->last_reviewed_at);
        $this->assertTrue($documentation->next_review_at->isFuture());
    }

    /** @test */
    public function it_can_export_documentation()
    {
        ClientITDocumentation::factory()->count(2)->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.it-documentation.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}