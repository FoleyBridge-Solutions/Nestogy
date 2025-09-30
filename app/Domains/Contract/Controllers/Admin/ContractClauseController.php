<?php

namespace App\Domains\Contract\Controllers\Admin;

use App\Domains\Contract\Models\ContractClause;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContractClauseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display contract clauses management interface
     */
    public function index(): View
    {
        $clauses = ContractClause::where('company_id', auth()->user()->company_id)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $categories = ContractClause::getDefaultCategories();
        $statistics = $this->getStatistics();

        return view('admin.contract-clauses.index', compact('clauses', 'categories', 'statistics'));
    }

    /**
     * Store a new contract clause
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'required|string|max:50',
                'type' => 'required|string|max:50',
                'content' => 'required|string',
                'variables' => 'nullable|json',
                'conditions' => 'nullable|json',
                'is_required' => 'boolean',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $clause = ContractClause::create([
                'company_id' => auth()->user()->company_id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'type' => $validated['type'],
                'content' => $validated['content'],
                'variables' => json_decode($validated['variables'] ?? '{}', true),
                'conditions' => json_decode($validated['conditions'] ?? '[]', true),
                'is_required' => $validated['is_required'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
                'legal_review_status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract clause created successfully',
                'data' => $clause,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create contract clause', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract clause: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show contract clause details
     */
    public function show(ContractClause $clause): JsonResponse
    {
        $this->authorize('view', $clause);

        return response()->json([
            'success' => true,
            'data' => $clause,
        ]);
    }

    /**
     * Update contract clause
     */
    public function update(Request $request, ContractClause $clause): JsonResponse
    {
        $this->authorize('update', $clause);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'required|string|max:50',
                'type' => 'required|string|max:50',
                'content' => 'required|string',
                'variables' => 'nullable|json',
                'conditions' => 'nullable|json',
                'is_required' => 'boolean',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $clause->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'type' => $validated['type'],
                'content' => $validated['content'],
                'variables' => json_decode($validated['variables'] ?? '{}', true),
                'conditions' => json_decode($validated['conditions'] ?? '[]', true),
                'is_required' => $validated['is_required'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract clause updated successfully',
                'data' => $clause,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract clause',
            ], 500);
        }
    }

    /**
     * Delete contract clause
     */
    public function destroy(ContractClause $clause): JsonResponse
    {
        $this->authorize('delete', $clause);

        try {
            $clause->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contract clause deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract clause',
            ], 500);
        }
    }

    /**
     * Approve contract clause
     */
    public function approve(ContractClause $clause): JsonResponse
    {
        $this->authorize('update', $clause);

        try {
            $clause->update([
                'legal_review_status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract clause approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve contract clause',
            ], 500);
        }
    }

    /**
     * Create default MSP clauses
     */
    public function createMSPDefaults(): JsonResponse
    {
        try {
            $defaultClauses = ContractClause::getDefaultMSPClauses();
            $created = 0;

            foreach ($defaultClauses as $clauseData) {
                $clauseData['company_id'] = auth()->user()->company_id;
                $clauseData['legal_review_status'] = 'approved'; // Pre-approved standard clauses

                // Check if clause already exists
                $exists = ContractClause::where('company_id', auth()->user()->company_id)
                    ->where('title', $clauseData['title'])
                    ->exists();

                if (! $exists) {
                    ContractClause::create($clauseData);
                    $created++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully created {$created} MSP contract clauses",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create MSP clauses',
            ], 500);
        }
    }

    /**
     * Import standard clause library
     */
    public function importStandardLibrary(): JsonResponse
    {
        try {
            $standardLibrary = $this->getStandardClauseLibrary();
            $imported = 0;

            foreach ($standardLibrary as $clauseData) {
                $clauseData['company_id'] = auth()->user()->company_id;

                // Check if clause already exists
                $exists = ContractClause::where('company_id', auth()->user()->company_id)
                    ->where('title', $clauseData['title'])
                    ->exists();

                if (! $exists) {
                    ContractClause::create($clauseData);
                    $imported++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$imported} standard contract clauses",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import standard library',
            ], 500);
        }
    }

    /**
     * Get contract clause statistics
     */
    protected function getStatistics(): array
    {
        $companyId = auth()->user()->company_id;

        $totalClauses = ContractClause::where('company_id', $companyId)->count();
        $approvedClauses = ContractClause::where('company_id', $companyId)->where('legal_review_status', 'approved')->count();

        return [
            'total_clauses' => $totalClauses,
            'approved_clauses' => $approvedClauses,
        ];
    }

    /**
     * Get standard clause library
     */
    protected function getStandardClauseLibrary(): array
    {
        return array_merge(
            ContractClause::getDefaultMSPClauses(),
            [
                // Additional legal clauses
                [
                    'title' => 'Force Majeure',
                    'category' => 'general',
                    'type' => 'legal',
                    'content' => '<h4>Force Majeure</h4>
                    <p>Neither party shall be liable for any failure or delay in performance under this Agreement which is due to fire, flood, earthquake, elements of nature or acts of God, acts of war, terrorism, riots, civil disorders, rebellions or revolutions, or any other similar cause beyond the reasonable control of such party.</p>',
                    'legal_review_status' => 'approved',
                ],
                [
                    'title' => 'Governing Law',
                    'category' => 'general',
                    'type' => 'legal',
                    'content' => '<h4>Governing Law</h4>
                    <p>This Agreement shall be governed by and construed in accordance with the laws of {{company.state}}, without regard to its conflict of law provisions. Any disputes arising under this Agreement shall be subject to the exclusive jurisdiction of the courts of {{company.state}}.</p>',
                    'variables' => [
                        'company.state' => 'Company State/Province',
                    ],
                    'legal_review_status' => 'approved',
                ],
                [
                    'title' => 'Entire Agreement',
                    'category' => 'general',
                    'type' => 'legal',
                    'content' => '<h4>Entire Agreement</h4>
                    <p>This Agreement constitutes the entire agreement between the parties and supersedes all prior or contemporaneous understandings, agreements, negotiations, representations and warranties, and communications, both written and oral, with respect to the subject matter of this Agreement.</p>',
                    'legal_review_status' => 'approved',
                ],
                [
                    'title' => 'HIPAA Compliance',
                    'category' => 'compliance',
                    'type' => 'compliance',
                    'content' => '<h4>HIPAA Compliance</h4>
                    <p>Service Provider acknowledges that it may have access to Protected Health Information (PHI) as defined by HIPAA. Service Provider agrees to:</p>
                    <ul>
                        <li>Comply with all applicable HIPAA requirements</li>
                        <li>Implement appropriate safeguards to protect PHI</li>
                        <li>Report any breaches or unauthorized access immediately</li>
                        <li>Execute a Business Associate Agreement if required</li>
                    </ul>',
                    'conditions' => [
                        ['field' => 'client.industry', 'operator' => 'in', 'value' => ['healthcare', 'medical']],
                    ],
                    'legal_review_status' => 'approved',
                ],
                [
                    'title' => 'PCI DSS Compliance',
                    'category' => 'compliance',
                    'type' => 'compliance',
                    'content' => '<h4>PCI DSS Compliance</h4>
                    <p>For clients processing credit card transactions, Service Provider will maintain PCI DSS compliance and ensure all systems handling cardholder data meet PCI DSS requirements.</p>',
                    'conditions' => [
                        ['field' => 'services', 'operator' => 'contains', 'value' => 'payment processing'],
                    ],
                    'legal_review_status' => 'approved',
                ],
                [
                    'title' => 'Remote Access Policy',
                    'category' => 'service',
                    'type' => 'standard',
                    'content' => '<h4>Remote Access Policy</h4>
                    <p>Service Provider may require remote access to Client systems to provide services. Such access will be:</p>
                    <ul>
                        <li>Secured using industry-standard encryption</li>
                        <li>Logged and monitored for security purposes</li>
                        <li>Limited to the minimum necessary for service delivery</li>
                        <li>Terminated immediately after service completion</li>
                    </ul>',
                    'legal_review_status' => 'approved',
                ],
                [
                    'title' => 'Business Continuity',
                    'category' => 'service',
                    'type' => 'standard',
                    'content' => '<h4>Business Continuity</h4>
                    <p>Service Provider maintains business continuity plans to ensure service delivery during emergencies. In the event of a service disruption, Service Provider will:</p>
                    <ul>
                        <li>Implement disaster recovery procedures within {{recovery.rto}} hours</li>
                        <li>Maintain communication with Client throughout the incident</li>
                        <li>Provide regular status updates every {{communication.frequency}} hours</li>
                        <li>Restore full service capability within {{recovery.rto}} hours</li>
                    </ul>',
                    'variables' => [
                        'recovery.rto' => 'Recovery Time Objective (hours)',
                        'communication.frequency' => 'Communication Frequency (hours)',
                    ],
                    'legal_review_status' => 'approved',
                ],
            ]
        );
    }
}
