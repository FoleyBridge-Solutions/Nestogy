<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Product\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $services = Service::with(['category', 'pricing'])
            ->when($request->get('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->get('type'), function ($query, $type) {
                $query->where('service_type', $type);
            })
            ->orderBy('name')
            ->paginate(20);

        $serviceTypes = ['one-time', 'recurring', 'project-based', 'hourly'];

        return view('financial.services.index', compact('services', 'serviceTypes'));
    }

    public function create(): View
    {
        $categories = collect(); // TODO: Load service categories
        $billingCycles = ['monthly', 'quarterly', 'semi-annual', 'annual'];

        return view('financial.services.create', compact('categories', 'billingCycles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:services,code',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:service_categories,id',
            'service_type' => 'required|in:one-time,recurring,project-based,hourly',
            'billing_cycle' => 'nullable|in:monthly,quarterly,semi-annual,annual',
            'unit_price' => 'required|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'minimum_commitment' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        Service::create($validated);

        return redirect()->route('financial.services.index')
            ->with('success', 'Service created successfully');
    }

    public function show(Service $service): View
    {
        $service->load(['category', 'pricing', 'contracts']);

        $stats = [
            'active_contracts' => $service->contracts()->active()->count(),
            'monthly_revenue' => 0, // TODO: Calculate MRR
            'total_revenue' => 0, // TODO: Calculate lifetime revenue
        ];

        return view('financial.services.show', compact('service', 'stats'));
    }

    public function edit(Service $service): View
    {
        $categories = collect(); // TODO: Load service categories
        $billingCycles = ['monthly', 'quarterly', 'semi-annual', 'annual'];

        return view('financial.services.edit', compact('service', 'categories', 'billingCycles'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:services,code,'.$service->id,
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:service_categories,id',
            'service_type' => 'required|in:one-time,recurring,project-based,hourly',
            'billing_cycle' => 'nullable|in:monthly,quarterly,semi-annual,annual',
            'unit_price' => 'required|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'minimum_commitment' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);

        return redirect()->route('financial.services.show', $service)
            ->with('success', 'Service updated successfully');
    }

    public function destroy(Service $service)
    {
        // TODO: Check if service has active contracts

        $service->delete();

        return redirect()->route('financial.services.index')
            ->with('success', 'Service deleted successfully');
    }
}
