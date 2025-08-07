<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketWatcher;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    /**
     * Create a new ticket
     */
    public function createTicket(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();
            
            // Create the ticket
            $ticket = Ticket::create([
                'company_id' => Auth::user()->company_id,
                'number' => $ticketNumber,
                'client_id' => $data['client_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'subject' => $data['subject'],
                'details' => $data['details'],
                'priority' => $data['priority'] ?? 'Medium',
                'status' => $data['status'] ?? 'Open',
                'assigned_to' => $data['assigned_to'] ?? null,
                'asset_id' => $data['asset_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'vendor_ticket_number' => $data['vendor_ticket_number'] ?? null,
                'billable' => $data['billable'] ?? true,
                'category' => $data['category'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'onsite' => $data['onsite'] ?? false,
                'created_by' => Auth::id(),
            ]);

            // Add watchers if provided
            if (!empty($data['watchers'])) {
                foreach ($data['watchers'] as $watcherEmail) {
                    if (!empty($watcherEmail)) {
                        TicketWatcher::create([
                            'ticket_id' => $ticket->id,
                            'email' => $watcherEmail
                        ]);
                    }
                }
            }

            // Handle attachments if provided
            if (!empty($data['attachments'])) {
                // Handle file uploads here
            }

            Log::info('Ticket created via service', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->number,
                'client_id' => $ticket->client_id,
                'subject' => $ticket->subject,
                'user_id' => Auth::id()
            ]);

            return [
                'ticket_id' => $ticket->id,
                'number' => $ticket->number,
                'ticket' => $ticket
            ];
        });
    }

    /**
     * Update an existing ticket
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        $ticket->update([
            'subject' => $data['subject'] ?? $ticket->subject,
            'details' => $data['details'] ?? $ticket->details,
            'priority' => $data['priority'] ?? $ticket->priority,
            'status' => $data['status'] ?? $ticket->status,
            'assigned_to' => $data['assigned_to'] ?? $ticket->assigned_to,
            'asset_id' => $data['asset_id'] ?? $ticket->asset_id,
            'vendor_id' => $data['vendor_id'] ?? $ticket->vendor_id,
            'vendor_ticket_number' => $data['vendor_ticket_number'] ?? $ticket->vendor_ticket_number,
            'billable' => $data['billable'] ?? $ticket->billable,
            'category' => $data['category'] ?? $ticket->category,
            'location_id' => $data['location_id'] ?? $ticket->location_id,
            'scheduled_at' => $data['scheduled_at'] ?? $ticket->scheduled_at,
            'onsite' => $data['onsite'] ?? $ticket->onsite,
        ]);

        return $ticket->fresh();
    }

    /**
     * Add a reply to a ticket
     */
    public function addTicketReply(Ticket $ticket, array $data): TicketReply
    {
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'company_id' => Auth::user()->company_id,
            'replied_by' => Auth::id(),
            'reply' => $data['message'] ?? $data['reply'],
            'type' => $data['type'] ?? 'public',
            'time_worked' => $data['time_spent'] ?? $data['time_worked'] ?? null,
        ]);

        // Update ticket status if provided
        if (!empty($data['status'])) {
            $ticket->update(['status' => $data['status']]);
        }

        // Update ticket updated_at
        $ticket->touch();

        return $reply;
    }

    /**
     * Assign ticket to a user
     */
    public function assignTicket(Ticket $ticket, User $user): void
    {
        $ticket->update(['assigned_to' => $user->id]);
    }

    /**
     * Update ticket status
     */
    public function updateTicketStatus(Ticket $ticket, string $status): void
    {
        $ticket->update(['status' => $status]);
        
        if ($status === 'Closed') {
            $ticket->update(['closed_at' => now()]);
        }
    }

    /**
     * Update ticket priority
     */
    public function updateTicketPriority(Ticket $ticket, string $priority): void
    {
        $ticket->update(['priority' => $priority]);
    }

    /**
     * Schedule a ticket
     */
    public function scheduleTicket(Ticket $ticket, array $data): void
    {
        $ticket->update([
            'scheduled_at' => $data['scheduled_at'],
            'onsite' => $data['onsite'] ?? false,
        ]);
    }

    /**
     * Add a watcher to a ticket
     */
    public function addTicketWatcher(Ticket $ticket, string $email): void
    {
        TicketWatcher::firstOrCreate([
            'ticket_id' => $ticket->id,
            'email' => $email
        ]);
    }

    /**
     * Merge tickets
     */
    public function mergeTickets(Ticket $sourceTicket, Ticket $targetTicket, ?string $comment = null): void
    {
        DB::transaction(function () use ($sourceTicket, $targetTicket, $comment) {
            // Add merge comment to target ticket
            if ($comment) {
                $this->addTicketReply($targetTicket, [
                    'message' => "Merged from ticket #{$sourceTicket->number}: {$comment}",
                    'type' => 'internal'
                ]);
            }

            // Move replies from source to target
            TicketReply::where('ticket_id', $sourceTicket->id)
                ->update(['ticket_id' => $targetTicket->id]);

            // Close source ticket with merge note
            $sourceTicket->update([
                'status' => 'Closed',
                'closed_at' => now(),
                'details' => $sourceTicket->details . "\n\n[Merged into ticket #{$targetTicket->number}]"
            ]);
        });
    }

    /**
     * Delete a ticket
     */
    public function deleteTicket(Ticket $ticket): void
    {
        $ticket->delete();
    }

    /**
     * Add ticket view for collision detection
     */
    public function addTicketView(int $ticketId, int $userId): void
    {
        $key = "ticket_view_{$ticketId}_{$userId}";
        Cache::put($key, [
            'user_id' => $userId,
            'viewed_at' => now(),
        ], now()->addMinutes(5));
    }

    /**
     * Get current viewers of a ticket
     */
    public function getTicketViewers(int $ticketId, ?int $excludeUserId = null): array
    {
        $viewers = [];
        $pattern = "ticket_view_{$ticketId}_*";
        
        // This is a simplified version - in production you'd use Redis SCAN
        $keys = Cache::get($pattern, []);
        
        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data && $data['user_id'] !== $excludeUserId) {
                $user = User::find($data['user_id']);
                if ($user) {
                    $viewers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'viewed_at' => $data['viewed_at']
                    ];
                }
            }
        }

        return $viewers;
    }

    /**
     * Generate a unique ticket number
     */
    private function generateTicketNumber(): int
    {
        $lastTicket = Ticket::where('company_id', Auth::user()->company_id)
            ->orderBy('number', 'desc')
            ->first();

        return $lastTicket ? $lastTicket->number + 1 : 1001;
    }

    /**
     * Create ticket from email
     */
    public function createTicketFromEmail(string $subject, string $body, string $fromEmail, ?Client $client = null): Ticket
    {
        // Find or create client if not provided
        if (!$client) {
            $client = Client::where('email', $fromEmail)->first();
            if (!$client) {
                $client = Client::create([
                    'name' => $fromEmail,
                    'email' => $fromEmail,
                    'company_id' => 1, // Default company
                ]);
            }
        }

        $ticketData = $this->createTicket([
            'client_id' => $client->id,
            'subject' => $subject,
            'details' => $body,
            'priority' => 'Medium',
            'status' => 'Open',
        ]);

        return $ticketData['ticket'];
    }

    /**
     * Get tickets for dashboard
     */
    public function getDashboardTickets(int $limit = 10): array
    {
        return [
            'open' => Ticket::where('status', 'Open')->limit($limit)->get(),
            'in_progress' => Ticket::where('status', 'In Progress')->limit($limit)->get(),
            'closed' => Ticket::where('status', 'Closed')->limit($limit)->get(),
        ];
    }

    /**
     * Get ticket statistics
     */
    public function getTicketStats(): array
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'Open')->count(),
            'in_progress' => Ticket::where('status', 'In Progress')->count(),
            'closed' => Ticket::where('status', 'Closed')->count(),
            'high_priority' => Ticket::where('priority', 'High')->where('status', '!=', 'Closed')->count(),
        ];
    }

    /**
     * Search tickets
     */
    public function searchTickets(string $query, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $ticketsQuery = Ticket::query()
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                  ->orWhere('details', 'like', "%{$query}%");
            });

        if (isset($filters['status'])) {
            $ticketsQuery->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $ticketsQuery->where('priority', $filters['priority']);
        }

        if (isset($filters['client_id'])) {
            $ticketsQuery->where('client_id', $filters['client_id']);
        }

        return $ticketsQuery->with(['client'])->get();
    }

    /**
     * Close ticket
     */
    public function closeTicket(Ticket $ticket, string $reason = ''): bool
    {
        $ticket->update([
            'status' => 'Closed',
            'closed_at' => now(),
        ]);

        if ($reason) {
            $this->addTicketReply($ticket, [
                'message' => "Ticket closed: {$reason}",
                'type' => 'internal'
            ]);
        }

        Log::info('Ticket closed', [
            'ticket_id' => $ticket->id,
            'reason' => $reason,
            'closed_by' => Auth::id()
        ]);

        return true;
    }
}