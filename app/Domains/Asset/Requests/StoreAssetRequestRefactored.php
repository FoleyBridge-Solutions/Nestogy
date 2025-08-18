<?php

namespace App\Domains\Asset\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Asset;
use Illuminate\Validation\Rule;

class StoreAssetRequestRefactored extends BaseFormRequest
{
    protected function initializeRequest(): void
    {
        $this->modelClass = Asset::class;
        $this->requiresCompanyValidation = true;
    }

    protected function getSpecificRules(): array
    {
        return [
            'type' => ['required', Rule::in(Asset::TYPES)],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'os' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'ip'],
            'nat_ip' => ['nullable', 'ip'],
            'mac' => ['nullable', 'string', 'max:17'],
            'uri' => ['nullable', 'url', 'max:255'],
            'uri_2' => ['nullable', 'url', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expire' => ['nullable', 'date', 'after:today'],
            'next_maintenance_date' => ['nullable', 'date'],
            'install_date' => ['nullable', 'date'],
            'rmm_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function getExpectedFields(): array
    {
        return [
            'name', 'description', 'notes', 'status', 'type',
            'client_id', 'vendor_id', 'location_id', 'contact_id', 'network_id'
        ];
    }

    protected function getSpecificMessages(): array
    {
        return [
            'type.in' => 'The selected asset type is invalid. Valid types are: ' . implode(', ', Asset::TYPES),
            'warranty_expire.after' => 'The warranty expiration date must be in the future.',
            'ip.ip' => 'The IP address must be a valid IP address.',
            'nat_ip.ip' => 'The NAT IP address must be a valid IP address.',
            'uri.url' => 'The primary URI must be a valid URL.',
            'uri_2.url' => 'The secondary URI must be a valid URL.',
        ];
    }

    protected function getSpecificAttributes(): array
    {
        return [
            'type' => 'asset type',
            'serial' => 'serial number',
            'os' => 'operating system',
            'ip' => 'IP address',
            'nat_ip' => 'NAT IP address',
            'mac' => 'MAC address',
            'uri' => 'primary URI',
            'uri_2' => 'secondary URI',
            'warranty_expire' => 'warranty expiration date',
            'next_maintenance_date' => 'next maintenance date',
            'install_date' => 'installation date',
            'network_id' => 'network',
            'rmm_id' => 'RMM ID',
        ];
    }

    protected function prepareSpecificFields(): void
    {
        // Clean MAC address
        if ($this->has('mac') && $this->get('mac')) {
            $mac = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $this->get('mac')));
            if (strlen($mac) === 12) {
                $mac = implode(':', str_split($mac, 2));
            }
            $this->merge(['mac' => $mac]);
        }

        // Ensure status has a default
        if (!$this->filled('status')) {
            $this->merge(['status' => 'Ready To Deploy']);
        }
    }

    protected function performSpecificValidation($validator): void
    {
        // Validate serial number uniqueness within company
        if ($this->filled('serial')) {
            $exists = Asset::where('company_id', $this->user()->company_id)
                ->where('serial', $this->serial)
                ->exists();
                
            if ($exists) {
                $validator->errors()->add('serial', 'An asset with this serial number already exists in your company.');
            }
        }

        // Validate IP address uniqueness if provided
        if ($this->filled('ip')) {
            $exists = Asset::where('company_id', $this->user()->company_id)
                ->where('ip', $this->ip)
                ->exists();
                
            if ($exists) {
                $validator->errors()->add('ip', 'An asset with this IP address already exists in your company.');
            }
        }

        // Validate MAC address format
        if ($this->filled('mac')) {
            $macPattern = '/^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/i';
            if (!preg_match($macPattern, $this->mac)) {
                $validator->errors()->add('mac', 'The MAC address format is invalid.');
            }
        }
    }

    protected function getUniqueFields(): array
    {
        return ['serial'];
    }

    protected function getBooleanFields(): array
    {
        return array_merge(parent::getBooleanFields(), ['is_managed', 'is_monitored']);
    }
}