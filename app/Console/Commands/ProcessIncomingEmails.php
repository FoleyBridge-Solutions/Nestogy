<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImapService;
use App\Services\TicketService;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class ProcessIncomingEmails extends Command
{
    private const DEFAULT_PAGE_SIZE = 50;

    // Class constants to reduce duplication
    private const STATUS_UNREAD = 'unread';
    private const STATUS_PROCESSED = 'processed';
    private const DEFAULT_BATCH_SIZE = 50;
    private const MSG_EMAIL_START = 'Processing incoming emails...';

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'emails:process-incoming
                            {--account=default : IMAP account to process}
                            {--limit=self::DEFAULT_PAGE_SIZE : Maximum number of emails to process}';

    /**
     * The console command description.
     */
    protected $description = 'Process incoming emails and create tickets';

    protected ImapService $imapService;
    protected TicketService $ticketService;

    public function __construct(ImapService $imapService, TicketService $ticketService)
    {
        parent::__construct();
        $this->imapService = $imapService;
        $this->ticketService = $ticketService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $account = $this->option('account');
        $limit = (int) $this->option('limit');

        $this->info("Processing incoming emails from account: {$account}");

        try {
            // Connect to IMAP
            if (!$this->imapService->connect($account)) {
                $this->error('Failed to connect to IMAP server');
                return Command::FAILURE;
            }

            // Get unread messages
            $messages = $this->imapService->getUnreadMessages('INBOX', $limit);

            if ($messages->isEmpty()) {
                $this->info('No unread messages found');
                $this->imapService->disconnect();
                return Command::SUCCESS;
            }

            $this->info("Found {$messages->count()} unread messages");

            $processed = 0;
            $errors = 0;

            foreach ($messages as $message) {
                try {
                    $this->processMessage($message);
                    $this->imapService->markAsRead($message);
                    $processed++;

                    $this->line("✓ Processed message: {$message->getSubject()}");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Failed to process message: {$message->getSubject()} - {$e->getMessage()}");

                    Log::error('Email processing failed', [
                        'message_id' => $message->getMessageId(),
                        'subject' => $message->getSubject(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->imapService->disconnect();

            $this->info("Processing complete: {$processed} processed, {$errors} errors");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error('Process incoming emails command failed', [
                'error' => $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process individual email message
     */
    protected function processMessage($message): void
    {
        $fromEmail = $message->getFrom()->first()->mail ?? '';
        $fromName = $message->getFrom()->first()->personal ?? '';
        $subject = $message->getSubject();
        $body = $message->getHTMLBody() ?: $message->getTextBody();

        // Find or create contact
        $contact = $this->findOrCreateContact($fromEmail, $fromName);

        // Check if this is a reply to existing ticket
        $existingTicket = $this->findExistingTicket($subject, $fromEmail);

        if ($existingTicket) {
            // Add reply to existing ticket
            $this->addTicketReply($existingTicket, $body, $contact);
        } else {
            // Create new ticket
            $this->createTicketFromEmail($subject, $body, $contact, $message);
        }
    }

    /**
     * Find or create contact from email
     */
    protected function findOrCreateContact(string $email, string $name): ?Contact
    {
        // Try to find existing contact
        $contact = Contact::where('email', $email)->first();

        if ($contact) {
            return $contact;
        }

        // Try to find client by email domain
        $domain = substr(strrchr($email, "@"), 1);
        $client = Client::where('website', 'like', "%{$domain}%")->first();

        if (!$client) {
            // Create a generic client for unknown senders
            $client = Client::create([
                'name' => $name ?: "Client ({$domain})",
                'type' => 'Email Client',
                'company_id' => 1, // Default company - adjust as needed
                'lead' => true
            ]);
        }

        // Create contact
        return Contact::create([
            'name' => $name ?: $email,
            'email' => $email,
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'primary' => true
        ]);
    }

    /**
     * Find existing ticket by subject or reference
     */
    protected function findExistingTicket(string $subject, string $fromEmail): ?Ticket
    {
        // Look for ticket number in subject (e.g., "Re: [Ticket #12345]")
        if (preg_match('/\[Ticket #(\d+)\]/', $subject, $matches)) {
            return Ticket::where('number', $matches[1])->first();
        }

        // Look for recent open tickets from same email
        $contact = Contact::where('email', $fromEmail)->first();
        if ($contact) {
            return Ticket::where('contact_id', $contact->id)
                ->whereIn('status', ['Open', 'In Progress', 'Waiting'])
                ->where('created_at', '>', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return null;
    }

    /**
     * Add reply to existing ticket
     */
    protected function addTicketReply(Ticket $ticket, string $body, ?Contact $contact): void
    {
        $ticket->replies()->create([
            'body' => $body,
            'type' => 'public',
            'user_id' => null, // Email reply
            'contact_id' => $contact?->id,
            'created_at' => now()
        ]);

        // Update ticket status if closed
        if ($ticket->status === 'Closed') {
            $ticket->update(['status' => 'Open']);
        }

        Log::info('Email reply added to ticket', [
            'ticket_id' => $ticket->id,
            'contact_email' => $contact?->email
        ]);
    }

    /**
     * Create new ticket from email
     */
    protected function createTicketFromEmail(string $subject, string $body, ?Contact $contact, $message): void
    {
        $ticketData = [
            'subject' => $subject,
            'details' => $body,
            'priority' => 'Medium',
            'status' => 'Open',
            'client_id' => $contact?->client_id,
            'contact_id' => $contact?->id,
            'company_id' => $contact?->company_id ?? 1,
            'source' => 'Email'
        ];

        $ticket = $this->ticketService->createTicket($ticketData);

        // Process attachments
        $attachments = $this->imapService->getAttachments($message);
        foreach ($attachments as $attachment) {
            try {
                $filename = $attachment->getName();
                $tempPath = sys_get_temp_dir() . '/' . $filename;

                if ($this->imapService->downloadAttachment($attachment, $tempPath)) {
                    // Here you could use FileUploadService to store the attachment
                    // For now, just log it
                    Log::info('Email attachment found', [
                        'ticket_id' => $ticket['ticket_id'],
                        'filename' => $filename
                    ]);

                    // Clean up temp file
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to process email attachment', [
                    'filename' => $attachment->getName(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Ticket created from email', [
            'ticket_id' => $ticket['ticket_id'],
            'subject' => $subject,
            'contact_email' => $contact?->email
        ]);
    }
}
