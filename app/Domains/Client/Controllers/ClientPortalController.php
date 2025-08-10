<?php

namespace App\Domains\Client\Controllers;

use App\Models\Contract;
use App\Models\Client;
use App\Models\ContractSignature;
use App\Models\ContractMilestone;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DigitalSignatureService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * ClientPortalController
 * 
 * Handles client-facing portal functionality for contract management,
 * digital signing, milestone tracking, and payment management.
 */
class ClientPortalController extends Controller
{
    protected DigitalSignatureService $signatureService;

    public function __construct(DigitalSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
        $this->middleware('guest:client')->except(['logout']);
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
            'access_code' => 'required|string',
        ]);

        $client = Client::where('email', $request->email)->first();

        if ($client && $this->verifyAccessCode($client, $request->access_code)) {
            auth('client')->login($client);
            
            return redirect()->intended(route('client.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Invalid credentials or access code.',
        ])->onlyInput('email');
    }

    /**
     * Client dashboard
     */
    public function dashboard()
    {
        $client = auth('client')->user();
        
        $contracts = Contract::where('client_id', $client->id)
            ->with(['milestones', 'signatures', 'invoices'])
            ->get();

        $stats = [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'pending_signatures' => $contracts->flatMap->signatures->where('status', 'pending')->count(),
            'overdue_milestones' => $contracts->flatMap->milestones
                ->where('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->count(),
            'total_contract_value' => $contracts->sum('contract_value'),
        ];

        $recentActivity = $this->getRecentActivity($client->id);
        $upcomingMilestones = $this->getUpcomingMilestones($client->id);
        $pendingActions = $this->getPendingActions($client->id);

        return view('client-portal.dashboard', compact(
            'client',
            'contracts', 
            'stats',
            'recentActivity',
            'upcomingMilestones',
            'pendingActions'
        ));
    }

    /**
     * List client contracts
     */
    public function contracts(Request $request)
    {
        $client = auth('client')->user();
        
        $query = Contract::where('client_id', $client->id)
            ->with(['milestones', 'signatures', 'invoices']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('contract_type', $request->type);
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('client-portal.contracts.index', compact('contracts'));
    }

    /**
     * View specific contract
     */
    public function viewContract(Contract $contract)
    {
        $this->authorizeClientAccess($contract);

        $contract->load([
            'milestones' => function($query) {
                $query->orderBy('due_date', 'asc');
            },
            'signatures',
            'invoices' => function($query) {
                $query->with('payments')->orderBy('created_at', 'desc');
            },
            'approvals' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'auditLogs' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        $nextMilestone = $contract->milestones
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->first();

        $pendingSignatures = $contract->signatures->where('status', 'pending');
        $outstandingInvoices = $contract->invoices->where('status', 'Sent');

        return view('client-portal.contracts.show', compact(
            'contract',
            'nextMilestone',
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

            if (!$signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending signature found for this contract.'
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
                'message' => 'Contract signed successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error signing contract', [
                'contract_id' => $contract->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while signing the contract.'
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
                'Content-Disposition' => 'attachment; filename="contract-' . $contract->contract_number . '.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating contract PDF for client', [
                'contract_id' => $contract->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage()
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
                'error' => $e->getMessage()
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
                'Content-Disposition' => 'attachment; filename="invoice-' . $invoice->invoice_number . '.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating invoice PDF for client', [
                'invoice_id' => $invoice->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Client profile/settings
     */
    public function profile()
    {
        $client = auth('client')->user();
        
        return view('client-portal.profile', compact('client'));
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request)
    {
        $client = auth('client')->user();

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notification_preferences' => 'nullable|array',
        ]);

        try {
            $client->update($request->only([
                'phone', 'address', 'city', 'state', 'postal_code', 'country'
            ]));

            if ($request->has('notification_preferences')) {
                $client->update([
                    'notification_preferences' => $request->notification_preferences
                ]);
            }

            return redirect()->back()->with('success', 'Profile updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating client profile', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
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
        if ($contract->client_id !== auth('client')->id()) {
            abort(403, 'Unauthorized access to contract.');
        }
    }

    protected function verifyAccessCode(Client $client, string $accessCode): bool
    {
        // Simple access code verification - in production, use more secure methods
        $expectedCode = substr(md5($client->email . $client->created_at), 0, 8);
        return hash_equals($expectedCode, $accessCode);
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
        return ContractMilestone::whereHas('contract', function($query) use ($clientId) {
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
        $pendingSignatures = ContractSignature::whereHas('contract', function($query) use ($clientId) {
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
        $overdueMilestones = ContractMilestone::whereHas('contract', function($query) use ($clientId) {
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
}