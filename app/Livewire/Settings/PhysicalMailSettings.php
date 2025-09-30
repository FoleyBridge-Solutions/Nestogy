<?php

namespace App\Livewire\Settings;

use App\Domains\PhysicalMail\Services\PostGridClient;
use App\Models\PhysicalMailSettings as PhysicalMailSettingsModel;
use Livewire\Component;

class PhysicalMailSettings extends Component
{
    public PhysicalMailSettingsModel $settings;

    // Form fields
    public $testKey;

    public $liveKey;

    public $webhookSecret;

    public $forceTestMode = false;

    public $fromCompanyName;

    public $fromContactName;

    public $fromAddressLine1;

    public $fromAddressLine2;

    public $fromCity;

    public $fromState;

    public $fromZip;

    public $defaultMailingClass = 'first_class';

    public $defaultColorPrinting = true;

    public $defaultDoubleSided = false;

    // State
    public $testConnectionResult = null;

    public $isTesting = false;

    public $hasChanges = false;

    protected $rules = [
        'testKey' => 'nullable|string|starts_with:test_sk_',
        'liveKey' => 'nullable|string|starts_with:live_sk_',
        'webhookSecret' => 'nullable|string',
        'fromCompanyName' => 'required|string|max:100',
        'fromContactName' => 'nullable|string|max:100',
        'fromAddressLine1' => 'required|string|max:100',
        'fromAddressLine2' => 'nullable|string|max:100',
        'fromCity' => 'required|string|max:50',
        'fromState' => 'required|string|size:2',
        'fromZip' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
        'defaultMailingClass' => 'required|in:first_class,standard_class',
        'defaultColorPrinting' => 'boolean',
        'defaultDoubleSided' => 'boolean',
        'forceTestMode' => 'boolean',
    ];

    protected $messages = [
        'testKey.starts_with' => 'Test key must start with "test_sk_"',
        'liveKey.starts_with' => 'Live key must start with "live_sk_"',
        'fromState.size' => 'State must be a 2-letter code (e.g., NY)',
        'fromZip.regex' => 'ZIP code must be in format 12345 or 12345-6789',
    ];

    public function mount()
    {
        // Get or create settings for the current company
        $this->settings = PhysicalMailSettingsModel::forCompany();

        if (! $this->settings) {
            abort(403, 'No company selected');
        }

        // Load current values
        $this->testKey = $this->settings->test_key;
        $this->liveKey = $this->settings->live_key;
        $this->webhookSecret = $this->settings->webhook_secret;
        $this->forceTestMode = $this->settings->force_test_mode;

        $this->fromCompanyName = $this->settings->from_company_name ?: auth()->user()->company->name;
        $this->fromContactName = $this->settings->from_contact_name;
        $this->fromAddressLine1 = $this->settings->from_address_line1;
        $this->fromAddressLine2 = $this->settings->from_address_line2;
        $this->fromCity = $this->settings->from_city;
        $this->fromState = $this->settings->from_state;
        $this->fromZip = $this->settings->from_zip;

        $this->defaultMailingClass = $this->settings->default_mailing_class;
        $this->defaultColorPrinting = $this->settings->default_color_printing;
        $this->defaultDoubleSided = $this->settings->default_double_sided;
    }

    public function updated($property)
    {
        $this->hasChanges = true;
        $this->testConnectionResult = null;
    }

    public function testConnection()
    {
        $this->isTesting = true;
        $this->testConnectionResult = null;

        try {
            $apiKey = $this->shouldUseTestMode() ? $this->testKey : $this->liveKey;

            if (! $apiKey) {
                throw new \Exception('No API key configured for the current mode');
            }

            // Create a temporary PostGrid client with current settings
            $testClient = new PostGridClient(
                testMode: $this->shouldUseTestMode(),
                apiKey: $apiKey
            );

            // Try to list templates (lightweight API call)
            $response = $testClient->list('templates', ['limit' => 1]);

            $this->testConnectionResult = [
                'success' => true,
                'mode' => $this->shouldUseTestMode() ? 'test' : 'live',
                'message' => 'Connection successful!',
            ];

            // Update last connection test in database
            $this->settings->updateConnectionTest(true);

        } catch (\Exception $e) {
            $this->testConnectionResult = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $this->settings->updateConnectionTest(false, $e->getMessage());
        } finally {
            $this->isTesting = false;
        }
    }

    public function save()
    {
        $this->validate();

        // At least one API key must be provided
        if (! $this->testKey && ! $this->liveKey) {
            $this->addError('testKey', 'At least one API key (test or live) is required');

            return;
        }

        $this->settings->update([
            'test_key' => $this->testKey,
            'live_key' => $this->liveKey,
            'webhook_secret' => $this->webhookSecret,
            'force_test_mode' => $this->forceTestMode,
            'from_company_name' => $this->fromCompanyName,
            'from_contact_name' => $this->fromContactName,
            'from_address_line1' => $this->fromAddressLine1,
            'from_address_line2' => $this->fromAddressLine2,
            'from_city' => $this->fromCity,
            'from_state' => strtoupper($this->fromState),
            'from_zip' => $this->fromZip,
            'default_mailing_class' => $this->defaultMailingClass,
            'default_color_printing' => $this->defaultColorPrinting,
            'default_double_sided' => $this->defaultDoubleSided,
            'is_active' => true,
        ]);

        $this->hasChanges = false;
        $this->dispatch('saved');

        session()->flash('success', 'Physical mail settings saved successfully!');
    }

    protected function shouldUseTestMode(): bool
    {
        if (app()->environment('production')) {
            return $this->forceTestMode || empty($this->liveKey);
        }

        return true;
    }

    public function render()
    {
        return view('livewire.settings.physical-mail-settings', [
            'environment' => app()->environment(),
            'isTestMode' => $this->shouldUseTestMode(),
            'stats' => [
                'total' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('company_id', auth()->user()->company_id)->count(),
                'month' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('company_id', auth()->user()->company_id)
                    ->whereMonth('created_at', now()->month)->count(),
                'pending' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('company_id', auth()->user()->company_id)
                    ->whereIn('status', ['pending', 'processing'])->count(),
                'delivered' => \App\Domains\PhysicalMail\Models\PhysicalMailOrder::where('company_id', auth()->user()->company_id)
                    ->where('status', 'delivered')->count(),
            ],
        ]);
    }
}
