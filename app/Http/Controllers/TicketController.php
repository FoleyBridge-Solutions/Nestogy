<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketWatcher;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Asset;
use App\Models\User;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Requests\StoreTicketReplyRequest;
use App\Services\TicketService;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Services\FileUploadService;
use App\Services\PdfService;

class TicketController extends Controller
{
    protected $ticketService;
    protected $notificationService;
    protected $emailService;
    protected $fileUploadService;
    protected $pdfService;

    public function __construct(
        TicketService $ticketService,
        NotificationService $notificationService,
        EmailService $emailService,
        FileUploadService $fileUploadService,
        PdfService $pdfService
    ) {
        $this->ticketService = $ticketService;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
        $this->fileUploadService = $fileUploadService;
        $this->pdfService = $pdfService;
    }

    /**
     * Display a listing of tickets
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::with(['client', 'contact', 'assignee', 'asset'])
            ->where('company_id', $user->company_id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($tickets);
        }

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new ticket
     */
    public function create(Request $request)
    {
        $clientId = $request->get('client_id');
        $client = $clientId ? Client::findOrFail($clientId) : null;
        
        return view('tickets.create', compact('client'));
    }

    /**
     * Store a newly created ticket
     */
    public function store(StoreTicketRequest $request)
    {
        try {
            $ticketData = $this->ticketService->createTicket($request->validated());
            
            Log::info('Ticket created', [
                'ticket_id' => $ticketData['ticket_id'],
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket created successfully',
                    'ticket' => $ticketData
                ], 201);
            }

            return redirect()
                ->route('tickets.show', $ticketData['ticket_id'])
                ->with('success', "Ticket #{$ticketData['number']} created successfully");

        } catch (\Exception $e) {
            Log::error('Ticket creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create ticket'
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create ticket');
        }
    }

    /**
     * Display the specified ticket
     */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        // Track ticket view for collision detection
        $this->ticketService->addTicketView($ticket->id, Auth::id());

        $ticket->load([
            'client',
            'contact',
            'assignee',
            'asset',
            'replies' => function ($query) {
                $query->with('user')->orderBy('created_at', 'asc');
            }
        ]);

        // Get other viewers (collision detection)
        $otherViewers = $this->ticketService->getTicketViewers($ticket->id, Auth::id());

        if ($request->wantsJson()) {
            return response()->json([
                'ticket' => $ticket,
                'other_viewers' => $otherViewers
            ]);
        }

        return view('tickets.show', compact('ticket', 'otherViewers'));
    }

    /**
     * Show the form for editing the specified ticket
     */
    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        
        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Update the specified ticket
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        try {
            $updatedTicket = $this->ticketService->updateTicket($ticket, $request->validated());
            
            Log::info('Ticket updated', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket updated successfully',
                    'ticket' => $updatedTicket
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket #{$ticket->number} updated successfully");

        } catch (\Exception $e) {
            Log::error('Ticket update failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update ticket'
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update ticket');
        }
    }

    /**
     * Add a reply to the ticket
     */
    public function addReply(StoreTicketReplyRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        try {
            $reply = $this->ticketService->addTicketReply($ticket, $request->validated());
            
            // Send notifications if public reply
            if ($request->get('type') === 'public') {
                $this->emailService->sendTicketUpdateEmail($ticket, $reply);
            }

            Log::info('Ticket reply added', [
                'ticket_id' => $ticket->id,
                'reply_id' => $reply->id,
                'type' => $request->get('type'),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reply added successfully',
                    'reply' => $reply
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Reply added successfully');

        } catch (\Exception $e) {
            Log::error('Ticket reply failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add reply'
                ], 500);
            }

            return back()->with('error', 'Failed to add reply');
        }
    }

    /**
     * Assign ticket to user
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        try {
            $assignedUser = User::findOrFail($request->get('assigned_to'));
            $this->ticketService->assignTicket($ticket, $assignedUser);
            
            // Send notification to assigned user
            $this->notificationService->notifyTicketAssigned($ticket, $assignedUser);

            Log::info('Ticket assigned', [
                'ticket_id' => $ticket->id,
                'assigned_to' => $assignedUser->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ticket assigned to {$assignedUser->name}"
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket assigned to {$assignedUser->name}");

        } catch (\Exception $e) {
            Log::error('Ticket assignment failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to assign ticket');
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'required|in:Open,In Progress,Waiting,Resolved,Closed'
        ]);

        try {
            $oldStatus = $ticket->status;
            $newStatus = $request->get('status');
            
            $this->ticketService->updateTicketStatus($ticket, $newStatus);

            // Send notifications if closing ticket
            if ($newStatus === 'Closed') {
                $this->emailService->sendTicketClosedEmail($ticket);
            }

            Log::info('Ticket status updated', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ticket status updated to {$newStatus}"
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket status updated to {$newStatus}");

        } catch (\Exception $e) {
            Log::error('Ticket status update failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to update ticket status');
        }
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'priority' => 'required|in:Low,Medium,High,Critical'
        ]);

        try {
            $oldPriority = $ticket->priority;
            $newPriority = $request->get('priority');
            
            $this->ticketService->updateTicketPriority($ticket, $newPriority);

            Log::info('Ticket priority updated', [
                'ticket_id' => $ticket->id,
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ticket priority updated to {$newPriority}"
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', "Ticket priority updated to {$newPriority}");

        } catch (\Exception $e) {
            Log::error('Ticket priority update failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to update ticket priority');
        }
    }

    /**
     * Schedule ticket
     */
    public function schedule(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'onsite' => 'boolean'
        ]);

        try {
            $this->ticketService->scheduleTicket($ticket, $request->validated());
            
            // Send scheduling notifications
            $this->emailService->sendTicketScheduledEmail($ticket);

            Log::info('Ticket scheduled', [
                'ticket_id' => $ticket->id,
                'scheduled_at' => $request->get('scheduled_at'),
                'onsite' => $request->get('onsite', false),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket scheduled successfully'
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Ticket scheduled successfully');

        } catch (\Exception $e) {
            Log::error('Ticket scheduling failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to schedule ticket');
        }
    }

    /**
     * Add watcher to ticket
     */
    public function addWatcher(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $this->ticketService->addTicketWatcher($ticket, $request->get('email'));

            Log::info('Ticket watcher added', [
                'ticket_id' => $ticket->id,
                'watcher_email' => $request->get('email'),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Watcher added successfully'
                ]);
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('success', 'Watcher added successfully');

        } catch (\Exception $e) {
            Log::error('Add ticket watcher failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to add watcher');
        }
    }

    /**
     * Merge tickets
     */
    public function merge(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'merge_into_ticket_number' => 'required|integer',
            'merge_comment' => 'nullable|string'
        ]);

        try {
            $targetTicketNumber = $request->get('merge_into_ticket_number');
            $targetTicket = Ticket::where('number', $targetTicketNumber)->firstOrFail();
            
            $this->ticketService->mergeTickets($ticket, $targetTicket, $request->get('merge_comment'));

            Log::info('Tickets merged', [
                'source_ticket_id' => $ticket->id,
                'target_ticket_id' => $targetTicket->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ticket merged into #{$targetTicketNumber}"
                ]);
            }

            return redirect()
                ->route('tickets.show', $targetTicket)
                ->with('success', "Ticket merged into #{$targetTicketNumber}");

        } catch (\Exception $e) {
            Log::error('Ticket merge failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to merge tickets');
        }
    }

    /**
     * Delete the specified ticket
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        try {
            $ticketNumber = $ticket->number;
            $this->ticketService->deleteTicket($ticket);
            
            Log::warning('Ticket deleted', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticketNumber,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket deleted successfully'
                ]);
            }

            return redirect()
                ->route('tickets.index')
                ->with('success', "Ticket #{$ticketNumber} deleted successfully");

        } catch (\Exception $e) {
            Log::error('Ticket deletion failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to delete ticket');
        }
    }

    /**
     * Export tickets to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        $clientId = $request->get('client_id');
        
        $query = Ticket::with('client')
            ->where('company_id', $user->company_id);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $tickets = $query->orderBy('number')->get();
        $filename = 'tickets-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Ticket Number',
                'Priority',
                'Status',
                'Subject',
                'Date Opened',
                'Date Closed'
            ]);

            // CSV data
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->number,
                    $ticket->priority,
                    $ticket->status,
                    $ticket->subject,
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->closed_at ? $ticket->closed_at->format('Y-m-d H:i:s') : ''
                ]);
            }
            
            fclose($file);
        };

        Log::info('Tickets exported to CSV', [
            'count' => $tickets->count(),
            'client_id' => $clientId,
            'user_id' => Auth::id()
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get ticket viewers (for collision detection)
     */
    public function getViewers(Request $request, Ticket $ticket)
    {
        $viewers = $this->ticketService->getTicketViewers($ticket->id, Auth::id());
        
        return response()->json([
            'viewers' => $viewers,
            'message' => $this->formatViewersMessage($viewers)
        ]);
    }

    /**
     * Format viewers message
     */
    private function formatViewersMessage($viewers)
    {
        if (empty($viewers)) {
            return '';
        }

        $names = array_column($viewers, 'name');
        
        if (count($names) === 1) {
            return "<i class='fas fa-fw fa-eye mr-2'></i>{$names[0]} is viewing this ticket.";
        }
        
        return "<i class='fas fa-fw fa-eye mr-2'></i>" . implode(', ', $names) . " are viewing this ticket.";
    }
    /**
     * Upload attachment to ticket
     */
    public function uploadAttachment(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'file' => 'required|file|max:10240' // 10MB max
        ]);

        try {
            $result = $this->fileUploadService->upload(
                file: $request->file('file'),
                collection: 'ticket-attachments'
            );

            if ($result['success']) {
                // Store attachment reference in database if needed
                Log::info('Ticket attachment uploaded', [
                    'ticket_id' => $ticket->id,
                    'file_name' => $result['file']['name'],
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'file' => $result['file']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Ticket attachment upload failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed'
            ], 500);
        }
    }

    /**
     * Generate PDF report for ticket
     */
    public function generatePdf(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        try {
            $ticket->load(['client', 'contact', 'assignee', 'asset', 'replies.user']);
            
            $filename = $this->pdfService->generateFilename('ticket', $ticket->id);
            
            Log::info('Ticket PDF generated', [
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id()
            ]);

            return $this->pdfService->download(
                view: 'pdf.ticket',
                data: ['ticket' => $ticket],
                filename: $filename,
                options: ['template' => 'ticket']
            );

        } catch (\Exception $e) {
            Log::error('Ticket PDF generation failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to generate PDF');
        }
    }
}