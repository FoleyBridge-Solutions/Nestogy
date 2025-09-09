<?php

namespace App\Domains\Contract\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Models\ContractClauseModel;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractTemplateController extends Controller
{
    protected ContractConfigurationRegistry $configRegistry;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->configRegistry = new ContractConfigurationRegistry(auth()->user()->company_id);
            return $next($request);
        });
    }

    /**
     * Display contract templates management interface
     */
    public function index(): View
    {
        $templates = ContractTemplate::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $contractTypes = $this->configRegistry->getContractTypes();
        
        $clauses = ContractClauseModel::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        $statistics = $this->getStatistics();

        return view('admin.contract-templates.index', compact('templates', 'contractTypes', 'clauses', 'statistics'));
    }

    /**
     * Store a new contract template
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'contract_type' => 'required|string|max:100',
                'category' => 'required|string|max:50',
                'content' => 'required|string',
                'version' => 'nullable|string|max:20',
                'status' => 'required|in:draft,active,archived',
                'is_default' => 'boolean',
                'clauses' => 'nullable|array',
                'clauses.*' => 'exists:contract_clauses,id'
            ]);

            $template = ContractTemplate::create([
                'company_id' => auth()->user()->company_id,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'contract_type' => $validated['contract_type'],
                'category' => $validated['category'],
                'content' => $validated['content'],
                'version' => $validated['version'] ?? '1.0',
                'status' => $validated['status'],
                'is_default' => $validated['is_default'] ?? false,
                'clauses' => $validated['clauses'] ?? [],
            ]);

            // If this is set as default, unset other defaults for same contract type
            if ($template->is_default) {
                ContractTemplate::where('company_id', auth()->user()->company_id)
                    ->where('contract_type', $template->contract_type)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contract template created successfully',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create contract template', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show contract template details
     */
    public function show(ContractTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json([
            'success' => true,
            'data' => $template->load('contractClauses')
        ]);
    }

    /**
     * Update contract template
     */
    public function update(Request $request, ContractTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'contract_type' => 'required|string|max:100',
                'category' => 'required|string|max:50',
                'content' => 'required|string',
                'version' => 'nullable|string|max:20',
                'status' => 'required|in:draft,active,archived',
                'is_default' => 'boolean',
                'clauses' => 'nullable|array',
            ]);

            $template->update($validated);

            // Handle default template logic
            if ($template->is_default) {
                ContractTemplate::where('company_id', auth()->user()->company_id)
                    ->where('contract_type', $template->contract_type)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contract template updated successfully',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract template'
            ], 500);
        }
    }

    /**
     * Clone contract template
     */
    public function clone(ContractTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        try {
            $clonedTemplate = $template->cloneTemplate($template->name . ' (Copy)');

            return response()->json([
                'success' => true,
                'message' => 'Template cloned successfully',
                'data' => $clonedTemplate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone template'
            ], 500);
        }
    }

    /**
     * Delete contract template
     */
    public function destroy(ContractTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        try {
            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contract template deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contract template'
            ], 500);
        }
    }

    /**
     * Preview template with sample data
     */
    public function preview(ContractTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        try {
            $preview = $template->getPreview();

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $preview,
                    'template' => $template
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview'
            ], 500);
        }
    }

    /**
     * Export template
     */
    public function export(ContractTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        try {
            $exportData = [
                'version' => '1.0',
                'exported_at' => now()->toISOString(),
                'template' => $template->toArray()
            ];

            return response()->json($exportData);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export template'
            ], 500);
        }
    }

    /**
     * Import standard templates
     */
    public function importStandards(): JsonResponse
    {
        try {
            $standardTemplates = $this->getStandardMSPTemplates();
            $imported = 0;

            foreach ($standardTemplates as $templateData) {
                $templateData['company_id'] = auth()->user()->company_id;
                
                // Check if template already exists
                $exists = ContractTemplate::where('company_id', auth()->user()->company_id)
                    ->where('name', $templateData['name'])
                    ->exists();

                if (!$exists) {
                    ContractTemplate::create($templateData);
                    $imported++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$imported} standard templates"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import standard templates'
            ], 500);
        }
    }

    /**
     * Get contract template statistics
     */
    protected function getStatistics(): array
    {
        $companyId = auth()->user()->company_id;
        
        $totalTemplates = ContractTemplate::where('company_id', $companyId)->count();
        $activeTemplates = ContractTemplate::where('company_id', $companyId)->where('status', 'active')->count();
        $contractsGenerated = 0; // This would come from actual contract usage
        $pendingReview = ContractTemplate::where('company_id', $companyId)->where('status', 'draft')->count();

        return [
            'total_templates' => $totalTemplates,
            'active_templates' => $activeTemplates,
            'contracts_generated' => $contractsGenerated,
            'pending_review' => $pendingReview,
        ];
    }

    /**
     * Get standard MSP templates
     */
    protected function getStandardMSPTemplates(): array
    {
        return [
            [
                'name' => 'MSP Service Agreement',
                'description' => 'Standard managed service provider agreement',
                'contract_type' => 'msp_services',
                'category' => 'service',
                'content' => $this->getMSPServiceAgreementTemplate(),
                'status' => 'active',
                'version' => '1.0'
            ],
            [
                'name' => 'IT Support Contract',
                'description' => 'General IT support and maintenance contract',
                'contract_type' => 'it_support',
                'category' => 'maintenance',
                'content' => $this->getITSupportContractTemplate(),
                'status' => 'active',
                'version' => '1.0'
            ],
            [
                'name' => 'Cloud Services Agreement',
                'description' => 'Cloud infrastructure and services contract',
                'contract_type' => 'cloud_services',
                'category' => 'service',
                'content' => $this->getCloudServicesTemplate(),
                'status' => 'active',
                'version' => '1.0'
            ]
        ];
    }

    /**
     * MSP Service Agreement Template
     */
    protected function getMSPServiceAgreementTemplate(): string
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1 style="text-align: center; color: #333;">MANAGED SERVICE PROVIDER AGREEMENT</h1>
            
            <p><strong>Effective Date:</strong> {{contract.start_date}}</p>
            
            <p>This Managed Service Provider Agreement ("Agreement") is entered into between:</p>
            
            <table style="width: 100%; margin: 20px 0;">
                <tr>
                    <td style="vertical-align: top; width: 50%; padding-right: 20px;">
                        <strong>Service Provider:</strong><br>
                        {{company.name}}<br>
                        {{company.address}}<br>
                        Phone: {{company.phone}}<br>
                        Email: {{company.email}}
                    </td>
                    <td style="vertical-align: top; width: 50%;">
                        <strong>Client:</strong><br>
                        {{client.name}}<br>
                        Contact: {{client.contact_name}}<br>
                        {{client.address}}<br>
                        Email: {{client.contact_email}}
                    </td>
                </tr>
            </table>

            <h3>1. SERVICES PROVIDED</h3>
            <p>Service Provider will provide comprehensive managed IT services including:</p>
            <ul>
                <li>24/7 network monitoring and alerting</li>
                <li>Help desk support during business hours</li>
                <li>Proactive maintenance and updates</li>
                <li>Security monitoring and incident response</li>
                <li>Backup and disaster recovery services</li>
            </ul>

            <h3>2. SERVICE LEVEL AGREEMENT</h3>
            <p>Service Provider commits to the following service levels:</p>
            <ul>
                <li>Network uptime: 99.5% monthly availability</li>
                <li>Critical issue response: 2 hours</li>
                <li>Standard issue response: 4 business hours</li>
                <li>Help desk availability: 8 AM - 6 PM EST, Monday-Friday</li>
            </ul>

            <h3>3. PAYMENT TERMS</h3>
            <p>Client agrees to pay {{contract.value}} monthly, with payment due within 30 days of invoice date. Late payments will incur a 1.5% monthly service charge.</p>

            <h3>4. TERM AND TERMINATION</h3>
            <p>This Agreement shall commence on {{contract.start_date}} and continue for a period of 12 months, automatically renewing for successive 12-month periods unless terminated by either party with 60 days written notice.</p>

            <h3>5. DATA SECURITY AND CONFIDENTIALITY</h3>
            <p>Service Provider acknowledges access to confidential Client information and agrees to maintain strict confidentiality and implement industry-standard security measures.</p>

            <h3>6. LIMITATION OF LIABILITY</h3>
            <p>Service Provider\'s total liability under this agreement shall not exceed the total amount paid by Client in the twelve months preceding the claim. IN NO EVENT SHALL SERVICE PROVIDER BE LIABLE FOR INDIRECT, INCIDENTAL, OR CONSEQUENTIAL DAMAGES.</p>

            <div style="margin-top: 50px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; text-align: center; padding: 20px;">
                            <strong>SERVICE PROVIDER</strong><br><br>
                            _________________________<br>
                            {{company.name}}<br>
                            Date: ___________
                        </td>
                        <td style="width: 50%; text-align: center; padding: 20px;">
                            <strong>CLIENT</strong><br><br>
                            _________________________<br>
                            {{client.name}}<br>
                            Date: ___________
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }

    /**
     * IT Support Contract Template
     */
    protected function getITSupportContractTemplate(): string
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1 style="text-align: center; color: #333;">IT SUPPORT AND MAINTENANCE AGREEMENT</h1>
            
            <p><strong>Agreement Date:</strong> {{contract.start_date}}</p>
            
            <h3>PARTIES</h3>
            <p><strong>IT Service Provider:</strong> {{company.name}}</p>
            <p><strong>Client:</strong> {{client.name}}</p>

            <h3>SCOPE OF SERVICES</h3>
            <p>IT support services including:</p>
            <ul>
                <li>Hardware troubleshooting and repair</li>
                <li>Software installation and configuration</li>
                <li>Network setup and maintenance</li>
                <li>User support and training</li>
                <li>System backups and data recovery</li>
            </ul>

            <h3>SUPPORT HOURS</h3>
            <p>Support available Monday through Friday, 9:00 AM to 5:00 PM EST. Emergency support available 24/7 for critical issues.</p>

            <h3>PRICING</h3>
            <p>Monthly fee: {{contract.value}}<br>
            Hourly rate for additional services: $150/hour<br>
            Payment terms: Net 30 days</p>

            <h3>TERM</h3>
            <p>Initial term: 12 months from {{contract.start_date}}<br>
            Auto-renewal: Month-to-month after initial term</p>

            <div style="margin-top: 40px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;">
                            <strong>SERVICE PROVIDER:</strong><br><br>
                            Signature: _____________________<br>
                            Date: _____________
                        </td>
                        <td style="width: 50%;">
                            <strong>CLIENT:</strong><br><br>
                            Signature: _____________________<br>
                            Date: _____________
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }

    /**
     * Cloud Services Template
     */
    protected function getCloudServicesTemplate(): string
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
            <h1 style="text-align: center; color: #333;">CLOUD SERVICES AGREEMENT</h1>
            
            <p><strong>Effective:</strong> {{contract.start_date}} to {{contract.end_date}}</p>
            
            <h3>SERVICE PROVIDER</h3>
            <p>{{company.name}}<br>{{company.address}}<br>{{company.phone}}</p>

            <h3>CLIENT</h3>
            <p>{{client.name}}<br>{{client.address}}</p>

            <h3>CLOUD SERVICES INCLUDED</h3>
            <ul>
                <li>Cloud infrastructure hosting</li>
                <li>Data backup and synchronization</li>
                <li>Application hosting and management</li>
                <li>Security monitoring and compliance</li>
                <li>24/7 technical support</li>
            </ul>

            <h3>SERVICE LEVELS</h3>
            <p>Guaranteed uptime: 99.9%<br>
            Data backup frequency: Daily<br>
            Support response time: 1 hour for critical issues</p>

            <h3>FEES AND PAYMENT</h3>
            <p>Monthly service fee: {{contract.value}}<br>
            Setup fee: One-time $500<br>
            Payment terms: Due upon receipt of invoice</p>

            <h3>DATA PROTECTION</h3>
            <p>All client data will be encrypted at rest and in transit. Service Provider maintains SOC 2 Type II certification and complies with applicable data protection regulations.</p>

            <div style="margin-top: 40px; text-align: center;">
                <p><strong>By signing below, both parties agree to the terms of this agreement.</strong></p>
                
                <table style="width: 100%; margin-top: 30px;">
                    <tr>
                        <td style="width: 50%; text-align: left;">
                            {{company.name}}<br><br>
                            ________________________<br>
                            Authorized Signature<br>
                            Date: ___________
                        </td>
                        <td style="width: 50%; text-align: left;">
                            {{client.name}}<br><br>
                            ________________________<br>
                            Authorized Signature<br>
                            Date: ___________
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }
}