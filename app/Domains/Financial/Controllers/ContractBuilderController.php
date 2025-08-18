<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Financial\ContractComponent;
use App\Models\Financial\ContractComponentAssignment;
use App\Domains\Financial\Requests\StoreBuiltContractRequest;
use App\Domains\Financial\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContractBuilderController extends Controller
{
    protected ContractService $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    /**
     * Show the contract builder interface
     */
    public function index()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $components = ContractComponent::where('company_id', auth()->user()->company_id)
            ->active()
            ->with(['creator', 'updater'])
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Add system components if none exist
        if ($components->isEmpty()) {
            $this->seedSystemComponents();
            $components = ContractComponent::where('company_id', auth()->user()->company_id)
                ->active()
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $templates = Contract::where('company_id', auth()->user()->company_id)
            ->where('is_template', true)
            ->with(['componentAssignments.component'])
            ->get()
            ->map(function ($template) {
                $template->components_count = $template->componentAssignments->count();
                $template->estimated_value = $template->componentAssignments->sum(function ($assignment) {
                    return $assignment->calculatePrice();
                });
                return $template;
            });

        return view('financial.contracts.builder', compact('clients', 'components', 'templates'));
    }

    /**
     * Store a contract built with the dynamic builder
     */
    public function store(StoreBuiltContractRequest $request): JsonResponse
    {
        try {
            $contract = $this->contractService->createFromBuilder(
                $request->validated(),
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'redirect_url' => route('contracts.show', $contract)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating contract: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get component details for the builder
     */
    public function getComponent(ContractComponent $component): JsonResponse
    {
        $this->authorize('view', $component);

        return response()->json([
            'component' => $component->load(['creator', 'updater']),
            'sample_pricing' => $component->calculatePrice([
                'units' => 1,
                'quantity' => 1
            ])
        ]);
    }

    /**
     * Calculate pricing for component configuration
     */
    public function calculatePricing(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|exists:contract_components,id',
            'variables' => 'array',
            'pricing_override' => 'nullable|array'
        ]);

        $component = ContractComponent::findOrFail($request->component_id);
        $this->authorize('view', $component);

        $variables = $request->input('variables', []);
        $pricingOverride = $request->input('pricing_override');

        // Calculate base price
        $basePrice = $component->calculatePrice($variables);

        // Apply override if present
        $finalPrice = $basePrice;
        if ($pricingOverride && isset($pricingOverride['type']) && $pricingOverride['amount'] > 0) {
            switch ($pricingOverride['type']) {
                case 'fixed':
                    $finalPrice = (float) $pricingOverride['amount'];
                    break;
                case 'percentage':
                    $percentage = (float) $pricingOverride['amount'];
                    $finalPrice = $basePrice * ($percentage / 100);
                    break;
            }
        }

        return response()->json([
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'variables_used' => $variables,
            'override_applied' => $pricingOverride ? true : false
        ]);
    }

    /**
     * Load template components for the builder
     */
    public function loadTemplate(Contract $template): JsonResponse
    {
        $this->authorize('view', $template);

        if (!$template->is_template) {
            return response()->json([
                'success' => false,
                'message' => 'This is not a template contract'
            ], 400);
        }

        $assignments = $template->componentAssignments()
            ->with('component')
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => 'template_' . $assignment->id,
                    'component' => $assignment->component,
                    'variable_values' => $assignment->variable_values ?? [],
                    'has_pricing_override' => !empty($assignment->pricing_override),
                    'pricing_override' => $assignment->pricing_override ?? [
                        'type' => 'fixed',
                        'amount' => 0
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'template' => $template,
            'components' => $assignments,
            'total_value' => $assignments->sum(function ($assignment) {
                return $assignment['component']->calculatePrice($assignment['variable_values']);
            })
        ]);
    }

    /**
     * Preview contract content
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'contract' => 'required|array',
            'components' => 'required|array'
        ]);

        $contractData = $request->input('contract');
        $components = $request->input('components');

        // Generate preview content
        $preview = [
            'title' => $contractData['title'] ?? 'Untitled Contract',
            'client_name' => $this->getClientName($contractData['client_id'] ?? null),
            'sections' => [],
            'total_value' => 0
        ];

        foreach ($components as $componentData) {
            $component = ContractComponent::find($componentData['component']['id']);
            if ($component) {
                $variables = $componentData['variable_values'] ?? [];
                $price = $this->calculateComponentPrice($component, $variables, $componentData['pricing_override'] ?? null);
                
                $preview['sections'][] = [
                    'category' => $component->category,
                    'name' => $component->name,
                    'content' => $this->renderComponentContent($component, $variables),
                    'price' => $price
                ];
                
                $preview['total_value'] += $price;
            }
        }

        return response()->json([
            'success' => true,
            'preview' => $preview
        ]);
    }

    /**
     * Save draft to session
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $request->validate([
            'contract' => 'required|array',
            'components' => 'required|array'
        ]);

        session(['contract_draft' => [
            'contract' => $request->input('contract'),
            'components' => $request->input('components'),
            'saved_at' => now()->toISOString()
        ]]);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully'
        ]);
    }

    /**
     * Load draft from session
     */
    public function loadDraft(): JsonResponse
    {
        $draft = session('contract_draft');

        if (!$draft) {
            return response()->json([
                'success' => false,
                'message' => 'No draft found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft
        ]);
    }

    /**
     * Clear saved draft
     */
    public function clearDraft(): JsonResponse
    {
        session()->forget('contract_draft');

        return response()->json([
            'success' => true,
            'message' => 'Draft cleared'
        ]);
    }

    /**
     * Seed system components if none exist
     */
    protected function seedSystemComponents(): void
    {
        $systemComponents = ContractComponent::getSystemComponents();
        
        foreach ($systemComponents as $componentData) {
            ContractComponent::create(array_merge($componentData, [
                'company_id' => auth()->user()->company_id,
                'is_system' => true,
                'status' => ContractComponent::STATUS_ACTIVE,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]));
        }
    }

    /**
     * Get client name by ID
     */
    protected function getClientName(?int $clientId): string
    {
        if (!$clientId) {
            return 'Unknown Client';
        }

        $client = Client::find($clientId);
        return $client ? $client->name : 'Unknown Client';
    }

    /**
     * Calculate component price with overrides
     */
    protected function calculateComponentPrice(ContractComponent $component, array $variables, ?array $pricingOverride): float
    {
        $basePrice = $component->calculatePrice($variables);

        if ($pricingOverride && isset($pricingOverride['type']) && $pricingOverride['amount'] > 0) {
            switch ($pricingOverride['type']) {
                case 'fixed':
                    return (float) $pricingOverride['amount'];
                case 'percentage':
                    $percentage = (float) $pricingOverride['amount'];
                    return $basePrice * ($percentage / 100);
            }
        }

        return $basePrice;
    }

    /**
     * Render component content with variables
     */
    protected function renderComponentContent(ContractComponent $component, array $variables): string
    {
        $content = $component->template_content ?? '';

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}