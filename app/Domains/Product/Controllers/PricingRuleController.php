<?php

namespace App\Domains\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Client;
use App\Domains\Product\Requests\StorePricingRuleRequest;
use App\Domains\Product\Requests\UpdatePricingRuleRequest;
use App\Services\PricingRuleService;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    protected $pricingRuleService;

    public function __construct(PricingRuleService $pricingRuleService)
    {
        $this->pricingRuleService = $pricingRuleService;
    }

    public function index(Request $request)
    {
        $query = PricingRule::with(['product', 'client'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($productQuery) use ($search) {
                      $productQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->rule_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $pricingRules = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $products = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('pricing-rules.index', compact('pricingRules', 'products', 'clients'));
    }

    public function show(PricingRule $pricingRule)
    {
        $this->authorize('view', $pricingRule);

        $pricingRule->load(['product', 'client']);

        return view('pricing-rules.show', compact('pricingRule'));
    }

    public function create()
    {
        $this->authorize('create', PricingRule::class);

        $products = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('pricing-rules.create', compact('products', 'clients'));
    }

    public function store(StorePricingRuleRequest $request)
    {
        $this->authorize('create', PricingRule::class);

        $pricingRule = $this->pricingRuleService->create($request->validated());

        return redirect()
            ->route('pricing-rules.show', $pricingRule)
            ->with('success', 'Pricing rule created successfully.');
    }

    public function edit(PricingRule $pricingRule)
    {
        $this->authorize('update', $pricingRule);

        $products = Product::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        return view('pricing-rules.edit', compact('pricingRule', 'products', 'clients'));
    }

    public function update(UpdatePricingRuleRequest $request, PricingRule $pricingRule)
    {
        $this->authorize('update', $pricingRule);

        $pricingRule = $this->pricingRuleService->update($pricingRule, $request->validated());

        return redirect()
            ->route('pricing-rules.show', $pricingRule)
            ->with('success', 'Pricing rule updated successfully.');
    }

    public function destroy(PricingRule $pricingRule)
    {
        $this->authorize('delete', $pricingRule);

        $this->pricingRuleService->delete($pricingRule);

        return redirect()
            ->route('pricing-rules.index')
            ->with('success', 'Pricing rule deleted successfully.');
    }

    public function testRule(Request $request, PricingRule $pricingRule)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'client_id' => 'nullable|exists:clients,id',
            'quantity' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
        ]);

        $result = $this->pricingRuleService->testRule(
            $pricingRule,
            $request->get('product_id'),
            $request->get('client_id'),
            $request->get('quantity'),
            $request->get('base_price')
        );

        return response()->json($result);
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', PricingRule::class);

        $request->validate([
            'rule_ids' => 'required|array',
            'rule_ids.*' => 'exists:pricing_rules,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        $rules = PricingRule::whereIn('id', $request->rule_ids)
            ->where('company_id', auth()->user()->company_id)
            ->get();

        foreach ($rules as $rule) {
            switch ($request->action) {
                case 'activate':
                    $rule->update(['is_active' => true]);
                    break;
                case 'deactivate':
                    $rule->update(['is_active' => false]);
                    break;
                case 'delete':
                    $rule->delete();
                    break;
            }
        }

        return redirect()
            ->route('pricing-rules.index')
            ->with('success', 'Bulk action completed successfully.');
    }
}