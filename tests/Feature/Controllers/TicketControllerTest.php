<?php

namespace Tests\Feature\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Domains\Ticket\Models\TicketWorkflow;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected User $admin;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->company = Company::factory()->create();

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'status' => true,
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'status' => true,
        ]);
        
        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        \Silber\Bouncer\BouncerFacade::assign('admin')->to($this->user);
        \Silber\Bouncer\BouncerFacade::assign('admin')->to($this->admin);
        \Silber\Bouncer\BouncerFacade::refreshFor($this->user);
        \Silber\Bouncer\BouncerFacade::refreshFor($this->admin);
        
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_index_displays_tickets_list(): void
    {
        $tickets = Ticket::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('tickets.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tickets.index-livewire');
    }

    public function test_index_filters_by_search_term(): void
    {
        $ticket1 = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Server Down',
        ]);

        $ticket2 = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Email Problem',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['search' => 'Server']));

        $response->assertStatus(200);
        $response->assertJsonPath('tickets.data.0.subject', 'Server Down');
    }

    public function test_index_filters_by_status(): void
    {
        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'open',
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'closed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['status' => 'open']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('tickets.total'));
    }

    public function test_index_filters_by_priority(): void
    {
        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'priority' => 'Critical',
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['priority' => 'Critical']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('tickets.total'));
    }

    public function test_index_filters_by_assignee(): void
    {
        $assignee = User::factory()->create(['company_id' => $this->user->company_id]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'assigned_to' => $assignee->id,
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['assigned_to' => $assignee->id]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('tickets.total'));
    }

    public function test_index_filters_overdue_tickets(): void
    {
        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'open',
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['overdue' => true]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('tickets.total'));
    }

    public function test_index_filters_unassigned_tickets(): void
    {
        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'assigned_to' => null,
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'assigned_to' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['unassigned' => true]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('tickets.total'));
    }

    public function test_index_filters_watched_tickets(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        TicketWatcher::factory()->create([
            'company_id' => $this->user->company_id,
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.index', ['watching' => true]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('tickets.total'));
    }

    public function test_create_displays_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('tickets.create'));

        $response->assertStatus(200);
        $response->assertViewIs('tickets.create');
        $response->assertViewHas(['clients', 'assignees', 'priorities', 'statuses']);
    }

    public function test_store_creates_ticket(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'subject' => 'Test Ticket',
            'details' => 'This is a test ticket description',
            'priority' => 'High',
            'status' => 'open',
            'assigned_to' => $this->user->id,
        ];

        $response = $this->actingAs($this->user)->post(route('tickets.store'), $data);

        $response->assertRedirect();
        
        if ($response->exception) {
            throw $response->exception;
        }
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'client_id' => $this->client->id,
            'company_id' => $this->user->company_id,
            'priority' => 'High',
            'status' => 'open',
        ]);
    }

    public function test_store_creates_watcher_for_creator(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'subject' => 'Test Ticket',
            'details' => 'This is a test ticket description',
            'priority' => 'Medium',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('tickets.store'), $data);

        $ticket = Ticket::where('subject', 'Test Ticket')->first();
        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_generates_unique_ticket_number(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'subject' => 'Test Ticket Unique',
            'details' => 'Test details',
            'priority' => 'Medium',
            'status' => 'new',
        ];

        $this->actingAs($this->user)->post(route('tickets.store'), $data);

        $ticket = Ticket::where('subject', 'Test Ticket Unique')
            ->where('company_id', $this->user->company_id)
            ->first();
        $this->assertNotNull($ticket);
        $this->assertNotNull($ticket->number);
        $this->assertIsInt($ticket->number);
        $this->assertGreaterThan(1000, $ticket->number);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('tickets.store'), []);

        $response->assertSessionHasErrors(['client_id', 'subject', 'details', 'priority', 'status']);
    }

    public function test_store_validates_client_belongs_to_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);

        $data = [
            'client_id' => $otherClient->id,
            'subject' => 'Test Ticket',
            'details' => 'Test details',
            'priority' => 'Medium',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('tickets.store'), $data);

        $response->assertSessionHasErrors('client_id');
    }

    public function test_store_validates_priority_values(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'subject' => 'Test Ticket',
            'details' => 'Test details',
            'priority' => 'InvalidPriority',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('tickets.store'), $data);

        $response->assertSessionHasErrors('priority');
    }

    public function test_store_returns_json_when_requested(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'subject' => 'API Test Ticket',
            'details' => 'Test details',
            'priority' => 'High',
            'status' => 'open',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.store'), $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'ticket' => ['id', 'subject', 'client', 'assignee'],
        ]);
    }

    public function test_show_displays_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertViewIs('tickets.show-livewire');
        $response->assertViewHas('ticket');
    }

    public function test_show_tracks_viewer(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $this->actingAs($this->user)->get(route('tickets.show', $ticket));

        $cacheKey = "ticket_viewer_{$ticket->id}_{$this->user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_show_returns_json_when_requested(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ticket',
            'available_transitions',
            'recent_activity',
            'other_viewers',
        ]);
    }

    public function test_edit_displays_form(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('tickets.edit', $ticket));

        $response->assertStatus(200);
        $response->assertViewIs('tickets.edit');
        $response->assertViewHas(['ticket', 'clients', 'assignees']);
    }

    public function test_update_modifies_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Old Subject',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'subject' => 'Updated Subject',
            'details' => 'Updated details',
            'priority' => 'Critical',
            'status' => 'in_progress',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('tickets.update', $ticket), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'Critical',
            'status' => 'in_progress',
        ]);
    }

    public function test_update_validates_input(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('tickets.update', $ticket), []);

        $response->assertSessionHasErrors(['client_id', 'subject', 'details', 'priority', 'status']);
    }

    public function test_destroy_soft_deletes_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_destroy_returns_json_when_requested(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('tickets.destroy', $ticket));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_assign_updates_ticket_assignee(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'assigned_to' => null,
        ]);

        $assignee = User::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.assign', $ticket), [
                'assigned_to' => $assignee->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_assign_creates_watcher_for_assignee(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $assignee = User::factory()->create(['company_id' => $this->user->company_id]);

        $this->actingAs($this->user)->postJson(route('tickets.assign', $ticket), [
            'assigned_to' => $assignee->id,
        ]);

        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_assign_validates_assignee_belongs_to_company(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.assign', $ticket), [
                'assigned_to' => $otherUser->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_update_status_changes_ticket_status(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.update-status', $ticket), [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_update_status_sets_closed_timestamps(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'status' => 'open',
        ]);

        $this->actingAs($this->user)->postJson(route('tickets.update-status', $ticket), [
            'status' => 'closed',
        ]);

        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);
        $this->assertEquals($this->user->id, $ticket->closed_by);
    }

    public function test_update_priority_changes_ticket_priority(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'priority' => 'Low',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.update-priority', $ticket), [
                'priority' => 'Critical',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'Critical',
        ]);
    }

    public function test_schedule_creates_calendar_event(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $scheduledTime = now()->addDay();

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.schedule', $ticket), [
                'scheduled_at' => $scheduledTime->toDateTimeString(),
                'duration' => 120,
                'location' => 'Client Office',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'scheduled_at' => $scheduledTime,
        ]);
    }

    public function test_schedule_validates_future_date(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.schedule', $ticket), [
                'scheduled_at' => now()->subDay()->toDateTimeString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_merge_moves_data_to_target_ticket(): void
    {
        $sourceTicket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Source Ticket',
        ]);

        $targetTicket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Target Ticket',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.merge', $sourceTicket), [
                'merge_into_number' => $targetTicket->number,
            ]);

        $response->assertStatus(200);
        $sourceTicket->refresh();
        $this->assertEquals('closed', $sourceTicket->status);
    }

    public function test_merge_validates_target_ticket_exists(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.merge', $ticket), [
                'merge_into_number' => 999999,
            ]);

        $response->assertStatus(404);
    }

    public function test_merge_prevents_self_merge(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.merge', $ticket), [
                'merge_into_number' => (int) $ticket->number,
            ]);

        $response->assertStatus(422);
    }

    public function test_export_generates_csv(): void
    {
        Ticket::factory()->count(3)->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('tickets.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_search_finds_tickets_by_number(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'number' => 12345,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.search', ['q' => '12345']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tickets');
    }

    public function test_search_finds_tickets_by_subject(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Email server is down',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.search', ['q' => 'Email']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tickets');
    }

    public function test_search_excludes_specified_ticket(): void
    {
        $ticket1 = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Server Issue',
        ]);

        $ticket2 = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
            'subject' => 'Server Problem',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.search', ['q' => 'Server', 'exclude' => $ticket1->id]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('tickets'));
        $this->assertEquals($ticket2->id, $response->json('tickets.0.id'));
    }

    public function test_get_viewers_returns_other_viewers(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $otherUser = User::factory()->create(['company_id' => $this->user->company_id]);

        Cache::put("ticket_viewer_{$ticket->id}_{$otherUser->id}", [
            'user_id' => $otherUser->id,
            'user_name' => $otherUser->name,
            'last_viewed' => now()->toISOString(),
            'session_id' => 'other-session',
        ], now()->addMinutes(5));

        $response = $this->actingAs($this->user)
            ->getJson(route('tickets.viewers', $ticket));

        $response->assertStatus(200);
        $response->assertJsonStructure(['viewers', 'message']);
    }

    public function test_start_smart_timer_creates_time_entry(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.start-timer', $ticket), [
                'work_type' => 'general_support',
                'description' => 'Working on ticket',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_stop_timer_ends_active_timer(): void
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->user->company_id,
            'client_id' => $this->client->id,
        ]);

        $timeEntry = TicketTimeEntry::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'entry_type' => 'timer',
            'started_at' => now()->subHour(),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('tickets.stop-timer', $ticket));

        $response->assertStatus(200);
        $timeEntry->refresh();
        $this->assertNotNull($timeEntry->ended_at);
    }
}
