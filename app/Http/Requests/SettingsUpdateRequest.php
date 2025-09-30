<?php

namespace App\Http\Requests;

use App\Domains\Core\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated and have a company
        return auth()->check() && auth()->user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'timezone' => 'required|string|timezone',
            'date_format' => 'required|string|in:'.implode(',', array_keys(SettingsService::getDateFormats())),
            'currency' => 'required|string|size:3|in:'.implode(',', array_keys(SettingsService::getCurrencies())),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'company_name.max' => 'Company name must not exceed 255 characters.',
            'timezone.required' => 'Timezone is required.',
            'timezone.timezone' => 'Please select a valid timezone.',
            'date_format.required' => 'Date format is required.',
            'date_format.in' => 'Please select a valid date format.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency code must be exactly 3 characters.',
            'currency.in' => 'Please select a valid currency.',
        ];
    }
}
