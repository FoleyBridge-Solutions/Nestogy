<?php

namespace App\Domains\Asset\Requests;

use App\Domains\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    private const MAX_STRING_LENGTH = 'max:255';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('asset'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $asset = $this->route('asset');

        return [
            'name' => ['required', 'string', self::MAX_STRING_LENGTH],
            'type' => ['required', Rule::in(Asset::TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'make' => ['nullable', 'string', self::MAX_STRING_LENGTH],
            'model' => ['nullable', 'string', self::MAX_STRING_LENGTH],
            'serial' => ['nullable', 'string', self::MAX_STRING_LENGTH],
            'os' => ['nullable', 'string', self::MAX_STRING_LENGTH],
            'ip' => ['nullable', 'ip'],
            'nat_ip' => ['nullable', 'ip'],
            'mac' => ['nullable', 'string', 'max:17'],
            'uri' => ['nullable', 'url', self::MAX_STRING_LENGTH],
            'uri_2' => ['nullable', 'url', self::MAX_STRING_LENGTH],
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expire' => ['nullable', 'date'],
            'next_maintenance_date' => ['nullable', 'date'],
            'install_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'vendor_id' => [
                'nullable',
                Rule::exists('vendors', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'contact_id' => [
                'nullable',
                Rule::exists('contacts', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'network_id' => [
                'nullable',
                Rule::exists('networks', 'id')->where(function ($query) {
                    $query->where('company_id', $this->user()->company_id);
                }),
            ],
            'rmm_id' => ['nullable', 'string', self::MAX_STRING_LENGTH],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'asset name',
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
            'client_id' => 'client',
            'vendor_id' => 'vendor',
            'location_id' => 'location',
            'contact_id' => 'assigned contact',
            'network_id' => 'network',
            'rmm_id' => 'RMM ID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The selected asset type is invalid. Valid types are: '.implode(', ', Asset::TYPES),
            'status.in' => 'The selected status is invalid. Valid statuses are: '.implode(', ', Asset::STATUSES),
            'ip.ip' => 'The IP address must be a valid IP address.',
            'nat_ip.ip' => 'The NAT IP address must be a valid IP address.',
            'uri.url' => 'The primary URI must be a valid URL.',
            'uri_2.url' => 'The secondary URI must be a valid URL.',
            'client_id.exists' => 'The selected client does not exist or does not belong to your company.',
            'vendor_id.exists' => 'The selected vendor does not exist or does not belong to your company.',
            'location_id.exists' => 'The selected location does not exist or does not belong to your company.',
            'contact_id.exists' => 'The selected contact does not exist or does not belong to your company.',
            'network_id.exists' => 'The selected network does not exist or does not belong to your company.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $asset = $this->route('asset');

            // Custom validation logic can go here
            // For example, ensuring serial number is unique within company (excluding current asset)
            if ($this->serial) {
                $exists = \App\Models\Asset::where('company_id', $this->user()->company_id)
                    ->where('serial', $this->serial)
                    ->where('id', '!=', $asset->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('serial', 'An asset with this serial number already exists in your company.');
                }
            }
        });
    }
}
