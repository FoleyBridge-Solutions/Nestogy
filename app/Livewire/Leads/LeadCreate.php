<?php

namespace App\Livewire\Leads;

use App\Domains\Core\Models\User;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadSource;
use App\Domains\Lead\Services\LeadScoringService;
use Livewire\Component;

class LeadCreate extends Component
{
    public $first_name = '';

    public $last_name = '';

    public $email = '';

    public $phone = '';

    public $company_name = '';

    public $title = '';

    public $website = '';

    public $address = '';

    public $city = '';

    public $state = '';

    public $zip_code = '';

    public $country = '';

    public $lead_source_id = null;

    public $assigned_user_id = null;

    public $priority = 'medium';

    public $industry = '';

    public $company_size = null;

    public $estimated_value = null;

    public $notes = '';

    public $utm_source = '';

    public $utm_medium = '';

    public $utm_campaign = '';

    public $utm_content = '';

    public $utm_term = '';

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:leads,email',
        'phone' => 'nullable|string|max:20',
        'company_name' => 'nullable|string|max:255',
        'title' => 'nullable|string|max:255',
        'website' => 'nullable|url',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'state' => 'nullable|string|max:100',
        'zip_code' => 'nullable|string|max:20',
        'country' => 'nullable|string|max:100',
        'lead_source_id' => 'nullable|exists:lead_sources,id',
        'assigned_user_id' => 'nullable|exists:users,id',
        'priority' => 'required|in:low,medium,high,urgent',
        'industry' => 'nullable|string|max:100',
        'company_size' => 'nullable|integer|min:1',
        'estimated_value' => 'nullable|numeric|min:0',
        'notes' => 'nullable|string',
        'utm_source' => 'nullable|string|max:100',
        'utm_medium' => 'nullable|string|max:100',
        'utm_campaign' => 'nullable|string|max:100',
        'utm_content' => 'nullable|string|max:100',
        'utm_term' => 'nullable|string|max:100',
    ];

    public function mount()
    {
        $this->authorize('create', Lead::class);
    }

    public function save()
    {
        $this->authorize('create', Lead::class);

        $validated = $this->validate();

        $validated['company_id'] = auth()->user()->company_id;
        $validated['status'] = Lead::STATUS_NEW;

        $lead = Lead::create($validated);

        $leadScoringService = app(LeadScoringService::class);
        $leadScoringService->updateLeadScore($lead);

        session()->flash('success', 'Lead created successfully');

        return redirect()->route('leads.index');
    }

    public function render()
    {
        $leadSources = LeadSource::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $users = User::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $industries = $this->getIndustries();
        $priorities = Lead::getPriorities();

        return view('livewire.leads.lead-create', [
            'leadSources' => $leadSources,
            'users' => $users,
            'industries' => $industries,
            'priorities' => $priorities,
        ]);
    }

    protected function getIndustries(): array
    {
        return [
            'Technology' => 'Technology',
            'Healthcare' => 'Healthcare',
            'Finance' => 'Finance',
            'Manufacturing' => 'Manufacturing',
            'Retail' => 'Retail',
            'Education' => 'Education',
            'Real Estate' => 'Real Estate',
            'Hospitality' => 'Hospitality',
            'Construction' => 'Construction',
            'Legal' => 'Legal',
            'Consulting' => 'Consulting',
            'Marketing' => 'Marketing',
            'Transportation' => 'Transportation',
            'Agriculture' => 'Agriculture',
            'Energy' => 'Energy',
            'Telecommunications' => 'Telecommunications',
            'Media & Entertainment' => 'Media & Entertainment',
            'Non-Profit' => 'Non-Profit',
            'Government' => 'Government',
            'Other' => 'Other',
        ];
    }
}
