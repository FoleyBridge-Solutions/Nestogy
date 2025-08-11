<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Display a listing of all services (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientService::with(['client', 'technician', 'backupTechnician'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply type filter
        if ($type = $request->get('service_type')) {
            $query->where('service_type', $type);
        }

        // Apply category filter
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply special filters
        if ($request->get('ending_soon')) {
            $query->endingSoon();
        }

        if ($request->get('due_for_renewal')) {
            $query->dueForRenewal();
        }

        if ($request->get('needs_review')) {
            $query->needingReview();
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply technician filter
        if ($technicianId = $request->get('assigned_technician')) {
            $query->where('assigned_technician', $technicianId);
        }

        $services = $query->orderBy('name')
                         ->paginate(20)
                         ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $technicians = User::where('company_id', auth()->user()->company_id)
                          ->orderBy('name')
                          ->get();

        $serviceTypes = ClientService::getServiceTypes();
        $serviceCategories = ClientService::getServiceCategories();
        $serviceStatuses = ClientService::getServiceStatuses();

        return view('clients.services.index', compact(
            'services', 
            'clients', 
            'technicians',
            'serviceTypes', 
            'serviceCategories',
            'serviceStatuses'
        ));
    }

    /**
     * Show the form for creating a new service
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $technicians = User::where('company_id', auth()->user()->company_id)
                          ->orderBy('name')
                          ->get();

        $selectedClientId = $request->get('client_id');
        $serviceTypes = ClientService::getServiceTypes();
        $serviceCategories = ClientService::getServiceCategories();
        $serviceStatuses = ClientService::getServiceStatuses();
        $billingCycles = ClientService::getBillingCycles();
        $serviceLevels = ClientService::getServiceLevels();
        $priorityLevels = ClientService::getPriorityLevels();

        return view('clients.services.create', compact(
            'clients', 
            'technicians',
            'selectedClientId', 
            'serviceTypes',
            'serviceCategories',
            'serviceStatuses',
            'billingCycles',
            'serviceLevels',
            'priorityLevels'
        ));
    }

    /**
     * Store a newly created service
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_type' => 'required|in:' . implode(',', array_keys(ClientService::getServiceTypes())),
            'category' => 'nullable|in:' . implode(',', array_keys(ClientService::getServiceCategories())),
            'status' => 'required|in:' . implode(',', array_keys(ClientService::getServiceStatuses())),
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'renewal_date' => 'nullable|date',
            'billing_cycle' => 'nullable|in:' . implode(',', array_keys(ClientService::getBillingCycles())),
            'monthly_cost' => 'nullable|numeric|min:0|max:999999.99',
            'setup_cost' => 'nullable|numeric|min:0|max:999999.99',
            'total_contract_value' => 'nullable|numeric|min:0|max:9999999.99',
            'currency' => 'nullable|string|size:3',
            'auto_renewal' => 'boolean',
            'contract_terms' => 'nullable|string',
            'sla_terms' => 'nullable|string',
            'service_level' => 'nullable|in:' . implode(',', array_keys(ClientService::getServiceLevels())),
            'priority_level' => 'nullable|in:' . implode(',', array_keys(ClientService::getPriorityLevels())),
            'assigned_technician' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'backup_technician' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'escalation_contact' => 'nullable|string|max:255',
            'service_hours' => 'nullable|string',
            'response_time' => 'nullable|string|max:100',
            'resolution_time' => 'nullable|string|max:100',
            'availability_target' => 'nullable|string|max:100',
            'performance_metrics' => 'nullable|string',
            'monitoring_enabled' => 'boolean',
            'backup_schedule' => 'nullable|string',
            'maintenance_schedule' => 'nullable|string',
            'last_review_date' => 'nullable|date|before_or_equal:today',
            'next_review_date' => 'nullable|date',
            'client_satisfaction' => 'nullable|integer|min:1|max:10',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $serviceData = $request->all();
        
        // Process array fields
        if ($request->service_hours) {
            $serviceData['service_hours'] = json_decode($request->service_hours, true) ?: [];
        }
        
        if ($request->performance_metrics) {
            $serviceData['performance_metrics'] = json_decode($request->performance_metrics, true) ?: [];
        }
        
        if ($request->tags) {
            $serviceData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $service = new ClientService($serviceData);
        $service->company_id = auth()->user()->company_id;
        $service->save();

        return redirect()->route('clients.services.standalone.index')
                        ->with('success', 'Service created successfully.');
    }

    /**
     * Display the specified service
     */
    public function show(ClientService $service)
    {
        $this->authorize('view', $service);

        $service->load('client', 'technician', 'backupTechnician');

        return view('clients.services.show', compact('service'));
    }

    /**
     * Show the form for editing the specified service
     */
    public function edit(ClientService $service)
    {
        $this->authorize('update', $service);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $technicians = User::where('company_id', auth()->user()->company_id)
                          ->orderBy('name')
                          ->get();

        $serviceTypes = ClientService::getServiceTypes();
        $serviceCategories = ClientService::getServiceCategories();
        $serviceStatuses = ClientService::getServiceStatuses();
        $billingCycles = ClientService::getBillingCycles();
        $serviceLevels = ClientService::getServiceLevels();
        $priorityLevels = ClientService::getPriorityLevels();

        return view('clients.services.edit', compact(
            'service',
            'clients', 
            'technicians',
            'serviceTypes',
            'serviceCategories',
            'serviceStatuses',
            'billingCycles',
            'serviceLevels',
            'priorityLevels'
        ));
    }

    /**
     * Update the specified service
     */
    public function update(Request $request, ClientService $service)
    {
        $this->authorize('update', $service);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_type' => 'required|in:' . implode(',', array_keys(ClientService::getServiceTypes())),
            'category' => 'nullable|in:' . implode(',', array_keys(ClientService::getServiceCategories())),
            'status' => 'required|in:' . implode(',', array_keys(ClientService::getServiceStatuses())),
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'renewal_date' => 'nullable|date',
            'billing_cycle' => 'nullable|in:' . implode(',', array_keys(ClientService::getBillingCycles())),
            'monthly_cost' => 'nullable|numeric|min:0|max:999999.99',
            'setup_cost' => 'nullable|numeric|min:0|max:999999.99',
            'total_contract_value' => 'nullable|numeric|min:0|max:9999999.99',
            'currency' => 'nullable|string|size:3',
            'auto_renewal' => 'boolean',
            'contract_terms' => 'nullable|string',
            'sla_terms' => 'nullable|string',
            'service_level' => 'nullable|in:' . implode(',', array_keys(ClientService::getServiceLevels())),
            'priority_level' => 'nullable|in:' . implode(',', array_keys(ClientService::getPriorityLevels())),
            'assigned_technician' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'backup_technician' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'escalation_contact' => 'nullable|string|max:255',
            'service_hours' => 'nullable|string',
            'response_time' => 'nullable|string|max:100',
            'resolution_time' => 'nullable|string|max:100',
            'availability_target' => 'nullable|string|max:100',
            'performance_metrics' => 'nullable|string',
            'monitoring_enabled' => 'boolean',
            'backup_schedule' => 'nullable|string',
            'maintenance_schedule' => 'nullable|string',
            'last_review_date' => 'nullable|date|before_or_equal:today',
            'next_review_date' => 'nullable|date',
            'client_satisfaction' => 'nullable|integer|min:1|max:10',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $serviceData = $request->all();
        
        // Process array fields
        if ($request->service_hours) {
            $serviceData['service_hours'] = json_decode($request->service_hours, true) ?: [];
        }
        
        if ($request->performance_metrics) {
            $serviceData['performance_metrics'] = json_decode($request->performance_metrics, true) ?: [];
        }
        
        if ($request->tags) {
            $serviceData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $service->fill($serviceData);
        $service->save();

        return redirect()->route('clients.services.standalone.index')
                        ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified service
     */
    public function destroy(ClientService $service)
    {
        $this->authorize('delete', $service);

        $service->delete();

        return redirect()->route('clients.services.standalone.index')
                        ->with('success', 'Service deleted successfully.');
    }

    /**
     * Export services to CSV
     */
    public function export(Request $request)
    {
        $query = ClientService::with(['client', 'technician'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('service_type')) {
            $query->where('service_type', $type);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $services = $query->orderBy('name')->get();

        $filename = 'services_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($services) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Service Name',
                'Client Name',
                'Type',
                'Category',
                'Status',
                'Start Date',
                'End Date',
                'Monthly Cost',
                'Annual Revenue',
                'Assigned Technician',
                'Service Level',
                'Priority',
                'Last Review',
                'Client Satisfaction'
            ]);

            // CSV data
            foreach ($services as $service) {
                fputcsv($file, [
                    $service->name,
                    $service->client->display_name,
                    $service->service_type,
                    $service->category,
                    $service->status_label,
                    $service->start_date ? $service->start_date->format('Y-m-d') : '',
                    $service->end_date ? $service->end_date->format('Y-m-d') : '',
                    $service->monthly_cost,
                    $service->annual_revenue,
                    $service->technician ? $service->technician->name : '',
                    $service->service_level,
                    $service->priority_level,
                    $service->last_review_date ? $service->last_review_date->format('Y-m-d') : '',
                    $service->client_satisfaction,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}