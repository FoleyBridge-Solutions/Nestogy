<?php

namespace Tests\Feature\ClientPortal;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test that ticket comments display correctly in the client portal
 * This was a bug where comments were being created but not showing up
 */
class TicketCommentDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_comment_displays_after_being_added()
    {
        // Setup
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'email' => 'test@example.com',
            'primary' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'subject' => 'Test Ticket',
            'status' => 'Open',
        ]);

        // Act: Add a comment
        $response = $this->actingAs($contact, 'client')
            ->post(route('client.tickets.comment', $ticket->id), [
                'comment' => 'This is my test comment',
            ]);

        // Assert: Comment was created
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'content' => 'This is my test comment',
            'visibility' => 'public',
            'author_type' => 'customer',
        ]);

        // Act: View the ticket
        $viewResponse = $this->actingAs($contact, 'client')
            ->get(route('client.tickets.show', $ticket->id));

        // Assert: Comment is visible in the view
        $viewResponse->assertOk();
        $viewResponse->assertSee('This is my test comment');
        $viewResponse->assertDontSee('No replies yet');
    }

    /** @test */
    public function multiple_comments_display_in_chronological_order()
    {
        // Setup
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'primary' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'status' => 'Open',
        ]);

        // Act: Add multiple comments
        $this->actingAs($contact, 'client')
            ->post(route('client.tickets.comment', $ticket->id), [
                'comment' => 'First comment',
            ]);

        sleep(1); // Ensure different timestamps

        $this->actingAs($contact, 'client')
            ->post(route('client.tickets.comment', $ticket->id), [
                'comment' => 'Second comment',
            ]);

        // Act: View the ticket
        $response = $this->actingAs($contact, 'client')
            ->get(route('client.tickets.show', $ticket->id));

        // Assert: Both comments are visible
        $response->assertOk();
        $response->assertSee('First comment');
        $response->assertSee('Second comment');
        
        // Assert: First comment appears before second (chronological order)
        $content = $response->getContent();
        $firstPos = strpos($content, 'First comment');
        $secondPos = strpos($content, 'Second comment');
        $this->assertLessThan($secondPos, $firstPos);
    }

    /** @test */
    public function customer_name_displays_correctly_with_comment()
    {
        // Setup
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'name' => 'John Customer',
            'primary' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'status' => 'Open',
        ]);

        // Act: Add comment
        $this->actingAs($contact, 'client')
            ->post(route('client.tickets.comment', $ticket->id), [
                'comment' => 'My question here',
            ]);

        // Act: View ticket
        $response = $this->actingAs($contact, 'client')
            ->get(route('client.tickets.show', $ticket->id));

        // Assert: Customer name shows with comment
        $response->assertOk();
        $response->assertSee('John Customer');
        $response->assertSee('My question here');
    }

    /** @test */
    public function internal_comments_do_not_display_to_customer()
    {
        // Setup
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'primary' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'status' => 'Open',
        ]);

        // Create internal staff note
        TicketComment::create([
            'ticket_id' => $ticket->id,
            'company_id' => $company->id,
            'content' => 'Internal note - customer should not see this',
            'visibility' => 'internal',
            'author_type' => 'system',
            'author_id' => null,
        ]);

        // Create public comment
        TicketComment::create([
            'ticket_id' => $ticket->id,
            'company_id' => $company->id,
            'content' => 'Public message for customer',
            'visibility' => 'public',
            'author_type' => 'system',
            'author_id' => null,
        ]);

        // Act: View ticket
        $response = $this->actingAs($contact, 'client')
            ->get(route('client.tickets.show', $ticket->id));

        // Assert: Public comment visible, internal not visible
        $response->assertOk();
        $response->assertSee('Public message for customer');
        $response->assertDontSee('Internal note - customer should not see this');
    }

    /** @test */
    public function ticket_with_no_comments_shows_appropriate_message()
    {
        // Setup
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $contact = Contact::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'primary' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'status' => 'Open',
        ]);

        // Act: View ticket with no comments
        $response = $this->actingAs($contact, 'client')
            ->get(route('client.tickets.show', $ticket->id));

        // Assert: Shows "no replies" message
        $response->assertOk();
        $response->assertSee('No replies yet');
    }
}
