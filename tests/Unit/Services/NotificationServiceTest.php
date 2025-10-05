<?php

namespace Tests\Unit\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use App\Models\Company;
use App\Models\InAppNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;
    protected User $user;
    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        Route::get('/tickets/{ticket}', fn() => 'test')->name('tickets.show');
        
        $this->service = new NotificationService();
        
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_notify_ticket_created_sends_in_app_notification()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-001',
            'subject' => 'Test Ticket',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'ticket_created',
            'title' => 'New Ticket Created',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_notify_ticket_created_respects_preferences()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_created' => false]);

        $this->service->notifyTicketCreated($ticket);

        $this->assertDatabaseMissing('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'ticket_created',
        ]);
    }

    public function test_notify_ticket_created_includes_ticket_number_and_subject_in_message()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 12345,
            'subject' => 'Critical Issue',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $notification = InAppNotification::where('user_id', $this->user->id)->first();
        $this->assertStringContainsString('#12345', $notification->message);
        $this->assertStringContainsString('Critical Issue', $notification->message);
    }

    public function test_notify_ticket_assigned_sends_notification_to_assignee()
    {
        $assignee = User::factory()->create(['company_id' => $this->company->id]);
        
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $assignee->id,
            'number' => 'TKT-002',
            'subject' => 'Assigned Ticket',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($assignee);
        $prefs->update(['ticket_assigned' => true]);

        $this->service->notifyTicketAssigned($ticket, $assignee);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $assignee->id,
            'type' => 'ticket_assigned',
            'title' => 'Ticket Assigned to You',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_notify_ticket_assigned_respects_preferences()
    {
        $assignee = User::factory()->create(['company_id' => $this->company->id]);
        
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $assignee->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($assignee);
        $prefs->update(['ticket_assigned' => false]);

        $this->service->notifyTicketAssigned($ticket, $assignee);

        $this->assertDatabaseMissing('in_app_notifications', [
            'user_id' => $assignee->id,
            'type' => 'ticket_assigned',
        ]);
    }

    public function test_notify_ticket_status_changed_sends_notification()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-003',
            'status' => 'In Progress',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_status_changed' => true]);

        $this->service->notifyTicketStatusChanged($ticket, 'Open', 'In Progress');

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'ticket_status_changed',
            'title' => 'Ticket Status Updated',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_notify_ticket_status_changed_includes_old_and_new_status()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-004',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_status_changed' => true]);

        $this->service->notifyTicketStatusChanged($ticket, 'Open', 'Resolved');

        $notification = InAppNotification::where('user_id', $this->user->id)->first();
        $this->assertStringContainsString('Open', $notification->message);
        $this->assertStringContainsString('Resolved', $notification->message);
    }

    public function test_notify_ticket_resolved_sends_notification()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-005',
            'subject' => 'Resolved Ticket',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_resolved' => true]);

        $this->service->notifyTicketResolved($ticket);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'ticket_resolved',
            'title' => 'Ticket Resolved',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_notify_ticket_comment_added_sends_notification_to_watchers()
    {
        $commenter = User::factory()->create(['company_id' => $this->company->id]);
        
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-006',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_comment_added' => true]);

        $comment = (object)['author_id' => $commenter->id];

        $this->service->notifyTicketCommentAdded($ticket, $comment);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'ticket_comment_added',
            'title' => 'New Comment Added',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_notify_ticket_comment_added_does_not_notify_comment_author()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_comment_added' => true]);

        $comment = (object)['author_id' => $this->user->id];

        $this->service->notifyTicketCommentAdded($ticket, $comment);

        $this->assertDatabaseMissing('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'ticket_comment_added',
        ]);
    }

    public function test_notify_sla_breach_warning_sends_notification()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-007',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['sla_breach_warning' => true]);

        $this->service->notifySLABreachWarning($ticket, 2);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'sla_breach_warning',
            'title' => 'SLA Breach Warning',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_notify_sla_breach_warning_includes_hours_remaining()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-008',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['sla_breach_warning' => true]);

        $this->service->notifySLABreachWarning($ticket, 3);

        $notification = InAppNotification::where('user_id', $this->user->id)->first();
        $this->assertStringContainsString('3h remaining', $notification->message);
    }

    public function test_notify_sla_breached_sends_critical_notification()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-009',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['sla_breached' => true]);

        $this->service->notifySLABreached($ticket, 5);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
            'type' => 'sla_breached',
            'title' => 'SLA BREACHED - Critical',
            'ticket_id' => $ticket->id,
            'color' => 'red',
        ]);
    }

    public function test_notify_sla_breached_includes_hours_overdue()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'number' => 'TKT-010',
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['sla_breached' => true]);

        $this->service->notifySLABreached($ticket, 4);

        $notification = InAppNotification::where('user_id', $this->user->id)->first();
        $this->assertStringContainsString('4h overdue', $notification->message);
    }

    public function test_get_recipients_includes_ticket_creator()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_get_recipients_includes_assigned_user()
    {
        $assignee = User::factory()->create(['company_id' => $this->company->id]);
        
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'assigned_to' => $assignee->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($assignee);
        $prefs->update(['ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $assignee->id,
        ]);
    }

    public function test_notification_link_points_to_ticket_show_route()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $notification = InAppNotification::where('user_id', $this->user->id)->first();
        $expectedUrl = route('tickets.show', $ticket->id);
        $this->assertEquals($expectedUrl, $notification->link);
    }

    public function test_notifications_have_appropriate_icons()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update([
            'ticket_created' => true,
            'sla_breached' => true,
        ]);

        $this->service->notifyTicketCreated($ticket);
        $createdNotif = InAppNotification::where('type', 'ticket_created')->first();
        $this->assertEquals('fas fa-ticket-alt', $createdNotif->icon);

        $this->service->notifySLABreached($ticket, 1);
        $breachedNotif = InAppNotification::where('type', 'sla_breached')->first();
        $this->assertEquals('fas fa-exclamation-circle', $breachedNotif->icon);
    }

    public function test_notifications_have_appropriate_colors()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $assignee = User::factory()->create(['company_id' => $this->company->id]);

        $prefs = NotificationPreference::getOrCreateForUser($assignee);
        $prefs->update(['ticket_assigned' => true]);

        $this->service->notifyTicketAssigned($ticket, $assignee);
        $assignedNotif = InAppNotification::where('type', 'ticket_assigned')->first();
        $this->assertEquals('green', $assignedNotif->color);
    }

    public function test_multiple_users_can_receive_same_notification()
    {
        $user2 = User::factory()->create(['company_id' => $this->company->id]);
        
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'assigned_to' => $user2->id,
        ]);

        NotificationPreference::getOrCreateForUser($this->user)->update(['in_app_ticket_created' => true]);
        NotificationPreference::getOrCreateForUser($user2)->update(['in_app_ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $this->assertDatabaseHas('in_app_notifications', ['user_id' => $this->user->id, 'type' => 'ticket_created']);
        $this->assertDatabaseHas('in_app_notifications', ['user_id' => $user2->id, 'type' => 'ticket_created']);
    }

    public function test_ticket_id_is_stored_in_notifications()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ]);

        $prefs = NotificationPreference::getOrCreateForUser($this->user);
        $prefs->update(['ticket_created' => true]);

        $this->service->notifyTicketCreated($ticket);

        $notification = InAppNotification::where('user_id', $this->user->id)->first();
        $this->assertEquals($ticket->id, $notification->ticket_id);
    }
}
