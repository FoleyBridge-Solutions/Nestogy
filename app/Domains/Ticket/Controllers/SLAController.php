<?php

namespace App\Domains\Ticket\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Ticket\Models\SLA;
use App\Domains\Ticket\Services\SLAService;
use App\Http\Requests\SLARequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * SLA Controller for Domain-Driven Design
 * 
 * Handles SLA management including CRUD operations and client assignments.
 */
class SLAController extends Controller
{
    protected SLAService $slaService;

    public function __construct(SLAService $slaService)
    {
        $this->slaService = $slaService;
        
        // Apply middleware for permissions
        $this->middleware('auth');
        $this->middleware('can:manage_slas');
    }

    /**
     * Display SLA management page
     */
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;
        
        $slas = $this->slaService->getActiveSLAs($companyId);
        
        return view('settings.slas.index', [
            'slas' => $slas,
            'defaultSLA' => $this->slaService->getDefaultSLA($companyId),
        ]);
    }

    /**
     * Show form for creating new SLA
     */
    public function create(): View
    {
        return view('settings.slas.create', [
            'priorityLevels' => SLA::getPriorityLevels(),
            'coverageTypes' => SLA::getCoverageTypes(),
            'businessDays' => SLA::getBusinessDays(),
            'timezones' => $this->getAvailableTimezones(),
        ]);
    }

    /**
     * Store a newly created SLA
     */
    public function store(SLARequest $request): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        
        // Validate SLA data
        $validationErrors = $this->slaService->validateSLAData($request->validated());
        
        if (!empty($validationErrors)) {
            return back()
                ->withErrors($validationErrors)
                ->withInput();
        }
        
        try {
            $sla = $this->slaService->create($companyId, $request->validated());
            
            return redirect()
                ->route('settings.slas.index')
                ->with('success', 'SLA created successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->withError('Failed to create SLA: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified SLA
     */
    public function show(SLA $sla): View
    {
        $this->authorize('view', $sla);
        
        return view('settings.slas.show', [
            'sla' => $sla,
            'clientsCount' => $sla->clients()->count(),
            'metrics' => $this->slaService->getSLAMetrics(
                $sla->company_id,
                now()->subMonth(),
                now()
            ),
        ]);
    }

    /**
     * Show form for editing SLA
     */
    public function edit(SLA $sla, Request $request)
    {
        $this->authorize('update', $sla);
        
        // If AJAX request, return JSON data for modal
        if ($request->ajax()) {
            return response()->json($sla->toArray());
        }
        
        return view('settings.slas.edit', [
            'sla' => $sla,
            'priorityLevels' => SLA::getPriorityLevels(),
            'coverageTypes' => SLA::getCoverageTypes(),
            'businessDays' => SLA::getBusinessDays(),
            'timezones' => $this->getAvailableTimezones(),
        ]);
    }

    /**
     * Update the specified SLA
     */
    public function update(SLARequest $request, SLA $sla): RedirectResponse
    {
        $this->authorize('update', $sla);
        
        // Validate SLA data
        $validationErrors = $this->slaService->validateSLAData($request->validated());
        
        if (!empty($validationErrors)) {
            return back()
                ->withErrors($validationErrors)
                ->withInput();
        }
        
        try {
            $this->slaService->update($sla, $request->validated());
            
            return redirect()
                ->route('settings.slas.index')
                ->with('success', 'SLA updated successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->withError('Failed to update SLA: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified SLA
     */
    public function destroy(SLA $sla): RedirectResponse
    {
        $this->authorize('delete', $sla);
        
        try {
            $this->slaService->delete($sla);
            
            return redirect()
                ->route('settings.slas.index')
                ->with('success', 'SLA deleted successfully. Affected clients have been reassigned to the default SLA.');
                
        } catch (\Exception $e) {
            return back()
                ->withError('Failed to delete SLA: ' . $e->getMessage());
        }
    }

    /**
     * Toggle SLA active status
     */
    public function toggleActive(SLA $sla): RedirectResponse
    {
        $this->authorize('update', $sla);
        
        try {
            $sla->update(['is_active' => !$sla->is_active]);
            
            $status = $sla->is_active ? 'activated' : 'deactivated';
            
            return back()
                ->with('success', "SLA {$status} successfully.");
                
        } catch (\Exception $e) {
            return back()
                ->withError('Failed to toggle SLA status: ' . $e->getMessage());
        }
    }

    /**
     * Set SLA as default for company
     */
    public function setDefault(SLA $sla): RedirectResponse
    {
        $this->authorize('update', $sla);
        
        try {
            $this->slaService->update($sla, ['is_default' => true]);
            
            return back()
                ->with('success', 'SLA set as default successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->withError('Failed to set default SLA: ' . $e->getMessage());
        }
    }

    /**
     * Get SLA data for API/AJAX requests
     */
    public function api(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        $slas = SLA::where('company_id', $companyId)
                   ->active()
                   ->effectiveOn()
                   ->get(['id', 'name', 'description', 'is_default']);
                   
        return response()->json($slas);
    }

    /**
     * Show client SLA assignments page
     */
    public function clientAssignments(): View
    {
        $companyId = auth()->user()->company_id;
        
        $clients = Client::where('company_id', $companyId)
                         ->with('sla')
                         ->paginate(20);
                         
        $slas = $this->slaService->getActiveSLAs($companyId);
        $defaultSLA = $this->slaService->getDefaultSLA($companyId);
        
        return view('settings.slas.client-assignments', [
            'clients' => $clients,
            'slas' => $slas,
            'defaultSLA' => $defaultSLA,
        ]);
    }

    /**
     * Get available timezones
     */
    protected function getAvailableTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (UTC-5/-4)',
            'America/Chicago' => 'Central Time (UTC-6/-5)',
            'America/Denver' => 'Mountain Time (UTC-7/-6)',
            'America/Los_Angeles' => 'Pacific Time (UTC-8/-7)',
            'Europe/London' => 'London (UTC+0/+1)',
            'Europe/Paris' => 'Paris (UTC+1/+2)',
            'Europe/Berlin' => 'Berlin (UTC+1/+2)',
            'Asia/Tokyo' => 'Tokyo (UTC+9)',
            'Asia/Shanghai' => 'Shanghai (UTC+8)',
            'Asia/Kolkata' => 'Mumbai (UTC+5:30)',
            'Australia/Sydney' => 'Sydney (UTC+10/+11)',
            'Australia/Melbourne' => 'Melbourne (UTC+10/+11)',
        ];
    }
}