<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditClient extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Client $client;

    // Tab management
    public string $activeTab = 'basic';

    // Basic Information
    public string $name = '';

    public string $type = '';

    public string $company_name = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $tax_id_number = '';

    public string $referral = '';

    public bool $lead = false;

    // Address Information
    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip_code = '';

    public string $country = '';

    // Contact Information
    public string $billing_contact = '';

    public string $technical_contact = '';

    // Billing Information
    public string $status = 'active';

    public ?string $hourly_rate = '';

    public ?string $rate = '';

    public string $currency_code = 'USD';

    public $net_terms = 30;

    // Custom Rate Settings
    public bool $use_custom_rates = false;

    public string $custom_rate_calculation_method = 'multipliers';

    public ?string $custom_standard_rate = '';

    public ?string $custom_after_hours_rate = '';

    public ?string $custom_emergency_rate = '';

    public ?string $custom_weekend_rate = '';

    public ?string $custom_holiday_rate = '';

    public ?string $custom_after_hours_multiplier = '';

    public ?string $custom_emergency_multiplier = '';

    public ?string $custom_weekend_multiplier = '';

    public ?string $custom_holiday_multiplier = '';

    public ?string $custom_minimum_billing_increment = '';

    public string $custom_time_rounding_method = 'nearest';

    // Contract Information
    public ?string $contract_start_date = '';

    public ?string $contract_end_date = '';

    public ?int $sla_id = null;

    // Integration
    public ?string $rmm_id = '';

    // Additional Information
    public string $notes = '';

    public $avatar;

    public bool $remove_avatar = false;

    public function mount(Client $client)
    {
        $this->authorize('update', $client);

        // Store the client model
        $this->client = $client;

        // Load all data from the client model
        $this->name = $client->name ?? '';
        $this->type = $client->type ?: 'individual';
        $this->company_name = $client->company_name ?? '';
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';
        $this->website = $client->website ?? '';
        $this->tax_id_number = $client->tax_id_number ?? '';
        $this->referral = $client->referral ?? '';
        $this->lead = (bool) $client->lead;

        // Address fields
        $this->address = $client->address ?? '';
        $this->city = $client->city ?? '';
        $this->state = $client->state ?? '';
        $this->zip_code = $client->zip_code ?? '';
        $this->country = $client->country ?? 'US';

        // Contact fields
        $this->billing_contact = $client->billing_contact ?? '';
        $this->technical_contact = $client->technical_contact ?? '';

        // Billing fields
        $this->status = $client->status ?? 'active';
        $this->hourly_rate = $client->hourly_rate !== null ? (string) $client->hourly_rate : '';
        $this->rate = $client->rate !== null ? (string) $client->rate : '';
        $this->currency_code = $client->currency_code ?? 'USD';
        $this->net_terms = (int) ($client->net_terms ?? 30);

        // Custom rate fields
        $this->use_custom_rates = (bool) $client->use_custom_rates;
        $this->custom_rate_calculation_method = $client->custom_rate_calculation_method ?? 'multipliers';
        $this->custom_standard_rate = $client->custom_standard_rate !== null ? (string) $client->custom_standard_rate : '';
        $this->custom_after_hours_rate = $client->custom_after_hours_rate !== null ? (string) $client->custom_after_hours_rate : '';
        $this->custom_emergency_rate = $client->custom_emergency_rate !== null ? (string) $client->custom_emergency_rate : '';
        $this->custom_weekend_rate = $client->custom_weekend_rate !== null ? (string) $client->custom_weekend_rate : '';
        $this->custom_holiday_rate = $client->custom_holiday_rate !== null ? (string) $client->custom_holiday_rate : '';
        $this->custom_after_hours_multiplier = $client->custom_after_hours_multiplier !== null ? (string) $client->custom_after_hours_multiplier : '';
        $this->custom_emergency_multiplier = $client->custom_emergency_multiplier !== null ? (string) $client->custom_emergency_multiplier : '';
        $this->custom_weekend_multiplier = $client->custom_weekend_multiplier !== null ? (string) $client->custom_weekend_multiplier : '';
        $this->custom_holiday_multiplier = $client->custom_holiday_multiplier !== null ? (string) $client->custom_holiday_multiplier : '';
        $this->custom_minimum_billing_increment = $client->custom_minimum_billing_increment !== null ? (string) $client->custom_minimum_billing_increment : '';
        $this->custom_time_rounding_method = $client->custom_time_rounding_method ?? 'nearest';

        // Contract fields
        $this->contract_start_date = $client->contract_start_date ? $client->contract_start_date->format('Y-m-d') : '';
        $this->contract_end_date = $client->contract_end_date ? $client->contract_end_date->format('Y-m-d') : '';
        $this->sla_id = $client->sla_id;

        // Integration fields
        $this->rmm_id = $client->rmm_id ?? '';

        // Additional fields
        $this->notes = $client->notes ?? '';

        \Log::info('EditClient mount completed', [
            'client_id' => $client->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'status' => $this->status,
        ]);
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:individual,business',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email,'.$this->client->id,
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'tax_id_number' => 'nullable|string|max:50',
            'referral' => 'nullable|string|max:255',
            'lead' => 'boolean',

            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',

            'billing_contact' => 'nullable|string|max:255',
            'technical_contact' => 'nullable|string|max:255',

            'status' => 'required|in:active,inactive,suspended',
            'hourly_rate' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'currency_code' => 'required|string|in:USD,EUR,GBP,CAD,AUD,JPY',
            'net_terms' => 'required|in:0,15,30,45,60,90',

            'use_custom_rates' => 'boolean',
            'custom_rate_calculation_method' => 'in:fixed_rates,multipliers',
            'custom_standard_rate' => 'nullable|numeric|min:0',
            'custom_after_hours_rate' => 'nullable|numeric|min:0',
            'custom_emergency_rate' => 'nullable|numeric|min:0',
            'custom_weekend_rate' => 'nullable|numeric|min:0',
            'custom_holiday_rate' => 'nullable|numeric|min:0',
            'custom_after_hours_multiplier' => 'nullable|numeric|min:1',
            'custom_emergency_multiplier' => 'nullable|numeric|min:1',
            'custom_weekend_multiplier' => 'nullable|numeric|min:1',
            'custom_holiday_multiplier' => 'nullable|numeric|min:1',
            'custom_minimum_billing_increment' => 'nullable|numeric|min:0.01|max:2',
            'custom_time_rounding_method' => 'in:nearest,up,down,none',

            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'sla_id' => 'nullable|exists:slas,id',

            'rmm_id' => 'nullable|string|max:255',

            'notes' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ];
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedType($value)
    {
        // Clear company_name field if switching to individual type
        if ($value === 'individual') {
            $this->company_name = '';
        }
    }

    public function updatedUseCustomRates($value)
    {
        // Reset custom rate fields if disabled
        if (! $value) {
            $this->custom_standard_rate = '';
            $this->custom_after_hours_rate = '';
            $this->custom_emergency_rate = '';
            $this->custom_weekend_rate = '';
            $this->custom_holiday_rate = '';
            $this->custom_after_hours_multiplier = '';
            $this->custom_emergency_multiplier = '';
            $this->custom_weekend_multiplier = '';
            $this->custom_holiday_multiplier = '';
            $this->custom_minimum_billing_increment = '';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        // Handle avatar upload
        if ($this->avatar) {
            $avatarPath = $this->avatar->store('avatars', 'public');
            $this->client->avatar = $avatarPath;
        }

        // Handle avatar removal
        if ($this->remove_avatar && $this->client->avatar) {
            // Delete old avatar file
            if (file_exists(storage_path('app/public/'.$this->client->avatar))) {
                unlink(storage_path('app/public/'.$this->client->avatar));
            }
            $this->client->avatar = null;
        }

        // Prepare data for update
        $updateData = [
            'name' => $this->name,
            'type' => $this->type ?: null,
            'company_name' => $this->company_name ?: null,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'website' => $this->website ?: null,
            'tax_id_number' => $this->tax_id_number ?: null,
            'referral' => $this->referral ?: null,
            'lead' => $this->lead,

            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'zip_code' => $this->zip_code ?: null,
            'country' => $this->country ?: null,

            'billing_contact' => $this->billing_contact ?: null,
            'technical_contact' => $this->technical_contact ?: null,

            'status' => $this->status,
            'hourly_rate' => $this->hourly_rate !== '' ? (float) $this->hourly_rate : null,
            'rate' => $this->rate !== '' ? (float) $this->rate : null,
            'currency_code' => $this->currency_code,
            'net_terms' => (int) $this->net_terms,

            'use_custom_rates' => $this->use_custom_rates,
            'custom_rate_calculation_method' => $this->use_custom_rates ? $this->custom_rate_calculation_method : null,
            'custom_standard_rate' => $this->use_custom_rates && $this->custom_standard_rate !== '' ? (float) $this->custom_standard_rate : null,
            'custom_after_hours_rate' => $this->use_custom_rates && $this->custom_after_hours_rate !== '' ? (float) $this->custom_after_hours_rate : null,
            'custom_emergency_rate' => $this->use_custom_rates && $this->custom_emergency_rate !== '' ? (float) $this->custom_emergency_rate : null,
            'custom_weekend_rate' => $this->use_custom_rates && $this->custom_weekend_rate !== '' ? (float) $this->custom_weekend_rate : null,
            'custom_holiday_rate' => $this->use_custom_rates && $this->custom_holiday_rate !== '' ? (float) $this->custom_holiday_rate : null,
            'custom_after_hours_multiplier' => $this->use_custom_rates && $this->custom_after_hours_multiplier !== '' ? (float) $this->custom_after_hours_multiplier : null,
            'custom_emergency_multiplier' => $this->use_custom_rates && $this->custom_emergency_multiplier !== '' ? (float) $this->custom_emergency_multiplier : null,
            'custom_weekend_multiplier' => $this->use_custom_rates && $this->custom_weekend_multiplier !== '' ? (float) $this->custom_weekend_multiplier : null,
            'custom_holiday_multiplier' => $this->use_custom_rates && $this->custom_holiday_multiplier !== '' ? (float) $this->custom_holiday_multiplier : null,
            'custom_minimum_billing_increment' => $this->use_custom_rates && $this->custom_minimum_billing_increment !== '' ? (float) $this->custom_minimum_billing_increment : null,
            'custom_time_rounding_method' => $this->use_custom_rates ? $this->custom_time_rounding_method : null,

            'contract_start_date' => $this->contract_start_date ?: null,
            'contract_end_date' => $this->contract_end_date ?: null,
            'sla_id' => $this->sla_id,

            'rmm_id' => $this->rmm_id ?: null,

            'notes' => $this->notes ?: null,
        ];

        // Update client
        $this->client->update($updateData);

        session()->flash('message', 'Client updated successfully!');

        return redirect()->route('clients.show', $this->client);
    }

    public function render()
    {
        // Get available SLAs for dropdown
        $slas = \App\Domains\Ticket\Models\SLA::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->get();

        // Temporarily use simple view for debugging
        return view('livewire.clients.edit-client-simple', [
            'slas' => $slas,
        ])
            ->extends('layouts.app')
            ->section('content')
            ->title('Edit '.($this->client->lead ? 'Lead' : 'Client').' - '.$this->client->name);
    }
}
