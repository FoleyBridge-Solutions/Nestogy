<?php

namespace Tests\Feature\ClientPortal;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Mail\Tickets\TicketCommentAdded;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketReplyTest extends TestCase
{
    use RefreshDatabase;

    protected Contact $contact;
    protected Client $client;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
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
        ]);
    }

    // ==========================================
    // 1. AUTHENTICATION & AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function guest_cannot_reply_to_ticket()
    {
        $response = $this->post(route('client.tickets.comment', $this->ticket->id), [
            'comment' => 'Test reply',
        ]);

        $response->assertRedirect(route('client.login'));
    }

    /** @test */
    public function client_cannot_reply_to_other_company_ticket()
    {
        $otherCompany = Client::factory()->create();
        $otherTicket = Ticket::factory()->create([
            'client_id' => $otherCompany->id,
            'company_id' => $otherCompany->company_id,
        ]);

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $otherTicket->id), [
                'comment' => 'Test reply',
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function client_can_reply_to_own_company_ticket()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'This is my reply',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ==========================================
    // 2. VALIDATION TESTS
    // ==========================================

    /** @test */
    public function message_is_required()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => '',
            ]);

        $response->assertSessionHasErrors('comment');
    }

    /** @test */
    public function message_has_minimum_length()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'ab',
            ]);

        $response->assertSessionHasErrors('comment');
    }

    /** @test */
    public function message_has_maximum_length()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => str_repeat('a', 5001),
            ]);

        $response->assertSessionHasErrors('comment');
    }

    /** @test */
    public function attachments_are_optional()
    {
        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply without attachments',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function attachment_file_size_limit()
    {
        Storage::fake('public');

        $largeFile = UploadedFile::fake()->create('document.pdf', 11000); // 11MB

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply with large file',
                'attachments' => [$largeFile],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    /** @test */
    public function attachment_file_type_validation()
    {
        Storage::fake('public');

        $invalidFile = UploadedFile::fake()->create('script.exe', 100);

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply with invalid file',
                'attachments' => [$invalidFile],
            ]);

        $response->assertSessionHasErrors('attachments.0');
    }

    /** @test */
    public function maximum_number_of_attachments()
    {
        Storage::fake('public');

        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->create("file{$i}.pdf", 100);
        }

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply with too many files',
                'attachments' => $files,
            ]);

        $response->assertSessionHasErrors('attachments');
    }

    // ==========================================
    // 3. COMMENT CREATION TESTS
    // ==========================================

    /** @test */
    public function comment_is_created_with_correct_fields()
    {
        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'This is my test reply',
            ]);

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'content' => 'This is my test reply',
            'visibility' => 'public',
            'source' => 'manual',
            'author_type' => 'customer',
            'author_id' => $this->contact->id,
            'company_id' => $this->ticket->company_id,
        ]);
    }

    /** @test */
    public function comment_belongs_to_correct_ticket()
    {
        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Test reply',
            ]);

        $this->assertCount(1, $this->ticket->fresh()->comments);
        $this->assertEquals('Test reply', $this->ticket->fresh()->comments->first()->content);
    }

    /** @test */
    public function ticket_updated_timestamp_changes()
    {
        $originalUpdatedAt = $this->ticket->updated_at;

        sleep(1);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Test reply',
            ]);

        $this->assertTrue($this->ticket->fresh()->updated_at->gt($originalUpdatedAt));
    }

    // ==========================================
    // 4. ATTACHMENT TESTS
    // ==========================================

    /** @test */
    public function single_attachment_is_saved()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply with attachment',
                'attachments' => [$file],
            ]);

        $comment = $this->ticket->fresh()->comments->first();

        $this->assertCount(1, $comment->attachments);
        $this->assertEquals('document.pdf', $comment->attachments->first()->original_filename);
    }

    /** @test */
    public function multiple_attachments_are_saved()
    {
        Storage::fake('public');

        $files = [
            UploadedFile::fake()->create('doc1.pdf', 100),
            UploadedFile::fake()->create('doc2.docx', 200),
            UploadedFile::fake()->image('image.jpg'),
        ];

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply with multiple attachments',
                'attachments' => $files,
            ]);

        $comment = $this->ticket->fresh()->comments->first();

        $this->assertCount(3, $comment->attachments);
    }

    /** @test */
    public function attachment_metadata_is_correct()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 150);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Reply with attachment',
                'attachments' => [$file],
            ]);

        $attachment = $this->ticket->fresh()->comments->first()->attachments->first();

        $this->assertEquals('document.pdf', $attachment->original_filename);
        $this->assertEquals('application/pdf', $attachment->mime_type);
        $this->assertGreaterThan(0, $attachment->size);
        $this->assertEquals($this->contact->id, $attachment->uploaded_by);
    }

    // ==========================================
    // 5. STATUS CHANGE TESTS
    // ==========================================

    /** @test */
    public function awaiting_customer_status_changes_to_open()
    {
        $this->ticket->update(['status' => 'Awaiting Customer']);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Here is my response',
            ]);

        $this->assertEquals('Open', $this->ticket->fresh()->status);
    }

    /** @test */
    public function other_statuses_remain_unchanged()
    {
        $this->ticket->update(['status' => 'In Progress']);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Additional info',
            ]);

        $this->assertEquals('In Progress', $this->ticket->fresh()->status);
    }

    /** @test */
    public function cannot_reply_to_closed_ticket()
    {
        $this->ticket->update(['status' => 'Closed']);

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Trying to reply to closed ticket',
            ]);

        $response->assertSessionHasErrors();
        $this->assertCount(0, $this->ticket->fresh()->comments);
    }

    /** @test */
    public function cannot_reply_to_resolved_ticket()
    {
        $this->ticket->update(['status' => 'Resolved']);

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Trying to reply to resolved ticket',
            ]);

        $response->assertSessionHasErrors();
        $this->assertCount(0, $this->ticket->fresh()->comments);
    }

    // ==========================================
    // 6. NOTIFICATION TESTS
    // ==========================================

    /** @test */
    public function email_sent_to_assigned_technician()
    {
        Mail::fake();

        $technician = User::factory()->create(['company_id' => $this->ticket->company_id]);
        $this->ticket->update(['assigned_to' => $technician->id]);

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Need urgent help',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(TicketCommentAdded::class, function ($mail) use ($technician) {
            return $mail->hasTo($technician->email);
        });
    }

    /** @test */
    public function no_email_if_ticket_unassigned()
    {
        Mail::fake();

        $this->ticket->update(['assigned_to' => null]);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Comment on unassigned ticket',
            ]);

        Mail::assertNothingSent();
    }

    // ==========================================
    // 7. EDGE CASES & SECURITY
    // ==========================================

    /** @test */
    public function xss_protection_in_comment_content()
    {
        $maliciousContent = '<script>alert("XSS")</script>Hello';

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => $maliciousContent,
            ]);

        $comment = $this->ticket->fresh()->comments->first();

        // Content should be stored as-is, but escaped on display
        $this->assertEquals($maliciousContent, $comment->content);
    }

    /** @test */
    public function unicode_characters_in_comment()
    {
        $unicodeMessage = 'Hello 你好 مرحبا שלום 🎉';

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => $unicodeMessage,
            ]);

        $comment = $this->ticket->fresh()->comments->first();
        $this->assertEquals($unicodeMessage, $comment->content);
    }

    /** @test */
    public function concurrent_comments_dont_conflict()
    {
        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'First comment',
            ]);

        $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => 'Second comment',
            ]);

        $this->assertCount(2, $this->ticket->fresh()->comments);
    }

    /** @test */
    public function large_comment_content()
    {
        $largeMessage = str_repeat('Lorem ipsum dolor sit amet. ', 100);

        $response = $this->actingAs($this->contact, 'client')
            ->post(route('client.tickets.comment', $this->ticket->id), [
                'comment' => $largeMessage,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
        ]);
    }
}
