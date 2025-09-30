<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $vendors = collect(); // TODO: Load from vendors table

        $activeVendors = $vendors->where('is_active', true);
        $totalSpend = 0; // TODO: Calculate total vendor spend

        return view('financial.vendors.index', compact('vendors', 'activeVendors', 'totalSpend'));
    }

    public function create(): View
    {
        $vendorTypes = ['supplier', 'contractor', 'service_provider', 'consultant'];
        $paymentTerms = ['net_15', 'net_30', 'net_45', 'net_60', 'due_on_receipt'];

        return view('financial.vendors.create', compact('vendorTypes', 'paymentTerms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:50|unique:vendors,vendor_code',
            'type' => 'required|in:supplier,contractor,service_provider,consultant',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'required|in:net_15,net_30,net_45,net_60,due_on_receipt',
            'bank_account' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // TODO: Create vendor

        return redirect()->route('financial.vendors.index')
            ->with('success', 'Vendor created successfully');
    }

    public function show($id): View
    {
        // TODO: Load vendor details
        $vendor = null;
        $purchases = collect();
        $openPOs = collect();
        $paymentHistory = collect();

        return view('financial.vendors.show', compact('vendor', 'purchases', 'openPOs', 'paymentHistory'));
    }

    public function edit($id): View
    {
        // TODO: Load vendor for editing
        $vendor = null;
        $vendorTypes = ['supplier', 'contractor', 'service_provider', 'consultant'];
        $paymentTerms = ['net_15', 'net_30', 'net_45', 'net_60', 'due_on_receipt'];

        return view('financial.vendors.edit', compact('vendor', 'vendorTypes', 'paymentTerms'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:50|unique:vendors,vendor_code,'.$id,
            'type' => 'required|in:supplier,contractor,service_provider,consultant',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'required|in:net_15,net_30,net_45,net_60,due_on_receipt',
            'bank_account' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // TODO: Update vendor

        return redirect()->route('financial.vendors.show', $id)
            ->with('success', 'Vendor updated successfully');
    }

    public function destroy($id)
    {
        // TODO: Check if vendor has active POs or unpaid bills
        // TODO: Soft delete vendor

        return redirect()->route('financial.vendors.index')
            ->with('success', 'Vendor deleted successfully');
    }
}
