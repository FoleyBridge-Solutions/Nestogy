<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Location;
use App\Models\Contact;
use App\Models\Vendor;
use App\Models\Network;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AssetsExport;
use App\Imports\AssetsImport;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Asset::with(['client', 'location', 'contact', 'vendor'])
            ->forCompany()
            ->withoutArchived();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('serial', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhere('mac', 'like', "%{$search}%");
            });
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by contact
        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        // Sort
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $assets = $query->paginate(20)->withQueryString();

        // Get filter options
        $clients = Client::forCompany()->orderBy('name')->get();
        $locations = Location::forCompany()->orderBy('name')->get();
        $contacts = Contact::forCompany()->orderBy('name')->get();

        return view('assets.index', compact('assets', 'clients', 'locations', 'contacts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $clients = Client::forCompany()->orderBy('name')->get();
        $locations = Location::forCompany()->orderBy('name')->get();
        $contacts = Contact::forCompany()->orderBy('name')->get();
        $vendors = Vendor::forCompany()->orderBy('name')->get();
        $networks = Network::forCompany()->orderBy('name')->get();

        // Pre-select client if provided
        $selectedClientId = $request->get('client_id');

        return view('assets.create', compact('clients', 'locations', 'contacts', 'vendors', 'networks', 'selectedClientId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:' . implode(',', Asset::TYPES),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial' => 'nullable|string|max:255',
            'os' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:45',
            'nat_ip' => 'nullable|string|max:255',
            'mac' => 'nullable|string|max:17',
            'uri' => 'nullable|string|max:500',
            'uri_2' => 'nullable|string|max:500',
            'status' => 'nullable|in:' . implode(',', Asset::STATUSES),
            'purchase_date' => 'nullable|date',
            'warranty_expire' => 'nullable|date',
            'install_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id',
            'location_id' => 'nullable|exists:locations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'network_id' => 'nullable|exists:networks,id',
        ]);

        // Handle DHCP
        if ($request->has('dhcp') && $request->dhcp == 1) {
            $validated['ip'] = 'DHCP';
        }

        $validated['company_id'] = Auth::user()->company_id;
        $validated['status'] = $validated['status'] ?? 'Ready To Deploy';

        $asset = Asset::create($validated);

        // Handle file uploads if any
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('assets/' . $asset->id, 'private');
                $asset->files()->create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset)
    {
        $asset->load(['client', 'location', 'contact', 'vendor', 'network', 'tickets', 'logins', 'files']);
        $asset->touchAccessed();

        // Generate QR code
        $qrCode = QrCode::size(200)->generate($asset->qr_code_data);

        return view('assets.show', compact('asset', 'qrCode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Asset $asset)
    {
        $clients = Client::forCompany()->orderBy('name')->get();
        $locations = Location::forCompany()->orderBy('name')->get();
        $contacts = Contact::forCompany()->orderBy('name')->get();
        $vendors = Vendor::forCompany()->orderBy('name')->get();
        $networks = Network::forCompany()->orderBy('name')->get();

        return view('assets.edit', compact('asset', 'clients', 'locations', 'contacts', 'vendors', 'networks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:' . implode(',', Asset::TYPES),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial' => 'nullable|string|max:255',
            'os' => 'nullable|string|max:255',
            'ip' => 'nullable|string|max:45',
            'nat_ip' => 'nullable|string|max:255',
            'mac' => 'nullable|string|max:17',
            'uri' => 'nullable|string|max:500',
            'uri_2' => 'nullable|string|max:500',
            'status' => 'nullable|in:' . implode(',', Asset::STATUSES),
            'purchase_date' => 'nullable|date',
            'warranty_expire' => 'nullable|date',
            'install_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id',
            'location_id' => 'nullable|exists:locations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'network_id' => 'nullable|exists:networks,id',
        ]);

        // Handle DHCP
        if ($request->has('dhcp') && $request->dhcp == 1) {
            $validated['ip'] = 'DHCP';
        }

        $asset->update($validated);

        // Handle file uploads if any
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('assets/' . $asset->id, 'private');
                $asset->files()->create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    /**
     * Archive the specified resource.
     */
    public function archive(Asset $asset)
    {
        $asset->archive();

        return redirect()->route('assets.index')
            ->with('success', 'Asset archived successfully.');
    }

    /**
     * Restore the specified resource.
     */
    public function restore($id)
    {
        $asset = Asset::withArchived()->findOrFail($id);
        $asset->unarchive();

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset restored successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset)
    {
        // Check if user has permission to delete
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $asset->delete();

        return redirect()->route('assets.index')
            ->with('success', 'Asset deleted successfully.');
    }

    /**
     * Export assets to CSV.
     */
    public function export(Request $request)
    {
        $clientId = $request->get('client_id');
        $fileName = 'assets-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new AssetsExport($clientId), $fileName);
    }

    /**
     * Show import form.
     */
    public function importForm()
    {
        $clients = Client::forCompany()->orderBy('name')->get();
        
        return view('assets.import', compact('clients'));
    }

    /**
     * Import assets from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx',
            'client_id' => 'required|exists:clients,id',
        ]);

        try {
            Excel::import(new AssetsImport($request->client_id), $request->file('file'));
            
            return redirect()->route('assets.index')
                ->with('success', 'Assets imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing assets: ' . $e->getMessage());
        }
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="assets-import-template.csv"',
        ];

        $columns = ['Name', 'Description', 'Type', 'Make', 'Model', 'Serial', 'OS', 'Assigned To', 'Location'];
        
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk update assets.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
            'action' => 'required|in:update_location,update_contact,update_status,archive',
        ]);

        $assets = Asset::whereIn('id', $request->asset_ids)->get();

        switch ($request->action) {
            case 'update_location':
                $request->validate(['location_id' => 'required|exists:locations,id']);
                foreach ($assets as $asset) {
                    $asset->update(['location_id' => $request->location_id]);
                }
                $message = 'Location updated for selected assets.';
                break;

            case 'update_contact':
                $request->validate(['contact_id' => 'required|exists:contacts,id']);
                foreach ($assets as $asset) {
                    $asset->update(['contact_id' => $request->contact_id]);
                }
                $message = 'Contact updated for selected assets.';
                break;

            case 'update_status':
                $request->validate(['status' => 'required|in:' . implode(',', Asset::STATUSES)]);
                foreach ($assets as $asset) {
                    $asset->update(['status' => $request->status]);
                }
                $message = 'Status updated for selected assets.';
                break;

            case 'archive':
                foreach ($assets as $asset) {
                    $asset->archive();
                }
                $message = 'Selected assets archived.';
                break;
        }

        return back()->with('success', $message);
    }

    /**
     * Generate QR code for asset.
     */
    public function qrCode(Asset $asset)
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(10)
            ->generate($asset->qr_code_data);

        return response($qrCode)->header('Content-Type', 'image/png');
    }

    /**
     * Print asset label.
     */
    public function printLabel(Asset $asset)
    {
        $qrCode = QrCode::size(150)->generate($asset->qr_code_data);
        
        return view('assets.label', compact('asset', 'qrCode'));
    }

    /**
     * Check in/out asset.
     */
    public function checkInOut(Request $request, Asset $asset)
    {
        $request->validate([
            'action' => 'required|in:check_in,check_out',
            'contact_id' => 'required_if:action,check_out|exists:contacts,id',
            'notes' => 'nullable|string',
        ]);

        if ($request->action === 'check_out') {
            $asset->update([
                'contact_id' => $request->contact_id,
                'status' => 'Deployed',
            ]);
            $message = 'Asset checked out successfully.';
        } else {
            $asset->update([
                'contact_id' => null,
                'status' => 'Ready To Deploy',
            ]);
            $message = 'Asset checked in successfully.';
        }

        // Log the action
        activity()
            ->performedOn($asset)
            ->withProperties(['action' => $request->action, 'notes' => $request->notes])
            ->log($message);

        return back()->with('success', $message);
    }
}