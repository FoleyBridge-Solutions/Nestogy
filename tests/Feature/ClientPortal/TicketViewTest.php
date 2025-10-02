<?php

namespace Tests\Feature\ClientPortal;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketViewTest extends TestCase
{
    use RefreshDatabase;

    protected Contact $contact;
    protected Client $client;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->create();
        $this->contact = Contact::factory()->for($this->client)->create([
            'email' => 'test@client.com',
            'primary' => true,
            'company_id' => $this->client->company_id,
        ]);
        $this->ticket = Ticket::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->client->company_id,
            'status' => 'Open',
            'subject' => 'Test Ticket',
            'details' => 'This is a test ticket',
        ]);
    }

    /** @test */
    public function guest_cannot_view_ticket()
    {
        $response = $this->get(route('client.tickets.show', $this->ticket->id));

        $response->assertRedirect(route('client.login'));
    }

    /** @test */
    public function contact_can_view_own_ticket()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('Test Ticket');
        $response->assertSee('This is a test ticket');
    }

    /** @test */
    public function contact_cannot_view_other_clients_ticket()
    {
        $otherClient = Client::factory()->create();
        $otherTicket = Ticket::factory()->create([
            'client_id' => $otherClient->id,
            'company_id' => $otherClient->company_id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $otherTicket->id));

        $response->assertNotFound();
    }

    /** @test */
    public function ticket_displays_public_comments()
    {
        // Create a public comment from customer
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'Customer comment here',
            'visibility' => 'public',
            'author_type' => 'customer',
            'author_id' => $this->contact->id,
        ]);

        // Create a public comment from staff
        $user = User::factory()->create(['company_id' => $this->ticket->company_id]);
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'Staff response here',
            'visibility' => 'public',
            'author_type' => 'user',
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('Customer comment here');
        $response->assertSee('Staff response here');
    }

    /** @test */
    public function ticket_does_not_display_internal_comments()
    {
        // Create an internal comment (staff only)
        $user = User::factory()->create(['company_id' => $this->ticket->company_id]);
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'Internal staff note',
            'visibility' => 'internal',
            'author_type' => 'user',
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertDontSee('Internal staff note');
    }

    /** @test */
    public function ticket_displays_no_replies_message_when_empty()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('No replies yet');
    }

    /** @test */
    public function ticket_comments_are_ordered_chronologically()
    {
        // Create comments with specific timestamps
        $comment1 = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'First comment',
            'visibility' => 'public',
            'author_type' => 'customer',
            'author_id' => $this->contact->id,
            'created_at' => now()->subHours(2),
        ]);

        $comment2 = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'Second comment',
            'visibility' => 'public',
            'author_type' => 'customer',
            'author_id' => $this->contact->id,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        
        // Check that "First comment" appears before "Second comment" in the HTML
        $content = $response->getContent();
        $firstPos = strpos($content, 'First comment');
        $secondPos = strpos($content, 'Second comment');
        
        $this->assertNotFalse($firstPos, 'First comment not found');
        $this->assertNotFalse($secondPos, 'Second comment not found');
        $this->assertLessThan($secondPos, $firstPos, 'Comments are not in chronological order');
    }

    /** @test */
    public function ticket_displays_staff_badge_for_staff_comments()
    {
        $user = User::factory()->create(['company_id' => $this->ticket->company_id]);
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'Staff response',
            'visibility' => 'public',
            'author_type' => 'user',
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('Staff');
        $response->assertSee('Staff response');
    }

    /** @test */
    public function ticket_shows_correct_author_name_for_customer_comments()
    {
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'My comment',
            'visibility' => 'public',
            'author_type' => 'customer',
            'author_id' => $this->contact->id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee($this->contact->name);
        $response->assertSee('My comment');
    }

    /** @test */
    public function ticket_shows_correct_author_name_for_staff_comments()
    {
        $user = User::factory()->create([
            'company_id' => $this->ticket->company_id,
            'name' => 'John Support Agent',
        ]);
        
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'company_id' => $this->ticket->company_id,
            'content' => 'Staff response',
            'visibility' => 'public',
            'author_type' => 'user',
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('John Support Agent');
        $response->assertSee('Staff response');
    }

    /** @test */
    public function closed_ticket_does_not_show_reply_form()
    {
        $this->ticket->update(['status' => 'Closed']);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('closed and cannot accept new replies');
        $response->assertDontSee('Add Reply');
    }

    /** @test */
    public function resolved_ticket_does_not_show_reply_form()
    {
        $this->ticket->update(['status' => 'Resolved']);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('resolved and cannot accept new replies');
        $response->assertDontSee('Add Reply');
    }

    /** @test */
    public function open_ticket_shows_reply_form()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('Add Reply');
        $response->assertSee('Your Message');
        $response->assertSee('Send Reply');
    }

    /** @test */
    public function ticket_shows_assigned_technician_when_assigned()
    {
        $user = User::factory()->create([
            'company_id' => $this->ticket->company_id,
            'name' => 'Tech Support',
        ]);
        $this->ticket->update(['assigned_to' => $user->id]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('Assigned To');
        $response->assertSee('Tech Support');
    }

    /** @test */
    public function ticket_shows_unassigned_when_not_assigned()
    {
        $this->ticket->update(['assigned_to' => null]);

        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('Unassigned');
    }

    /** @test */
    public function newly_added_comment_appears_in_conversation()
    {
        // First, view the ticket - should have no comments
        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));
        $response->assertSee('No replies yet');

        // Add a comment
        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'My new comment',
            ]);

        // View the ticket again - comment should be visible
        $response = $this->actingAs($this->contact, 'client')
            ->get(route('client.tickets.show', $this->ticket->id));

        $response->assertOk();
        $response->assertSee('My new comment');
        $response->assertDontSee('No replies yet');
    }
}
