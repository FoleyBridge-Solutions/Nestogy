<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class DiscountController extends Controller
{
    public function index(Request $request): View
    {
        $discounts = collect(); // TODO: Load from discounts table
        
        $activeDiscounts = $discounts->where('is_active', true)
            ->where('valid_to', '>=', Carbon::now());
        $expiredDiscounts = $discounts->where('valid_to', '<', Carbon::now());
        
        return view('financial.discounts.index', compact(
            'discounts',
            'activeDiscounts',
            'expiredDiscounts'
        ));
    }

    public function create(): View
    {
        $discountTypes = ['percentage', 'fixed_amount', 'bogo', 'volume'];
        $applicableTypes = ['all', 'product', 'service', 'category', 'client'];
        
        return view('financial.discounts.create', compact('discountTypes', 'applicableTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:discounts,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount,bogo,volume',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_client' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'is_active' => 'boolean'
        ]);

        // TODO: Create discount
        
        return redirect()->route('financial.discounts.index')
            ->with('success', 'Discount created successfully');
    }

    public function show($id): View
    {
        // TODO: Load discount details
        $discount = null;
        $usageHistory = collect();
        $totalSavings = 0;
        
        return view('financial.discounts.show', compact('discount', 'usageHistory', 'totalSavings'));
    }

    public function edit($id): View
    {
        // TODO: Load discount for editing
        $discount = null;
        $discountTypes = ['percentage', 'fixed_amount', 'bogo', 'volume'];
        
        return view('financial.discounts.edit', compact('discount', 'discountTypes'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:discounts,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount,bogo,volume',
            'value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_client' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'is_active' => 'boolean'
        ]);

        // TODO: Update discount
        
        return redirect()->route('financial.discounts.show', $id)
            ->with('success', 'Discount updated successfully');
    }

    public function destroy($id)
    {
        // TODO: Delete discount (soft delete if in use)
        
        return redirect()->route('financial.discounts.index')
            ->with('success', 'Discount deleted successfully');
    }
}