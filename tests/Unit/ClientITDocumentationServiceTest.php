<?php

namespace Tests\Unit;

use App\Domains\Client\Models\ClientITDocumentation;
use App\Domains\Client\Services\ClientITDocumentationService;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientITDocumentationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientITDocumentationService $service;
    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ClientITDocumentationService();
        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->client = Client::factory()->create(['tenant_id' => 1]);
        
        $this->actingAs($this->user);
        Storage::fake('local');
    }

    /** @test */
    public function it_can_create_it_documentation()
    {
        $data = [
            'client_id' => $this->client->id,
            'name' => 'Test Documentation',
            'description' => 'Test description',
            'it_category' => 'runbook',
            'access_level' => 'confidential',
            'review_schedule' => 'quarterly',
        ];

        $documentation = $this->service->createITDocumentation($data);

        $this->assertInstanceOf(ClientITDocumentation::class, $documentation);
        $this->assertEquals('Test Documentation', $documentation->name);
        $this->assertEquals($this->client->id, $documentation->client_id);
        $this->assertEquals($this->user->id, $documentation->authored_by);
        $this->assertEquals($this->user->tenant_id, $documentation->tenant_id);
    }

    /** @test */
    public function it_can_create_documentation_with_file()
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024);
        
        $data = [
            'client_id' => $this->client->id,
            'name' => 'Test Documentation with File',
            'it_category' => 'architecture',
            'access_level' => 'public',
            'review_schedule' => 'annually',
        ];

        $documentation = $this->service->createITDocumentation($data, $file);

        $this->assertNotNull($documentation->file_path);
        $this->assertEquals('test.pdf', $documentation->original_filename);
        $this->assertEquals(1024, $documentation->file_size);
        Storage::assertExists($documentation->file_path);
    }

    /** @test */
    public function it_sets_next_review_date_based_on_schedule()
    {
        $data = [
            'client_id' => $this->client->id,
            'name' => 'Monthly Review Doc',
            'it_category' => 'runbook',
            'access_level' => 'confidential',
            'review_schedule' => 'monthly',
        ];

        $documentation = $this->service->createITDocumentation($data);
        
        $this->assertTrue($documentation->next_review_at->isNextMonth());
    }

    /** @test */
    public function it_can_update_documentation()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'it_category' => 'troubleshooting',
        ];

        $updated = $this->service->updateITDocumentation($documentation, $updateData);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Updated description', $updated->description);
        $this->assertEquals('troubleshooting', $updated->it_category);
    }

    /** @test */
    public function it_can_generate_new_version()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'version' => '1.0',
        ]);

        $versionData = [
            'description' => 'Updated version with new procedures',
        ];

        $newVersion = $this->service->generateNewVersion($documentation, $versionData);

        $this->assertEquals('1.1', $newVersion->version);
        $this->assertEquals($documentation->id, $newVersion->parent_document_id);
        $this->assertEquals($this->user->id, $newVersion->authored_by);
        $this->assertEquals('Updated version with new procedures', $newVersion->description);
    }

    /** @test */
    public function it_can_search_documentation()
    {
        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Network Setup Guide',
            'it_category' => 'runbook',
        ]);

        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Backup Procedures',
            'it_category' => 'backup_recovery',
        ]);

        $results = $this->service->searchDocumentation(['search' => 'Network']);
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Network Setup Guide', $results->first()->name);

        $categoryResults = $this->service->searchDocumentation(['it_category' => 'runbook']);
        $this->assertEquals(1, $categoryResults->count());
        $this->assertEquals('Network Setup Guide', $categoryResults->first()->name);
    }

    /** @test */
    public function it_can_get_client_statistics()
    {
        ClientITDocumentation::factory()->count(3)->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'it_category' => 'runbook',
            'is_active' => true,
        ]);

        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'it_category' => 'troubleshooting',
            'next_review_at' => now()->subDays(30), // Needs review
        ]);

        $stats = $this->service->getClientStatistics($this->client->id);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(4, $stats['active']);
        $this->assertEquals(1, $stats['needs_review']);
        $this->assertEquals(3, $stats['by_category']['runbook']);
        $this->assertEquals(1, $stats['by_category']['troubleshooting']);
    }

    /** @test */
    public function it_can_duplicate_documentation_for_another_client()
    {
        $sourceClient = $this->client;
        $targetClient = Client::factory()->create(['tenant_id' => $this->user->tenant_id]);

        $original = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $sourceClient->id,
            'authored_by' => $this->user->id,
            'name' => 'Original Documentation',
        ]);

        $duplicate = $this->service->duplicateForClient($original, $targetClient->id);

        $this->assertEquals($targetClient->id, $duplicate->client_id);
        $this->assertEquals('Original Documentation (Copy)', $duplicate->name);
        $this->assertEquals('1.0', $duplicate->version);
        $this->assertNull($duplicate->parent_document_id);
        $this->assertEquals($this->user->id, $duplicate->authored_by);
    }

    /** @test */
    public function it_can_schedule_review()
    {
        $documentation = ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'review_schedule' => 'annually',
            'last_reviewed_at' => null,
            'next_review_at' => null,
        ]);

        $this->service->scheduleReview($documentation, 'quarterly');

        $documentation->refresh();
        $this->assertEquals('quarterly', $documentation->review_schedule);
        $this->assertNotNull($documentation->last_reviewed_at);
        $this->assertNotNull($documentation->next_review_at);
        $this->assertTrue($documentation->next_review_at->isNextQuarter());
    }

    /** @test */
    public function it_can_get_overdue_reviews()
    {
        // Create documentation that needs review
        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Overdue Doc 1',
            'next_review_at' => now()->subDays(30),
            'is_active' => true,
        ]);

        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Overdue Doc 2',
            'next_review_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        // Create documentation that doesn't need review
        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Current Doc',
            'next_review_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        $overdueReviews = $this->service->getOverdueReviews();
        
        $this->assertEquals(2, $overdueReviews->count());
        $this->assertTrue($overdueReviews->contains('name', 'Overdue Doc 1'));
        $this->assertTrue($overdueReviews->contains('name', 'Overdue Doc 2'));
    }

    /** @test */
    public function it_can_bulk_update_access_levels()
    {
        $docs = ClientITDocumentation::factory()->count(3)->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'access_level' => 'confidential',
        ]);

        $docIds = $docs->pluck('id')->toArray();
        
        $updated = $this->service->bulkUpdateAccessLevel($docIds, 'public');
        
        $this->assertEquals(3, $updated);
        
        $docs->each(function ($doc) {
            $doc->refresh();
            $this->assertEquals('public', $doc->access_level);
        });
    }

    /** @test */
    public function it_can_export_data()
    {
        ClientITDocumentation::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => $this->client->id,
            'authored_by' => $this->user->id,
            'name' => 'Export Test Doc',
            'tags' => ['test', 'export'],
        ]);

        $exportData = $this->service->exportData();
        
        $this->assertEquals(1, $exportData->count());
        
        $record = $exportData->first();
        $this->assertEquals('Export Test Doc', $record['name']);
        $this->assertEquals($this->client->name, $record['client']);
        $this->assertEquals($this->user->name, $record['author']);
        $this->assertEquals('test, export', $record['tags']);
    }
}