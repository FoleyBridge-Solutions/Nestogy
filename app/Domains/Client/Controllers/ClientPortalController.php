<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractMilestone;
use App\Domains\Contract\Models\ContractSignature;
use App\Domains\Security\Services\DigitalSignatureService;
use App\Http\Controllers\Controller;
use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Contact;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ClientPortalController
 *
 * Handles client-facing portal functionality for contract management,
 * digital signing, milestone tracking, and payment management.
 */
class ClientPortalController extends Controller
{
    private const ERROR_NO_PERMISSION_VIEW_QUOTES = 'You do not have permission to view quotes.';

    protected DigitalSignatureService $signatureService;

    public function __construct(DigitalSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
        $this->middleware('guest:client')->only(['showLogin', 'login']);
        $this->middleware('auth:client')->except(['showLogin', 'login']);
    }

    /**
     * Show client login form
     */
    public function showLogin()
    {
        return view('client-portal.auth.login');
    }

    /**
     * Handle client login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        // Find contact with portal access
        $contact = Contact::findByEmail($request->email);

        if (! $contact) {
            return back()->withErrors([
                'email' => 'No account found with this email address.',
            ])->onlyInput('email');
        }

        // Check if account is locked
        if ($contact->isLocked()) {
            return back()->withErrors([
                'email' => 'Your account is temporarily locked due to multiple failed login attempts. Please try again later.',
            ])->onlyInput('email');
        }

        // Verify password
        if (! $contact->verifyPassword($request->password)) {
            // Increment failed login attempts
            $contact->incrementFailedLoginAttempts();

            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->onlyInput('email');
        }

        // Check if contact can access portal
        if (! $contact->canAccessPortal()) {
            // Debug why portal access is denied
            $debugInfo = [
                'has_portal_access' => $contact->has_portal_access,
                'is_locked' => $contact->isLocked(),
                'client_exists' => $contact->client ? true : false,
                'client_is_active' => $contact->client ? $contact->client->is_active : 'no_client',
                'contact_id' => $contact->id,
                'client_id' => $contact->client_id,
            ];

            \Log::error('Portal access denied for contact', $debugInfo);

            $errorMessage = 'Your account does not have portal access enabled. Please contact your administrator.';

            // Add specific error details for debugging
            if (! $contact->has_portal_access) {
                $errorMessage = 'Portal access is not enabled for your account.';
            } elseif ($contact->isLocked()) {
                $errorMessage = 'Your account is temporarily locked.';
            } elseif (! $contact->client) {
                $errorMessage = 'No client association found for your account.';
            } elseif (! $contact->client->is_active) {
                $errorMessage = 'Your client account is not active.';
            }

            return back()->withErrors([
                'email' => $errorMessage,
            ])->onlyInput('email');
        }

        // Update login info and authenticate
        $contact->updateLoginInfo($request->ip());
        auth('client')->login($contact, $request->boolean('remember', false));

        // Clear any intended URL to prevent redirect to wrong dashboard
        session()->forget('url.intended');

        return redirect()->route('client.dashboard');
    }

    /**
     * Client dashboard - role-based content
     */
    public function dashboard()
    {
        return view('client-portal.dashboard');
    }

    /**
     * List client contracts
     */
    public function contracts(Request $request)
    {
        $contact = auth('client')->user();

        // Check if contact can view contracts
        if (! $this->canViewContracts($contact)) {
            abort(403, 'You do not have permission to view contracts.');
        }

        $client = $contact->client;

        $query = Contract::where('client_id', $client->id)
            ->with(['signatures', 'invoices']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('contract_type', $request->type);
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(10);
        $notifications = $this->getNotificationsForContact($contact);

        return view('client-portal.contracts.index', compact('contracts', 'contact', 'notifications'));
    }

    /**
     * View specific contract
     */
    public function viewContract(Contract $contract)
    {
        $this->authorizeClientAccess($contract);

        $contract->load([
            'signatures',
            'invoices' => function ($query) {
                $query->with('payments')->orderBy('created_at', 'desc');
            },
            'approvals' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);

        $pendingSignatures = $contract->signatures->where('status', 'pending');
        $outstandingInvoices = $contract->invoices->where('status', 'Sent');

        return view('client-portal.contracts.show', compact(
            'contract',
            'pendingSignatures',
            'outstandingInvoices'
        ));
    }

    /**
     * Sign contract
     */
    public function signContract(Request $request, Contract $contract)
    {
        $this->authorizeClientAccess($contract);

        $request->validate([
            'signature_type' => 'required|in:electronic,digital',
            'signature_method' => 'required|in:draw,type,upload',
            'signature_data' => 'required|string',
            'terms_accepted' => 'required|accepted',
        ]);

        try {
            // Find pending signature for this client
            $signature = ContractSignature::where('contract_id', $contract->id)
                ->where('signer_email', auth('client')->user()->email)
                ->where('status', 'pending')
                ->first();

            if (! $signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending signature found for this contract.',
                ], 400);
            }

            // Process the signature
            $signatureData = [
                'signature_type' => $request->signature_type,
                'signature_method' => $request->signature_method,
                'signature_data' => $request->signature_data,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'signed_at' => now(),
            ];

            $this->signatureService->processClientSignature($signature, $signatureData);

            // Log activity
            $contract->auditLogs()->create([
                'user_type' => 'client',
                'user_id' => auth('client')->id(),
                'action' => 'contract_signed',
                'description' => 'Contract signed by client',
                'changes' => $signatureData,
                'company_id' => $contract->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract signed successfully!',
            ]);

        } catch (\Exception $e) {
            Log::error('Error signing contract', [
                'contract_id' => $contract->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while signing the contract.',
            ], 500);
        }
    }

    /**
     * Download contract PDF
     */
    public function downloadContract(Contract $contract)
    {
        $this->authorizeClientAccess($contract);

        try {
            $pdf = $contract->generatePdf();

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="contract-'.$contract->contract_number.'.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating contract PDF for client', [
                'contract_id' => $contract->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to download contract PDF.');
        }
    }

    /**
     * View milestone details
     */
    public function viewMilestone(Contract $contract, ContractMilestone $milestone)
    {
        $this->authorizeClientAccess($contract);

        if ($milestone->contract_id !== $contract->id) {
            abort(404);
        }

        $milestone->load(['attachments']);

        return view('client-portal.milestones.show', compact('contract', 'milestone'));
    }

    /**
     * Update milestone progress (client input)
     */
    public function updateMilestoneProgress(Request $request, Contract $contract, ContractMilestone $milestone)
    {
        $this->authorizeClientAccess($contract);

        if ($milestone->contract_id !== $contract->id) {
            abort(404);
        }

        $request->validate([
            'client_notes' => 'nullable|string|max:1000',
            'attachments.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,txt',
        ]);

        try {
            $milestone->update([
                'client_notes' => $request->client_notes,
                'updated_at' => now(),
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('milestone-attachments', 'public');

                    $milestone->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => 'client',
                        'uploaded_by_id' => auth('client')->id(),
                    ]);
                }
            }

            // Log activity
            $contract->auditLogs()->create([
                'user_type' => 'client',
                'user_id' => auth('client')->id(),
                'action' => 'milestone_updated',
                'description' => 'Milestone progress updated by client',
                'changes' => [
                    'milestone_id' => $milestone->id,
                    'client_notes' => $request->client_notes,
                ],
                'company_id' => $contract->company_id,
            ]);

            return redirect()->back()->with('success', 'Milestone progress updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating milestone progress', [
                'milestone_id' => $milestone->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to update milestone progress.');
        }
    }

    /**
     * View invoices for contract
     */
    public function contractInvoices(Contract $contract)
    {
        $this->authorizeClientAccess($contract);

        $invoices = $contract->invoices()
            ->with(['payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client-portal.invoices.index', compact('contract', 'invoices'));
    }

    /**
     * View specific invoice
     */
    public function viewInvoice(Contract $contract, Invoice $invoice)
    {
        $this->authorizeClientAccess($contract);

        if ($invoice->contract_id !== $contract->id) {
            abort(404);
        }

        $invoice->load(['items', 'payments']);

        return view('client-portal.invoices.show', compact('contract', 'invoice'));
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(Contract $contract, Invoice $invoice)
    {
        $this->authorizeClientAccess($contract);

        if ($invoice->contract_id !== $contract->id) {
            abort(404);
        }

        try {
            $pdf = $invoice->generatePdf();

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->invoice_number.'.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating invoice PDF for client', [
                'invoice_id' => $invoice->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Client profile/settings
     */
    public function profile()
    {
        $contact = auth('client')->user();
        $client = $contact->client;

        return view('client-portal.profile', compact('contact', 'client'));
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request)
    {
        $contact = auth('client')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'notification_preferences' => 'nullable|array',
        ]);

        try {
            $contact->update($request->only([
                'name', 'phone', 'mobile', 'title', 'department',
            ]));

            if ($request->has('notification_preferences')) {
                $contact->update([
                    'portal_permissions' => array_merge(
                        $contact->portal_permissions ?? [],
                        ['notification_preferences' => $request->notification_preferences]
                    ),
                ]);
            }

            return redirect()->back()->with('success', 'Profile updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating contact profile', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to update profile.');
        }
    }

    /**
     * Client logout
     */
    public function logout(Request $request)
    {
        auth('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }

    /**
     * Helper methods
     */
    protected function authorizeClientAccess(Contract $contract)
    {
        $contact = auth('client')->user();

        // Check client association
        if ($contract->client_id !== $contact->client_id) {
            abort(403, 'Unauthorized access to contract.');
        }

        // Check contract viewing permissions
        if (! $this->canViewContracts($contact)) {
            abort(403, 'You do not have permission to view contracts.');
        }
    }

    protected function getRecentActivity(int $clientId): array
    {
        // Get recent activity across all client contracts
        return Contract::where('client_id', $clientId)
            ->with('auditLogs')
            ->get()
            ->flatMap->auditLogs
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->toArray();
    }

    protected function getUpcomingMilestones(int $clientId): array
    {
        return ContractMilestone::whereHas('contract', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getPendingActions(int $clientId): array
    {
        $actions = [];

        // Pending signatures
        $pendingSignatures = ContractSignature::whereHas('contract', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })
            ->where('status', 'pending')
            ->count();

        if ($pendingSignatures > 0) {
            $actions[] = [
                'type' => 'signature',
                'count' => $pendingSignatures,
                'message' => "You have {$pendingSignatures} contract(s) pending signature.",
                'action_url' => route('client.contracts'),
            ];
        }

        // Overdue milestones requiring input
        $overdueMilestones = ContractMilestone::whereHas('contract', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueMilestones > 0) {
            $actions[] = [
                'type' => 'milestone',
                'count' => $overdueMilestones,
                'message' => "You have {$overdueMilestones} overdue milestone(s) requiring attention.",
                'action_url' => route('client.contracts'),
            ];
        }

        return $actions;
    }

    /**
     * Role and Permission Checking Methods
     */
    protected function canViewContracts(Contact $contact): bool
    {
        // Primary contacts can always view contracts
        // Others need explicit permission
        return $contact->isPrimary() ||
               in_array('can_view_contracts', $contact->portal_permissions ?? []);
    }

    protected function canViewInvoices(Contact $contact): bool
    {
        // Billing contacts and primary can view invoices
        return $contact->isBilling() ||
               $contact->isPrimary() ||
               in_array('can_view_invoices', $contact->portal_permissions ?? []);
    }

    protected function canViewTickets(Contact $contact): bool
    {
        // Technical contacts can view tickets, others need permission
        return $contact->isTechnical() ||
               $contact->isPrimary() ||
               in_array('can_view_tickets', $contact->portal_permissions ?? []);
    }

    protected function canCreateTickets(Contact $contact): bool
    {
        return $contact->isTechnical() ||
               $contact->isPrimary() ||
               in_array('can_create_tickets', $contact->portal_permissions ?? []);
    }

    protected function canViewAssets(Contact $contact): bool
    {
        return $contact->isTechnical() ||
               $contact->isPrimary() ||
               in_array('can_view_assets', $contact->portal_permissions ?? []);
    }

    protected function canViewProjects(Contact $contact): bool
    {
        return $contact->isPrimary() ||
               in_array('can_view_projects', $contact->portal_permissions ?? []);
    }

    protected function canViewReports(Contact $contact): bool
    {
        return $contact->isPrimary() ||
               in_array('can_view_reports', $contact->portal_permissions ?? []);
    }

    protected function canApproveQuotes(Contact $contact): bool
    {
        return $contact->isPrimary() ||
               $contact->isBilling() ||
               in_array('can_approve_quotes', $contact->portal_permissions ?? []);
    }

    /**
     * Contact-specific Data Methods
     */
    protected function getInvoicesForContact(Contact $contact)
    {
        if (! $contact->client) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return $contact->client->invoices()
            ->with(['payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    protected function getInvoiceStatsForContact(Contact $contact): array
    {
        if (! $contact->client) {
            return [];
        }

        $invoices = $contact->client->invoices();

        return [
            'total_invoices' => $invoices->count(),
            'total_outstanding' => $invoices->where('status', 'sent')->sum('amount'),
            'overdue_amount' => $invoices->where('due_date', '<', now())->where('status', 'sent')->sum('amount'),
            'overdue_count' => $invoices->where('due_date', '<', now())->where('status', 'sent')->count(),
            'paid_this_month' => $invoices->where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->sum('amount'),
        ];
    }

    protected function getTicketsForContact(Contact $contact)
    {
        if (! $contact->client) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return $contact->client->tickets()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    protected function getTicketStatsForContact(Contact $contact): array
    {
        if (! $contact->client) {
            return [];
        }

        $tickets = $contact->client->tickets();

        return [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->whereIn('status', ['Open', 'In Progress', 'Waiting', 'On Hold'])->count(),
            'resolved_this_month' => $tickets->whereIn('status', ['Resolved', 'Closed'])
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'avg_response_time' => '< 1h', // Placeholder - calculate from ticket history
        ];
    }

    protected function getAssetsForContact(Contact $contact)
    {
        if (! $contact->client) {
            return collect();
        }

        return $contact->client->assets()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getAssetStatsForContact(Contact $contact): array
    {
        if (! $contact->client) {
            return [];
        }

        $assets = $contact->client->assets();

        return [
            'total_assets' => $assets->count(),
            'active_assets' => $assets->where('status', 'active')->count(),
            'maintenance_due' => $assets->where('next_maintenance_date', '<=', now()->addDays(30))->count(),
            'warranty_expiring' => $assets->where('warranty_expire', '<=', now()->addDays(60))->count(),
        ];
    }

    protected function getProjectsForContact(Contact $contact)
    {
        if (! $contact->client) {
            return collect();
        }

        return $contact->client->projects()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getProjectStatsForContact(Contact $contact): array
    {
        if (! $contact->client) {
            return [];
        }

        $projects = $contact->client->projects();

        return [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->whereNull('completed_at')->count(),
            'completed_projects' => $projects->whereNotNull('completed_at')->count(),
            'projects_on_time' => $projects->whereNull('completed_at')
                ->where('due', '>', now())
                ->count(),
        ];
    }

    protected function getRecentActivityForContact(Contact $contact): array
    {
        if (! $contact->client) {
            return [];
        }

        // Filter activity based on what the contact can see
        $activities = [];

        if ($this->canViewContracts($contact)) {
            $contracts = Contract::where('client_id', $contact->client->id)->get();
            foreach ($contracts as $contract) {
                $activities = array_merge($activities, $contract->getAuditHistory());
            }
        }

        if ($this->canViewTickets($contact)) {
            // Add ticket activity
        }

        if ($this->canViewInvoices($contact)) {
            // Add invoice activity
        }

        usort($activities, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($activities, 0, 10);
    }

    protected function getUpcomingMilestonesForContact(Contact $contact): array
    {
        if (! $this->canViewContracts($contact) || ! $contact->client) {
            return [];
        }

        return ContractMilestone::whereHas('contract', function ($query) use ($contact) {
            $query->where('client_id', $contact->client->id);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getPendingActionsForContact(Contact $contact): array
    {
        $actions = [];

        if (! $contact->client) {
            return $actions;
        }

        // Contract signatures (if can view contracts)
        if ($this->canViewContracts($contact)) {
            $pendingSignatures = ContractSignature::whereHas('contract', function ($query) use ($contact) {
                $query->where('client_id', $contact->client->id);
            })
                ->where('status', 'pending')
                ->count();

            if ($pendingSignatures > 0) {
                $actions[] = [
                    'type' => 'signature',
                    'count' => $pendingSignatures,
                    'message' => "You have {$pendingSignatures} contract(s) pending signature.",
                    'action_url' => route('client.contracts'),
                ];
            }
        }

        // Quote approvals (if can approve quotes)
        if ($this->canApproveQuotes($contact)) {
            // Add quote approval actions
        }

        // Overdue invoices (if billing contact)
        if ($this->canViewInvoices($contact)) {
            $overdueInvoices = $contact->client->invoices()
                ->where('status', 'sent')
                ->where('due_date', '<', now())
                ->count();

            if ($overdueInvoices > 0) {
                $actions[] = [
                    'type' => 'invoice',
                    'count' => $overdueInvoices,
                    'message' => "You have {$overdueInvoices} overdue invoice(s).",
                    'action_url' => route('client.invoices'),
                ];
            }
        }

        return $actions;
    }

    /**
     * Additional Role-based Methods
     */

    // Invoice methods
    public function invoices(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewInvoices($contact)) {
            abort(403, 'You do not have permission to view invoices.');
        }

        $invoices = $this->getInvoicesForContact($contact);
        $stats = $this->getInvoiceStatsForContact($contact);
        $notifications = $this->getNotificationsForContact($contact);

        if ($request->wantsJson()) {
            return response()->json([
                'invoices' => $invoices->items(),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'total_pages' => $invoices->lastPage(),
                    'total_count' => $invoices->total(),
                    'per_page' => $invoices->perPage(),
                ],
                'summary' => $stats,
            ]);
        }

        return view('client-portal.invoices.index', compact('invoices', 'stats', 'contact', 'notifications'));
    }

    public function showInvoice(Request $request, Invoice $invoice)
    {
        $contact = auth('client')->user();

        if (! $this->canViewInvoices($contact)) {
            abort(403, 'You do not have permission to view invoices.');
        }

        if ($invoice->client_id !== $contact->client_id) {
            abort(404);
        }

        $invoice->load(['items', 'payments']);

        if ($request->wantsJson()) {
            $invoiceData = $invoice->toArray();
            $invoiceData['can_be_paid'] = $invoice->canBePaid();
            
            return response()->json([
                'invoice' => $invoiceData,
                'items' => $invoice->items,
                'payments' => $invoice->payments,
                'payment_info' => [
                    'total' => $invoice->amount,
                    'paid' => $invoice->getTotalPaid(),
                    'balance' => $invoice->getBalance(),
                    'is_paid' => $invoice->isFullyPaid(),
                ],
            ]);
        }

        return view('client-portal.invoices.show', compact('invoice', 'contact'));
    }

    public function downloadClientInvoice(Request $request, Invoice $invoice)
    {
        $contact = auth('client')->user();

        if (! $this->canViewInvoices($contact)) {
            abort(403, 'You do not have permission to view invoices.');
        }

        if ($invoice->client_id !== $contact->client_id) {
            abort(404);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'pdf_url' => route('client.invoices.pdf', $invoice->id),
                'download_url' => route('client.invoices.download', $invoice->id),
            ]);
        }

        try {
            $pdf = $invoice->generatePdf();

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->invoice_number.'.pdf"',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Unable to download invoice PDF.');
        }
    }

    // Ticket methods
    public function tickets(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewTickets($contact)) {
            abort(403, 'You do not have permission to view tickets.');
        }

        $tickets = $this->getTicketsForContact($contact);
        $stats = $this->getTicketStatsForContact($contact);
        $notifications = $this->getNotificationsForContact($contact);

        return view('client-portal.tickets.index', compact('tickets', 'stats', 'contact', 'notifications'));
    }

    public function createTicket()
    {
        $contact = auth('client')->user();

        if (! $this->canCreateTickets($contact)) {
            abort(403, 'You do not have permission to create tickets.');
        }

        return view('client-portal.tickets.create', compact('contact'));
    }

    public function storeTicket(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canCreateTickets($contact)) {
            abort(403, 'You do not have permission to create tickets.');
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'details' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'category' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        try {
            \Log::info('Creating ticket for contact', [
                'contact_id' => $contact->id,
                'client_id' => $contact->client_id,
                'company_id' => $contact->company_id,
                'has_client' => $contact->client ? true : false,
            ]);

            $ticket = $contact->client->tickets()->create([
                'subject' => $request->subject,
                'details' => $request->details,
                'priority' => $request->priority,
                'category' => $request->category,
                'status' => 'Open',
                'contact_id' => $contact->id,
                'company_id' => $contact->company_id,
                'source' => 'Portal',
                'created_by' => $contact->id, // Use the contact ID who created the ticket
            ]);

            \Log::info('Ticket created successfully', ['ticket_id' => $ticket->id]);

            // TODO: Handle file attachments - TicketAttachment model needs to be created
            // if ($request->hasFile('attachments')) {
            //     foreach ($request->file('attachments') as $file) {
            //         $path = $file->store('ticket-attachments', 'public');
            //
            //         $ticket->attachments()->create([
            //             'filename' => $file->getClientOriginalName(),
            //             'path' => $path,
            //             'size' => $file->getSize(),
            //             'mime_type' => $file->getMimeType(),
            //             'uploaded_by' => 'client',
            //             'uploaded_by_id' => $contact->id,
            //         ]);
            //     }
            // }

            return redirect()->route('client.tickets.show', $ticket)
                ->with('success', 'Ticket created successfully.');

        } catch (\Exception $e) {
            \Log::error('Error creating ticket', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'contact_id' => $contact->id,
                'client_id' => $contact->client_id,
            ]);

            return redirect()->back()
                ->with('error', 'Unable to create ticket: '.$e->getMessage())
                ->withInput();
        }
    }

    public function showTicket($ticket)
    {
        $contact = auth('client')->user();

        if (! $this->canViewTickets($contact)) {
            abort(403, 'You do not have permission to view tickets.');
        }

        $ticket = \App\Domains\Ticket\Models\Ticket::withoutGlobalScope('company')
            ->where('client_id', $contact->client_id)
            ->where('company_id', $contact->company_id)
            ->with(['comments' => function ($query) {
                $query->where('visibility', 'public')
                    ->orderBy('created_at', 'asc');
            }, 'comments.author', 'assignee'])
            ->findOrFail($ticket);

        return view('client-portal.tickets.show', compact('ticket', 'contact'));
    }

    public function addTicketComment(Request $request, $ticket)
    {
        $contact = auth('client')->user();

        if (! $this->canViewTickets($contact)) {
            abort(403, 'You do not have permission to comment on tickets.');
        }

        $ticket = \App\Domains\Ticket\Models\Ticket::withoutGlobalScope('company')
            ->where('client_id', $contact->client_id)
            ->where('company_id', $contact->company_id)
            ->with('assignee')
            ->findOrFail($ticket);

        // Check if ticket is closed or resolved
        if (in_array($ticket->status, ['Closed', 'Resolved'])) {
            return redirect()->back()->withErrors(['comment' => 'Cannot reply to a closed or resolved ticket.']);
        }

        $request->validate([
            'comment' => 'required|string|min:3|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif|max:10240',
        ]);

        try {
            $comment = $ticket->comments()->create([
                'content' => $request->comment,
                'visibility' => 'public',
                'source' => 'manual',
                'author_type' => 'customer',
                'author_id' => $contact->id,
                'company_id' => $ticket->company_id,
            ]);

            // Handle file attachments if any
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = \Illuminate\Support\Str::random(40).'.'.$file->extension();
                    $content = base64_encode($file->get());

                    $comment->attachments()->create([
                        'company_id' => $ticket->company_id,
                        'filename' => $filename,
                        'original_filename' => $file->getClientOriginalName(),
                        'content' => $content,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_by' => $contact->id,
                    ]);
                }
            }

            // Change status if awaiting customer
            if ($ticket->status === 'Awaiting Customer') {
                $ticket->update(['status' => 'Open']);
            }

            // Send notification to assigned technician
            if ($ticket->assigned_to && $ticket->assignee) {
                try {
                    \Illuminate\Support\Facades\Mail::to($ticket->assignee->email)
                        ->send(new \App\Mail\Tickets\TicketCommentAdded($ticket, $comment));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send ticket comment email', [
                        'error' => $e->getMessage(),
                        'ticket_id' => $ticket->id,
                        'ticket_class' => get_class($ticket),
                        'comment_id' => $comment->id,
                        'comment_class' => get_class($comment),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Comment added successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to add ticket comment', [
                'ticket_id' => $ticket->id,
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to add comment. Please try again.');
        }
    }

    // Asset methods
    public function assets(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewAssets($contact)) {
            abort(403, 'You do not have permission to view assets.');
        }

        $assets = $this->getAssetsForContact($contact);
        $stats = $this->getAssetStatsForContact($contact);

        return view('client-portal.assets.index', compact('assets', 'stats', 'contact'));
    }

    public function showAsset($asset)
    {
        $contact = auth('client')->user();

        if (! $this->canViewAssets($contact)) {
            abort(403, 'You do not have permission to view assets.');
        }

        $asset = $contact->client->assets()->findOrFail($asset);

        return view('client-portal.assets.show', compact('asset', 'contact'));
    }

    // Project methods
    public function projects(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewProjects($contact)) {
            abort(403, 'You do not have permission to view projects.');
        }

        $projects = $this->getProjectsForContact($contact);
        $stats = $this->getProjectStatsForContact($contact);

        return view('client-portal.projects.index', compact('projects', 'stats', 'contact'));
    }

    public function showProject($project)
    {
        $contact = auth('client')->user();

        if (! $this->canViewProjects($contact)) {
            abort(403, 'You do not have permission to view projects.');
        }

        $project = $contact->client->projects()->findOrFail($project);

        return view('client-portal.projects.show', compact('project', 'contact'));
    }

    /**
     * Get notifications for contact.
     */
    protected function getNotificationsForContact(Contact $contact)
    {
        if (! $contact->client) {
            return collect();
        }

        return \App\Domains\Core\Models\PortalNotification::where('client_id', $contact->client->id)
            ->where('company_id', $contact->client->company_id)
            ->where('show_in_portal', true)
            ->active()
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationAsRead($notificationId)
    {
        $contact = auth('client')->user();

        $notification = \App\Domains\Core\Models\PortalNotification::where('id', $notificationId)
            ->where('client_id', $contact->client->id)
            ->where('company_id', $contact->client->company_id)
            ->first();

        if ($notification) {
            $notification->markAsRead();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * List client quotes
     */
    public function quotes(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewQuotes($contact)) {
            abort(403, self::ERROR_NO_PERMISSION_VIEW_QUOTES);
        }

        $quotes = $this->getQuotesForContact($contact);
        $stats = $this->getQuoteStatsForContact($contact);
        $notifications = $this->getNotificationsForContact($contact);

        return view('client-portal.quotes.index', compact('quotes', 'stats', 'contact', 'notifications'));
    }

    /**
     * Show specific quote
     */
    public function showQuote(\App\Domains\Financial\Models\Quote $quote)
    {
        $contact = auth('client')->user();

        if (! $this->canViewQuotes($contact)) {
            abort(403, self::ERROR_NO_PERMISSION_VIEW_QUOTES);
        }

        if ($quote->client_id !== $contact->client_id) {
            abort(403, 'Unauthorized access to quote.');
        }

        $quote->load(['items', 'client', 'category']);

        return view('client-portal.quotes.show', compact('quote', 'contact'));
    }

    /**
     * Download quote PDF
     */
    public function downloadQuotePdf(\App\Domains\Financial\Models\Quote $quote)
    {
        $contact = auth('client')->user();

        if (! $this->canViewQuotes($contact)) {
            abort(403, self::ERROR_NO_PERMISSION_VIEW_QUOTES);
        }

        if ($quote->client_id !== $contact->client_id) {
            abort(403, 'Unauthorized access to quote.');
        }

        try {
            $quote->load(['client', 'items', 'category']);

            $pdfService = app(\App\Domains\Core\Services\PdfService::class);
            $filename = $pdfService->generateFilename('quote', $quote->getFullNumber());

            return $pdfService->download(
                view: 'pdf.quote',
                data: ['quote' => $quote],
                filename: $filename,
                options: ['template' => 'quote']
            );
        } catch (\Exception $e) {
            Log::error('Quote PDF generation failed for client', [
                'quote_id' => $quote->id,
                'client_id' => $contact->client_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to download quote PDF.');
        }
    }

    /**
     * Check if contact can view quotes
     */
    protected function canViewQuotes(Contact $contact): bool
    {
        return $contact->isPrimary() ||
               $contact->isBilling() ||
               in_array('can_view_quotes', $contact->portal_permissions ?? []);
    }

    /**
     * Get quotes for contact
     */
    protected function getQuotesForContact(Contact $contact)
    {
        if (! $contact->client) {
            return collect();
        }

        return $contact->client->quotes()
            ->with(['items', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Get quote statistics for contact
     */
    protected function getQuoteStatsForContact(Contact $contact): array
    {
        if (! $contact->client) {
            return [];
        }

        $quotes = $contact->client->quotes();

        return [
            'total' => $quotes->count(),
            'pending' => $quotes->whereIn('status', ['Sent', 'Viewed'])->count(),
            'accepted' => $quotes->where('status', 'Accepted')->count(),
            'total_value' => $quotes->where('status', 'Accepted')->sum('amount'),
        ];
    }

    /**
     * Get invoice summary dashboard data
     */
    public function invoicesSummary(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewInvoices($contact)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->getInvoiceStatsForContact($contact);
        $recentInvoices = $this->getInvoicesForContact($contact)->take(5);
        $upcomingDue = $contact->client->invoices()
            ->where('status', '!=', 'paid')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->take(5)
            ->get();

        $thisYear = $contact->client->invoices()
            ->whereYear('date', now()->year)
            ->sum('amount');
        
        $thisMonth = $contact->client->invoices()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount');

        return response()->json(array_merge($stats, [
            'current_balance' => $stats['total_outstanding'] ?? 0,
            'this_year' => $thisYear,
            'this_month' => $thisMonth,
            'recent_invoices' => $recentInvoices,
            'upcoming_due' => $upcomingDue,
        ]));
    }

    /**
     * Get invoice statistics/analytics
     */
    public function invoicesStatistics(Request $request)
    {
        $contact = auth('client')->user();

        if (! $this->canViewInvoices($contact)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date', now()->subYear());
        $endDate = $request->input('end_date', now());

        $invoices = $contact->client->invoices()
            ->whereBetween('date', [$startDate, $endDate]);

        $byStatus = $contact->client->invoices()
            ->selectRaw('status, count(*) as count, sum(amount) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => ['count' => $item->count, 'total' => $item->total]];
            });

        return response()->json([
            'totals' => [
                'total_invoiced' => $invoices->sum('amount'),
                'total_paid' => $invoices->where('status', 'paid')->sum('amount'),
                'total_outstanding' => $invoices->whereIn('status', ['sent', 'viewed', 'overdue'])->sum('amount'),
                'average_invoice_value' => $invoices->avg('amount'),
                'invoice_count' => $invoices->count(),
            ],
            'by_status' => $byStatus,
            'payment_trends' => $invoices->where('status', 'paid')
                ->orderBy('date')
                ->get()
                ->groupBy(function ($invoice) {
                    return $invoice->date->format('Y-m');
                })
                ->map(function ($group) {
                    return $group->sum('amount');
                }),
        ]);
    }

    /**
     * Get payment options for an invoice
     */
    public function invoicePaymentOptions(Request $request, $invoiceId)
    {
        $contact = auth('client')->user();
        $invoice = \App\Domains\Financial\Models\Invoice::findOrFail($invoiceId);

        if ($invoice->client_id !== $contact->client_id || ! $this->canViewInvoices($contact)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (! $invoice->canBePaid()) {
            return response()->json(['error' => 'Invoice cannot be paid'], 400);
        }

        $balance = $invoice->getBalance();

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'amount' => $invoice->amount,
                'balance' => $balance,
            ],
            'payment_methods' => ['credit_card', 'bank_transfer', 'ach'],
            'payment_amounts' => [
                'suggested_amounts' => [
                    'minimum' => max(50, $balance * 0.25),
                    'partial' => $balance * 0.5,
                    'full' => $balance,
                ],
            ],
            'payment_terms' => $invoice->payment_terms ?? 'Net 30',
        ]);
    }
}
