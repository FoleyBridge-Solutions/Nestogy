<?php

namespace App\Domains\Marketing\Controllers;

use App\Http\Controllers\Controller;

use App\Domains\Marketing\Models\MarketingCampaign;
use App\Domains\Marketing\Models\CampaignSequence;
use App\Domains\Marketing\Models\CampaignEnrollment;
use App\Domains\Marketing\Services\CampaignEmailService;
use App\Domains\Lead\Models\Lead;
use App\Domains\Core\Controllers\BaseResourceController;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CampaignController extends BaseResourceController
{
    protected CampaignEmailService $campaignEmailService;

    public function __construct(CampaignEmailService $campaignEmailService)
    {
        $this->campaignEmailService = $campaignEmailService;
    }

    protected function initializeController(): void
    {
        $this->service = app(\App\Domains\Marketing\Services\CampaignService::class);
        $this->resourceName = 'campaign';
        $this->viewPath = 'marketing.campaigns';
        $this->routePrefix = 'marketing.campaigns';
    }

    protected function getModelClass(): string
    {
        return MarketingCampaign::class;
    }

    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = MarketingCampaign::where('company_id', auth()->user()->company_id)
            ->with(['createdBy']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by_user_id', $request->created_by);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Apply sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        if (in_array($sortField, ['created_at', 'start_date', 'total_recipients', 'total_converted'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $campaigns = $query->paginate($request->get('per_page', 25));

        if ($request->expectsJson()) {
            return response()->json([
                'campaigns' => $campaigns,
                'filters' => [
                    'statuses' => MarketingCampaign::getStatuses(),
                    'types' => MarketingCampaign::getTypes(),
                ]
            ]);
        }

        return view('marketing.campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): View
    {
        return view('marketing.campaigns.create');
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:email,nurture,drip,event,webinar,content',
            'settings' => 'nullable|array',
            'target_criteria' => 'nullable|array',
            'auto_enroll' => 'boolean',
            'start_date' => 'nullable|date|after:now',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by_user_id'] = auth()->id();
        $validated['status'] = MarketingCampaign::STATUS_DRAFT;

        $campaign = MarketingCampaign::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Campaign created successfully',
                'campaign' => $campaign->load(['createdBy'])
            ], 201);
        }

        return redirect()->route('marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully');
    }

    /**
     * Display the specified campaign.
     */
    public function show($id): View|JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        $campaign->load([
            'createdBy',
            'sequences' => function($query) {
                $query->orderBy('step_number');
            },
            'enrollments' => function($query) {
                $query->with(['lead', 'contact'])->latest();
            }
        ]);

        // Get campaign metrics
        $metrics = $this->campaignEmailService->getCampaignMetrics($campaign);

        if (request()->expectsJson()) {
            return response()->json([
                'campaign' => $campaign,
                'metrics' => $metrics
            ]);
        }

        return view('marketing.campaigns.show', compact('campaign', 'metrics'));
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit($id): View
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        if (!$campaign->canBeEdited()) {
            return redirect()->route('marketing.campaigns.show', $campaign)
                ->with('error', 'Campaign cannot be edited in current status');
        }

        return view('marketing.campaigns.edit', compact('campaign'));
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        if (!$campaign->canBeEdited()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Campaign cannot be edited in current status'], 400);
            }
            return back()->with('error', 'Campaign cannot be edited in current status');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:email,nurture,drip,event,webinar,content',
            'settings' => 'nullable|array',
            'target_criteria' => 'nullable|array',
            'auto_enroll' => 'boolean',
            'start_date' => 'nullable|date|after:now',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $campaign->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Campaign updated successfully',
                'campaign' => $campaign->load(['createdBy'])
            ]);
        }

        return redirect()->route('marketing.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully');
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy($id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('delete', $campaign);

        // Can only delete draft campaigns
        if ($campaign->status !== MarketingCampaign::STATUS_DRAFT) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Only draft campaigns can be deleted'], 400);
            }
            return back()->with('error', 'Only draft campaigns can be deleted');
        }

        $campaign->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Campaign deleted successfully']);
        }

        return redirect()->route('marketing.campaigns.index')
            ->with('success', 'Campaign deleted successfully');
    }

    /**
     * Start the campaign.
     */
    public function start(MarketingCampaign $campaign): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $campaign);

        try {
            $campaign->start();

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Campaign started successfully',
                    'campaign' => $campaign->refresh()
                ]);
            }

            return back()->with('success', 'Campaign started successfully');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause the campaign.
     */
    public function pause($id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        try {
            $campaign->pause();

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Campaign paused successfully',
                    'campaign' => $campaign->refresh()
                ]);
            }

            return back()->with('success', 'Campaign paused successfully');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the campaign.
     */
    public function complete($id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $campaign->complete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Campaign completed successfully',
                'campaign' => $campaign->refresh()
            ]);
        }

        return back()->with('success', 'Campaign completed successfully');
    }

    /**
     * Clone the campaign.
     */
    public function clone($id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        $clonedCampaign = $campaign->replicate();
        $clonedCampaign->name = $campaign->name . ' (Copy)';
        $clonedCampaign->status = MarketingCampaign::STATUS_DRAFT;
        $clonedCampaign->created_by_user_id = auth()->id();
        $clonedCampaign->start_date = null;
        $clonedCampaign->end_date = null;
        $clonedCampaign->save();

        // Clone sequences
        foreach ($campaign->sequences as $sequence) {
            $sequence->cloneFor($clonedCampaign);
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Campaign cloned successfully',
                'campaign' => $clonedCampaign->load(['createdBy'])
            ]);
        }

        return redirect()->route('marketing.campaigns.show', $clonedCampaign)
            ->with('success', 'Campaign cloned successfully');
    }

    /**
     * Add sequences to campaign.
     */
    public function addSequence(Request $request, $id): JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        if (!$campaign->canBeEdited()) {
            return response()->json(['error' => 'Campaign cannot be edited in current status'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'delay_days' => 'required|integer|min:0|max:365',
            'delay_hours' => 'required|integer|min:0|max:23',
            'subject_line' => 'required|string|max:255',
            'email_template' => 'required|string',
            'email_text' => 'nullable|string',
            'send_time' => 'nullable|date_format:H:i',
            'send_days' => 'nullable|array',
            'send_days.*' => 'integer|min:1|max:7',
        ]);

        $stepNumber = $campaign->sequences()->max('step_number') + 1;
        $validated['step_number'] = $stepNumber;

        $sequence = $campaign->sequences()->create($validated);

        return response()->json([
            'message' => 'Sequence added successfully',
            'sequence' => $sequence
        ]);
    }

    /**
     * Enroll leads in campaign.
     */
    public function enrollLeads(Request $request, $id): JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        $companyId = auth()->user()->company_id;
        $leads = Lead::where('company_id', $companyId)
            ->whereIn('id', $validated['lead_ids'])
            ->get();

        $enrolledCount = 0;
        foreach ($leads as $lead) {
            $enrollment = $campaign->enrollLead($lead);
            if ($enrollment->wasRecentlyCreated) {
                $enrolledCount++;
            }
        }

        $campaign->updateMetrics();

        return response()->json([
            'message' => "Enrolled {$enrolledCount} leads in campaign",
            'enrolled_count' => $enrolledCount
        ]);
    }

    /**
     * Enroll contacts in campaign.
     */
    public function enrollContacts(Request $request, $id): JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $companyId = auth()->user()->company_id;
        $contacts = Contact::where('company_id', $companyId)
            ->whereIn('id', $validated['contact_ids'])
            ->get();

        $enrolledCount = 0;
        foreach ($contacts as $contact) {
            $enrollment = $campaign->enrollContact($contact);
            if ($enrollment->wasRecentlyCreated) {
                $enrolledCount++;
            }
        }

        $campaign->updateMetrics();

        return response()->json([
            'message' => "Enrolled {$enrolledCount} contacts in campaign",
            'enrolled_count' => $enrolledCount
        ]);
    }

    /**
     * Get campaign analytics.
     */
    public function analytics($id): JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $this->authorize('view', $campaign);

        $metrics = $this->campaignEmailService->getCampaignMetrics($campaign);
        $performance = $campaign->getPerformanceSummary();

        // Get enrollment stats
        $enrollmentStats = [
            'total_enrollments' => $campaign->enrollments()->count(),
            'active_enrollments' => $campaign->activeEnrollments()->count(),
            'completed_enrollments' => $campaign->completedEnrollments()->count(),
            'unsubscribed_enrollments' => $campaign->enrollments()->unsubscribed()->count(),
        ];

        // Get conversion data over time
        $conversionData = CampaignEnrollment::where('campaign_id', $campaign->id)
            ->where('converted', true)
            ->selectRaw('converted_at::date as date, COUNT(*) as conversions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'metrics' => $metrics,
            'performance' => $performance,
            'enrollment_stats' => $enrollmentStats,
            'conversion_data' => $conversionData,
        ]);
    }

    /**
     * Send test email for sequence.
     */
    public function sendTestEmail(Request $request, $id, $sequenceId): JsonResponse
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $sequence = CampaignSequence::findOrFail($sequenceId);
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'test_email' => 'required|email'
        ]);

        $sent = $this->campaignEmailService->sendTestEmail($sequence, $validated['test_email']);

        if ($sent) {
            return response()->json(['message' => 'Test email sent successfully']);
        }

        return response()->json(['error' => 'Failed to send test email'], 500);
    }
}