<?php

namespace App\Livewire\Marketing;

use App\Domains\Marketing\Models\MarketingCampaign;
use Illuminate\Support\Carbon;
use Livewire\Component;

class CampaignCreate extends Component
{
    public $name = '';

    public $description = '';

    public $type = '';

    public $auto_enroll = false;

    public ?Carbon $start_date = null;

    public $start_time = '';

    public ?Carbon $end_date = null;

    public $end_time = '';

    public $min_score = 0;

    public $max_score = 100;

    public $target_statuses = ['new', 'contacted'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|in:email,nurture,drip,event,webinar,content',
        'auto_enroll' => 'boolean',
        'start_date' => 'nullable|date',
        'start_time' => 'nullable|date_format:H:i',
        'end_date' => 'nullable|date',
        'end_time' => 'nullable|date_format:H:i',
        'min_score' => 'nullable|integer|min:0|max:100',
        'max_score' => 'nullable|integer|min:0|max:100',
        'target_statuses' => 'nullable|array',
    ];

    public function mount()
    {
        $this->authorize('create', MarketingCampaign::class);
    }

    public function save()
    {
        $this->authorize('create', MarketingCampaign::class);

        $validated = $this->validate();

        $targetCriteria = [
            'min_score' => $validated['min_score'],
            'max_score' => $validated['max_score'],
            'statuses' => $validated['target_statuses'] ?? [],
        ];

        $startDateTime = null;
        if ($this->start_date) {
            $startDateTime = Carbon::parse($this->start_date);
            if ($this->start_time) {
                [$hours, $minutes] = explode(':', $this->start_time);
                $startDateTime->setTime((int) $hours, (int) $minutes);
            }
        }

        $endDateTime = null;
        if ($this->end_date) {
            $endDateTime = Carbon::parse($this->end_date);
            if ($this->end_time) {
                [$hours, $minutes] = explode(':', $this->end_time);
                $endDateTime->setTime((int) $hours, (int) $minutes);
            }
        }

        $campaignData = [
            'company_id' => auth()->user()->company_id,
            'created_by_user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'auto_enroll' => $validated['auto_enroll'],
            'start_date' => $startDateTime,
            'end_date' => $endDateTime,
            'target_criteria' => $targetCriteria,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ];

        $campaign = MarketingCampaign::create($campaignData);

        session()->flash('success', 'Campaign created successfully');

        return redirect()->route('marketing.campaigns.show', $campaign);
    }

    public function render()
    {
        return view('livewire.marketing.campaign-create', [
            'campaignTypes' => MarketingCampaign::getTypes(),
            'availableStatuses' => ['new', 'contacted', 'qualified', 'proposal', 'negotiation'],
        ]);
    }
}
